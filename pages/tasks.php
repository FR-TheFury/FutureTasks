
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/partner_config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$is_partner = isset($_SESSION['is_partner']) && $_SESSION['is_partner'];

// Récupérer les tâches
$companyTasks = [];
$partnerTasks = [];

if (checkApiStatus()) {
    try {
        // Récupérer toutes les tâches (entreprise et partenaires)
        $companyTasks = getApiTasks('company');
        $partnerTasks = getApiTasks('partner');
    } catch (Exception $e) {
        $error = "Erreur lors de la récupération des tâches via l'API: " . $e->getMessage();
    }
} else {
    // Fallback - Récupérer uniquement les tâches de l'entreprise depuis la base de données locale
    $companyTasks = getUserTasks($pdo, $user_id, $role);
}

// Combiner les deux listes de tâches et ajouter l'information de source
foreach ($companyTasks as &$task) {
    $task['source'] = 'company';
}

foreach ($partnerTasks as &$task) {
    $task['source'] = 'partner';
}

$allTasks = array_merge($companyTasks, $partnerTasks);

// Filtrer les tâches en fonction du rôle et des préférences utilisateur
$filteredTasks = filterTasks($allTasks, $user_id, $role, $is_partner);

// Trier les tâches par statut et date d'échéance
usort($filteredTasks, function($a, $b) {
    // D'abord par statut (à faire en premier)
    if ($a['status'] !== $b['status']) {
        if ($a['status'] === 'à faire') return -1;
        if ($b['status'] === 'à faire') return 1;
        if ($a['status'] === 'en cours') return -1;
        if ($b['status'] === 'en cours') return 1;
    }
    
    // Ensuite par date d'échéance
    $dateA = strtotime($a['due_date']);
    $dateB = strtotime($b['due_date']);
    return $dateA - $dateB;
});

// Variables pour la pagination
$tasksPerPage = 10;
$totalTasks = count($filteredTasks);
$totalPages = ceil($totalTasks / $tasksPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($currentPage - 1) * $tasksPerPage;
$paginatedTasks = array_slice($filteredTasks, $offset, $tasksPerPage);

// Filtrage par statut ou priorité si demandé
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';

if ($statusFilter || $priorityFilter) {
    $filteredTasks = array_filter($filteredTasks, function($task) use ($statusFilter, $priorityFilter) {
        $statusMatch = !$statusFilter || $task['status'] === $statusFilter;
        $priorityMatch = !$priorityFilter || $task['priority'] === $priorityFilter;
        return $statusMatch && $priorityMatch;
    });
    
    // Recalculer la pagination après filtrage
    $totalTasks = count($filteredTasks);
    $totalPages = ceil($totalTasks / $tasksPerPage);
    $currentPage = min($currentPage, max(1, $totalPages));
    $offset = ($currentPage - 1) * $tasksPerPage;
    $paginatedTasks = array_slice($filteredTasks, $offset, $tasksPerPage);
}

// Recherche si demandée
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchQuery) {
    $searchResults = array_filter($filteredTasks, function($task) use ($searchQuery) {
        return (stripos($task['title'], $searchQuery) !== false) || 
               (stripos($task['description'], $searchQuery) !== false);
    });
    
    // Recalculer la pagination après recherche
    $totalTasks = count($searchResults);
    $totalPages = ceil($totalTasks / $tasksPerPage);
    $currentPage = min($currentPage, max(1, $totalPages));
    $offset = ($currentPage - 1) * $tasksPerPage;
    $paginatedTasks = array_slice($searchResults, $offset, $tasksPerPage);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tâches - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <!-- Background Three.js (réduit) -->
    <div id="bg-animation" class="bg-subtle"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include('../includes/sidebar.php'); ?>
        
        <!-- Contenu principal -->
        <main class="main-content">
            <header class="dashboard-header">
                <h1>Gestion des tâches</h1>
                <?php if ($role === 'admin' || $role === 'manager'): ?>
                <div>
                    <a href="add_task.php" class="futuristic-button">
                        <i class="bi bi-plus-lg"></i> Nouvelle tâche
                    </a>
                </div>
                <?php endif; ?>
            </header>
            
            <!-- Filtres et recherche -->
            <div class="filters-container mb-4">
                <form action="tasks.php" method="GET" class="d-flex flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="form-control">
                            <button type="submit" class="btn btn-sm">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="à faire" <?php if($statusFilter === 'à faire') echo 'selected'; ?>>À faire</option>
                            <option value="en cours" <?php if($statusFilter === 'en cours') echo 'selected'; ?>>En cours</option>
                            <option value="terminé" <?php if($statusFilter === 'terminé') echo 'selected'; ?>>Terminé</option>
                        </select>
                    </div>
                    
                    <div>
                        <select name="priority" class="form-select" onchange="this.form.submit()">
                            <option value="">Toutes les priorités</option>
                            <option value="basse" <?php if($priorityFilter === 'basse') echo 'selected'; ?>>Basse</option>
                            <option value="moyenne" <?php if($priorityFilter === 'moyenne') echo 'selected'; ?>>Moyenne</option>
                            <option value="haute" <?php if($priorityFilter === 'haute') echo 'selected'; ?>>Haute</option>
                            <option value="urgente" <?php if($priorityFilter === 'urgente') echo 'selected'; ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <?php if ($searchQuery || $statusFilter || $priorityFilter): ?>
                    <div>
                        <a href="tasks.php" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($paginatedTasks)): ?>
                <div class="alert alert-info">
                    Aucune tâche trouvée.
                    <?php if ($searchQuery || $statusFilter || $priorityFilter): ?>
                        <a href="tasks.php">Afficher toutes les tâches</a>
                    <?php elseif ($role === 'admin' || $role === 'manager'): ?>
                        <a href="add_task.php">Créer une nouvelle tâche</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive futuristic-panel">
                    <table class="table table-hover task-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Assigné à</th>
                                <th>Échéance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginatedTasks as $task): ?>
                                <?php 
                                // Déterminer les classes CSS en fonction du statut et de la priorité
                                $statusClass = '';
                                switch ($task['status']) {
                                    case 'à faire':
                                        $statusClass = 'status-todo';
                                        break;
                                    case 'en cours':
                                        $statusClass = 'status-in-progress';
                                        break;
                                    case 'terminé':
                                        $statusClass = 'status-completed';
                                        break;
                                }
                                
                                $priorityClass = '';
                                switch ($task['priority']) {
                                    case 'basse':
                                        $priorityClass = 'priority-low';
                                        break;
                                    case 'moyenne':
                                        $priorityClass = 'priority-medium';
                                        break;
                                    case 'haute':
                                        $priorityClass = 'priority-high';
                                        break;
                                    case 'urgente':
                                        $priorityClass = 'priority-urgent';
                                        break;
                                }
                                
                                // Formater la date d'échéance
                                $dueDate = new DateTime($task['due_date']);
                                $now = new DateTime();
                                $interval = $now->diff($dueDate);
                                $isPast = $dueDate < $now;
                                
                                $dueDateClass = '';
                                if ($isPast && $task['status'] !== 'terminé') {
                                    $dueDateClass = 'text-danger';
                                } elseif ($interval->days <= 2 && !$isPast && $task['status'] !== 'terminé') {
                                    $dueDateClass = 'text-warning';
                                }
                                
                                $formattedDueDate = $dueDate->format('d/m/Y');
                                
                                // Vérifier si l'utilisateur peut modifier cette tâche
                                $canEdit = false;
                                if ($role === 'admin') {
                                    $canEdit = true;
                                } elseif ($role === 'manager' && $task['source'] === 'company') {
                                    $canEdit = true;
                                } elseif ($is_partner && $task['source'] === 'partner' && $task['created_by'] == $user_id) {
                                    $canEdit = true;
                                }
                                
                                $source = $task['source'] === 'partner' ? 'Partenaire' : 'Entreprise';
                                ?>
                                <tr class="<?php echo $statusClass; ?>">
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><span class="priority-badge <?php echo $priorityClass; ?>"><?php echo htmlspecialchars($task['priority']); ?></span></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span></td>
                                    <td>
                                        <?php 
                                        // Afficher le nom de l'utilisateur assigné
                                        echo htmlspecialchars($task['assigned_to_name'] ?? 'Non assigné'); 
                                        
                                        // Ajouter une indication si c'est un partenaire
                                        if ($task['source'] === 'partner' && !empty($task['assigned_to_name'])) {
                                            echo ' <span class="badge bg-info">Partenaire</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="<?php echo $dueDateClass; ?>"><?php echo $formattedDueDate; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="task_detail.php?id=<?php echo $task['id']; ?>&source=<?php echo $task['source']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Détails
                                            </a>
                                            <?php if ($canEdit): ?>
                                                <a href="edit_task.php?id=<?php echo $task['id']; ?>&source=<?php echo $task['source']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Éditer
                                                </a>
                                                <a href="delete_task.php?id=<?php echo $task['id']; ?>&source=<?php echo $task['source']; ?>&confirm=0" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $priorityFilter ? '&priority=' . urlencode($priorityFilter) : ''; ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i === $currentPage) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $priorityFilter ? '&priority=' . urlencode($priorityFilter) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $priorityFilter ? '&priority=' . urlencode($priorityFilter) : ''; ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
