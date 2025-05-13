
<?php
/**
 * Fonctions utilitaires pour l'application
 */

/**
 * Récupère toutes les tâches pour un utilisateur spécifique
 * Fonctionne uniquement avec la base de données locale
 */
function getAllTasks($pdo, $user_id, $role, $status = '', $priority = '') {
    // Base de la requête SQL
    $sql = "SELECT t.*, u.username as assigned_to_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id";
    
    // Conditions WHERE en fonction du rôle de l'utilisateur
    $where = [];
    $params = [];
    
    // Filtrer par statut si spécifié
    if (!empty($status)) {
        $where[] = "t.status = ?";
        $params[] = $status;
    }
    
    // Filtrer par priorité si spécifié
    if (!empty($priority)) {
        $where[] = "t.priority = ?";
        $params[] = $priority;
    }
    
    // Si l'utilisateur n'est pas admin ou manager, il ne voit que ses tâches
    if ($role === 'user') {
        $where[] = "t.assigned_to = ?";
        $params[] = $user_id;
    }
    
    // Ajouter les conditions WHERE à la requête
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Trier par date d'échéance et priorité
    $sql .= " ORDER BY 
            CASE t.priority
                WHEN 'urgente' THEN 1
                WHEN 'haute' THEN 2
                WHEN 'moyenne' THEN 3
                WHEN 'basse' THEN 4
                ELSE 5
            END,
            t.due_date ASC";
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Récupère les tâches d'un utilisateur en fonction de son rôle
 */
function getUserTasks($pdo, $user_id, $role) {
    // La fonction getAllTasks gère déjà la logique des permissions selon le rôle
    return getAllTasks($pdo, $user_id, $role);
}

/**
 * Récupère les statistiques des tâches
 */
function getTaskStatistics($pdo, $user_id, $role) {
    $stats = [
        'total' => 0,
        'completed' => 0,
        'pending' => 0
    ];
    
    // Récupérer toutes les tâches de l'utilisateur
    $tasks = getUserTasks($pdo, $user_id, $role);
    
    // Calculer les statistiques
    $stats['total'] = count($tasks);
    
    foreach ($tasks as $task) {
        if ($task['status'] === 'terminé') {
            $stats['completed']++;
        } else {
            $stats['pending']++;
        }
    }
    
    return $stats;
}

/**
 * Vérifie si un utilisateur a le droit de modifier une tâche spécifique
 */
function canUserEditTask($pdo, $user_id, $role, $task_id) {
    // Les administrateurs peuvent tout modifier
    if ($role === 'admin') {
        return true;
    }
    
    // Les managers peuvent modifier toutes les tâches
    if ($role === 'manager') {
        return true;
    }
    
    // Les utilisateurs standards ne peuvent modifier que leurs tâches
    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$task_id, $user_id]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Met à jour le statut d'une tâche
 */
function updateTaskStatus($pdo, $task_id, $status) {
    $stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $task_id]);
    
    // Pour assurer la synchronisation, appeler l'API après mise à jour locale
    if (function_exists('syncTasks')) {
        syncTasks('company');
    }
    
    return $stmt->rowCount() > 0;
}

/**
 * Formate une date pour l'affichage
 */
function formatDate($date) {
    if (empty($date)) {
        return 'N/A';
    }
    
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Limite le texte à un certain nombre de caractères
 */
function limitText($text, $limit = 100) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    return substr($text, 0, $limit) . '...';
}

/**
 * Retourne la classe CSS pour une priorité donnée
 */
function getPriorityClass($priority) {
    switch ($priority) {
        case 'basse':
            return 'info';
        case 'moyenne':
            return 'success';
        case 'haute':
            return 'warning';
        case 'urgente':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Retourne la classe CSS pour un statut donné
 */
function getStatusClass($status) {
    switch ($status) {
        case 'à faire':
            return 'secondary';
        case 'en cours':
            return 'primary';
        case 'terminé':
            return 'success';
        default:
            return 'info';
    }
}

/**
 * Retourne la classe de badge pour un statut (pour la page sync_data)
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'à faire':
            return 'secondary';
        case 'en cours':
            return 'primary';
        case 'terminé':
            return 'success';
        default:
            return 'info';
    }
}

/**
 * Retourne la classe de badge pour une priorité (pour la page sync_data)
 */
function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'basse':
            return 'info';
        case 'moyenne':
            return 'success';
        case 'haute':
            return 'warning';
        case 'urgente':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Retourne la classe de badge pour un rôle
 */
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'admin':
            return 'danger';
        case 'manager':
            return 'warning';
        case 'user':
            return 'primary';
        default:
            return 'secondary';
    }
}
