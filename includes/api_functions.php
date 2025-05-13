
<?php
/**
 * Fonctions pour communiquer avec l'API Python
 */

/**
 * Envoie une requête à l'API Python
 */
function callApi($endpoint, $method = 'GET', $data = null) {
    // Vérifier que l'URL de l'API est définie
    if (!defined('API_URL')) {
        define('API_URL', 'http://127.0.0.1:5000/api');
        error_log("API_URL n'était pas définie. Définie par défaut à http://127.0.0.1:5000/api");
    }
    
    $apiUrl = API_URL . $endpoint;
    
    // Initialiser cURL
    $ch = curl_init();
    
    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Augmenter le timeout pour le développement local
    
    // Ignorer les erreurs SSL en développement local
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Ajouter le token d'API (utiliser le token de session si disponible)
    $headers = [
        'Content-Type: application/json',
    ];
    
    // Utiliser le token stocké en session s'il existe
    if (isset($_SESSION['api_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['api_token'];
    } else {
        $headers[] = 'x-api-token: secure_api_token_for_testing';  // Token simple pour les tests
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Configurer la méthode HTTP
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Fermer la connexion cURL
    curl_close($ch);
    
    // Log pour le débogage en développement
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("API Call to: $apiUrl");
        error_log("Response code: $httpCode");
        error_log("Response: $response");
        if ($error) error_log("cURL Error: $error");
    }
    
    // Gérer les erreurs
    if ($error) {
        return [
            'success' => false,
            'error' => 'Erreur cURL: ' . $error,
            'code' => $httpCode
        ];
    }
    
    // Décoder la réponse JSON
    $result = json_decode($response, true);
    
    if (!$result) {
        return [
            'success' => false,
            'error' => 'Réponse invalide de l\'API',
            'code' => $httpCode,
            'raw' => $response
        ];
    }
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'data' => $result,
        'code' => $httpCode
    ];
}

/**
 * Récupère les utilisateurs depuis l'API
 */
function getApiUsers($dbType = 'company') {
    $response = callApi('/users?db_type=' . $dbType);
    
    if ($response['success'] && isset($response['data']['users'])) {
        return $response['data']['users'];
    }
    
    return [];
}

/**
 * Récupère tous les utilisateurs (entreprise et partenaires) pour l'assignation des tâches
 */
function getAllUsersForAssignment() {
    $companyUsers = getApiUsers('company');
    $partnerUsers = getApiUsers('partner');
    
    // Fallback en cas d'échec de l'API - utiliser les données locales pour les utilisateurs de l'entreprise
    if (empty($companyUsers)) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, username, role FROM users ORDER BY username");
            $stmt->execute();
            $companyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des utilisateurs locaux: " . $e->getMessage());
        }
    }
    
    // Combiner les deux listes d'utilisateurs
    $allUsers = [];
    
    // Ajouter les utilisateurs de l'entreprise
    foreach ($companyUsers as $user) {
        $user['user_type'] = 'company';
        $allUsers[] = $user;
    }
    
    // Ajouter les utilisateurs partenaires
    foreach ($partnerUsers as $user) {
        $user['user_type'] = 'partner';
        $allUsers[] = $user;
    }
    
    return $allUsers;
}

/**
 * Récupère les tâches depuis l'API
 */
function getApiTasks($dbType = 'company') {
    $response = callApi('/tasks?db_type=' . $dbType);
    
    if ($response['success'] && isset($response['data']['tasks'])) {
        return $response['data']['tasks'];
    }
    
    return [];
}

/**
 * Récupère toutes les tâches (entreprise et partenaires) via l'API
 * Cette fonction est différente de celle dans functions.php qui utilise directement la BDD
 */
function getApiAllTasks() {
    $companyTasks = getApiTasks('company');
    $partnerTasks = getApiTasks('partner');
    
    // Combiner les deux listes de tâches
    $allTasks = array_merge($companyTasks, $partnerTasks);
    
    // Ajouter une propriété pour identifier la source de la tâche
    foreach ($allTasks as $index => $task) {
        $allTasks[$index]['source'] = isset($task['source']) ? $task['source'] : 'company';
    }
    
    return $allTasks;
}

/**
 * Crée une nouvelle tâche via l'API
 */
function createApiTask($taskData, $dbType = 'company') {
    $response = callApi('/tasks/create?db_type=' . $dbType, 'POST', $taskData);
    
    // Si la création réussit, synchroniser automatiquement avec l'autre base de données
    if ($response['success']) {
        syncTasks($dbType);
    }
    
    return $response;
}

/**
 * Met à jour une tâche existante via l'API
 */
function updateApiTask($taskId, $taskData, $dbType = 'company') {
    $response = callApi('/tasks/update/' . $taskId . '?db_type=' . $dbType, 'POST', $taskData);
    
    // Si la mise à jour réussit, synchroniser automatiquement avec l'autre base de données
    if ($response['success']) {
        syncTasks($dbType);
    }
    
    return $response;
}

/**
 * Supprime une tâche via l'API
 */
function deleteApiTask($taskId, $dbType = 'company') {
    $response = callApi('/tasks/delete/' . $taskId . '?db_type=' . $dbType, 'POST');
    
    // Si la suppression réussit, synchroniser automatiquement avec l'autre base de données
    if ($response['success']) {
        syncTasks($dbType);
    }
    
    return $response;
}

/**
 * Synchronise les utilisateurs entre les bases de données
 */
function syncUsers($sourceDb = 'company') {
    $response = callApi('/sync/users?source=' . $sourceDb, 'POST');
    return $response;
}

/**
 * Synchronise les tâches entre les bases de données
 */
function syncTasks($sourceDb = 'company') {
    $response = callApi('/sync/tasks?source=' . $sourceDb, 'POST');
    return $response;
}

/**
 * Inscrit un nouveau partenaire
 */
function registerPartner($userData) {
    $response = callApi('/partner/register', 'POST', $userData);
    return $response;
}

/**
 * Connecte un partenaire
 */
function loginPartner($credentials) {
    $response = callApi('/partner/login', 'POST', $credentials);
    return $response;
}

/**
 * Vérifie si l'API Python est accessible
 */
function checkApiStatus() {
    $response = callApi('/test');
    return $response['success'] ? true : false;
}

/**
 * Récupère les statistiques du partenaire
 */
function getPartnerStats() {
    $response = callApi('/partner/stats', 'GET');
    
    if ($response['success'] && isset($response['data']['stats'])) {
        return $response['data']['stats'];
    }
    
    return [];
}

/**
 * Récupère les détails d'un utilisateur par son ID
 */
function getUserById($userId, $dbType = 'company') {
    $response = callApi('/users/' . $userId . '?db_type=' . $dbType);
    
    if ($response['success'] && isset($response['data']['user'])) {
        return $response['data']['user'];
    }
    
    return null;
}

/**
 * Assigne une tâche à un utilisateur
 */
function assignTask($taskId, $userId, $dbType = 'company') {
    $data = ['user_id' => $userId];
    $response = callApi('/tasks/assign/' . $taskId . '?db_type=' . $dbType, 'POST', $data);
    
    // Si l'assignation réussit, synchroniser automatiquement
    if ($response['success']) {
        syncTasks($dbType);
    }
    
    return $response;
}

/**
 * Récupère les tâches d'un utilisateur via l'API
 * Renommé pour éviter le conflit avec la fonction dans functions.php
 */
function getApiUserTasks($pdo, $userId, $role) {
    // Si c'est un admin ou un manager, récupérer toutes les tâches
    if ($role === 'admin' || $role === 'manager') {
        $stmt = $pdo->prepare("
            SELECT t.*, u.username as assigned_to_name 
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            ORDER BY 
                CASE 
                    WHEN t.status = 'à faire' THEN 1
                    WHEN t.status = 'en cours' THEN 2
                    ELSE 3
                END, 
                t.due_date ASC
        ");
        $stmt->execute();
    } else {
        // Sinon, récupérer uniquement les tâches assignées à l'utilisateur
        $stmt = $pdo->prepare("
            SELECT t.*, u.username as assigned_to_name 
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.assigned_to = ?
            ORDER BY 
                CASE 
                    WHEN t.status = 'à faire' THEN 1
                    WHEN t.status = 'en cours' THEN 2
                    ELSE 3
                END, 
                t.due_date ASC
        ");
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Filtre les tâches en fonction du rôle et des droits de l'utilisateur
 */
function filterTasks($tasks, $userId, $role, $isPartner = false) {
    $filteredTasks = [];
    
    foreach ($tasks as $task) {
        // Admin peut voir toutes les tâches
        if ($role === 'admin') {
            $filteredTasks[] = $task;
        }
        // Manager peut voir toutes les tâches de l'entreprise et celles partenaires assignées
        elseif ($role === 'manager') {
            if ($task['source'] === 'company' || ($task['source'] === 'partner' && isset($task['assigned_to']) && $task['assigned_to'] == $userId)) {
                $filteredTasks[] = $task;
            }
        }
        // Utilisateur standard ne voit que ses tâches
        elseif ($task['assigned_to'] == $userId) {
            $filteredTasks[] = $task;
        }
    }
    
    return $filteredTasks;
}

/**
 * Vérifie si un utilisateur a le droit de modifier une tâche via l'API
 * Renommé pour éviter le conflit avec la fonction dans functions.php
 */
function apiCanUserEditTask($pdo, $userId, $role, $taskId) {
    // Admin et manager peuvent modifier toutes les tâches
    if ($role === 'admin' || $role === 'manager') {
        return true;
    }
    
    // Pour les utilisateurs standard, vérifier s'ils sont assignés à la tâche
    $stmt = $pdo->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    
    if ($task && $task['assigned_to'] == $userId) {
        return true;
    }
    
    return false;
}
