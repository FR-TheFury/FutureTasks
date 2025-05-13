
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$account_type = $_SESSION['account_type'] ?? 'company';

// Déterminer quelle base de données utiliser
if ($account_type === 'partner') {
    require_once('../includes/partner_config.php');
    $current_pdo = $partner_pdo ?? $pdo; // Fallback au pdo principal si partner_pdo n'est pas défini
} else {
    $current_pdo = $pdo;
}

// Récupérer les tâches de l'utilisateur selon son rôle
// Utilisez la fonction de functions.php pour assurer la compatibilité
$tasks = getUserTasks($current_pdo, $user_id, $role);

// Récupérer les statistiques
$stats = getTaskStatistics($current_pdo, $user_id, $role);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
                <h1>Tableau de bord <?php echo $account_type === 'partner' ? 'Partenaire' : ''; ?></h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role badge bg-primary"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                    <?php if ($account_type === 'partner'): ?>
                        <span class="user-type badge bg-info">Partenaire</span>
                    <?php endif; ?>
                </div>
            </header>
            
            <div class="row stats-cards">
                <div class="col-md-4 mb-4">
                    <div class="futuristic-card">
                        <div class="card-icon">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <h3>Total des tâches</h3>
                        <p class="stat-value"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="futuristic-card">
                        <div class="card-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3>Tâches terminées</h3>
                        <p class="stat-value"><?php echo $stats['completed']; ?></p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="futuristic-card">
                        <div class="card-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3>En cours</h3>
                        <p class="stat-value"><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="recent-tasks futuristic-panel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Tâches récentes</h2>
                    <a href="tasks.php" class="futuristic-button sm">Voir toutes les tâches</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-dark table-hover futuristic-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Priorité</th>
                                <th>Date d'échéance</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tasks) > 0): ?>
                                <?php foreach(array_slice($tasks, 0, 5) as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getPriorityClass($task['priority']); ?>">
                                                <?php echo htmlspecialchars($task['priority']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($task['due_date']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusClass($task['status']); ?>">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucune tâche à afficher</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($account_type === 'partner'): ?>
            <div class="mt-4 futuristic-panel">
                <h2 class="mb-4">Zone Partenaire</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-dark text-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Synchronisation des données</h5>
                                <p class="card-text">Synchronisez vos tâches avec l'entreprise principale.</p>
                                <a href="sync_data.php" class="futuristic-button sm">Synchroniser</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Documentation API</h5>
                                <p class="card-text">Accédez à la documentation de l'API pour intégrer vos services.</p>
                                <a href="#" class="futuristic-button sm disabled">Voir la documentation</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
