
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/partner_config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Vérifier si l'utilisateur est connecté et a le rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$apiStatus = checkApiStatus();

// Actions de synchronisation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sync_users_to_partner'])) {
        $response = syncUsers('company');
        if ($response['success']) {
            $message = 'Utilisateurs synchronisés avec succès vers la base de données partenaire.';
        } else {
            $error = 'Erreur lors de la synchronisation des utilisateurs: ' . ($response['error'] ?? 'Erreur inconnue');
        }
    }
    elseif (isset($_POST['sync_users_from_partner'])) {
        $response = syncUsers('partner');
        if ($response['success']) {
            $message = 'Utilisateurs synchronisés avec succès depuis la base de données partenaire.';
        } else {
            $error = 'Erreur lors de la synchronisation des utilisateurs: ' . ($response['error'] ?? 'Erreur inconnue');
        }
    }
    elseif (isset($_POST['sync_tasks_to_partner'])) {
        $response = syncTasks('company');
        if ($response['success']) {
            $message = 'Tâches synchronisées avec succès vers la base de données partenaire.';
        } else {
            $error = 'Erreur lors de la synchronisation des tâches: ' . ($response['error'] ?? 'Erreur inconnue');
        }
    }
    elseif (isset($_POST['sync_tasks_from_partner'])) {
        $response = syncTasks('partner');
        if ($response['success']) {
            $message = 'Tâches synchronisées avec succès depuis la base de données partenaire.';
        } else {
            $error = 'Erreur lors de la synchronisation des tâches: ' . ($response['error'] ?? 'Erreur inconnue');
        }
    }
}

// Récupérer les utilisateurs des deux bases de données si l'API est accessible
$companyUsers = $apiStatus ? getApiUsers('company') : [];
$partnerUsers = $apiStatus ? getApiUsers('partner') : [];

// Récupérer les tâches des deux bases de données si l'API est accessible
$companyTasks = $apiStatus ? getApiTasks('company') : [];
$partnerTasks = $apiStatus ? getApiTasks('partner') : [];

// Titre de la page
$pageTitle = 'Synchronisation des données';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <!-- Background Three.js -->
    <div id="bg-animation"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once('../includes/sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="futuristic-title"><?php echo $pageTitle; ?></h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success animate__animated animate__fadeIn">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger animate__animated animate__fadeIn">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- État de l'API -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="futuristic-panel">
                            <div class="panel-header">
                                <h4>État de l'API Python</h4>
                            </div>
                            <div class="panel-content">
                                <?php if ($apiStatus): ?>
                                    <div class="alert alert-success mb-0">
                                        <strong>API connectée et fonctionnelle ✓</strong>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger mb-0">
                                        <strong>API non disponible ✗</strong>
                                        <p>Veuillez vérifier que le serveur API Python est démarré.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions de synchronisation -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="futuristic-panel">
                            <div class="panel-header">
                                <h4>Actions de synchronisation</h4>
                            </div>
                            <div class="panel-content">
                                <?php if ($apiStatus): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Synchronisation des utilisateurs</h5>
                                            <div class="d-flex gap-2 mb-3">
                                                <form method="POST" action="">
                                                    <button type="submit" name="sync_users_to_partner" class="futuristic-button">
                                                        Vers le partenaire
                                                    </button>
                                                </form>
                                                <form method="POST" action="">
                                                    <button type="submit" name="sync_users_from_partner" class="futuristic-button outline">
                                                        Depuis le partenaire
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Synchronisation des tâches</h5>
                                            <div class="d-flex gap-2 mb-3">
                                                <form method="POST" action="">
                                                    <button type="submit" name="sync_tasks_to_partner" class="futuristic-button">
                                                        Vers le partenaire
                                                    </button>
                                                </form>
                                                <form method="POST" action="">
                                                    <button type="submit" name="sync_tasks_from_partner" class="futuristic-button outline">
                                                        Depuis le partenaire
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <strong>Actions désactivées</strong>
                                        <p>Les actions de synchronisation ne sont pas disponibles car l'API Python n'est pas accessible.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onglets pour les différents types de données -->
                <ul class="nav nav-tabs mb-4" id="syncTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">
                            Utilisateurs
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" aria-selected="false">
                            Tâches
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="syncTabsContent">
                    <!-- Onglet Utilisateurs -->
                    <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div class="row">
                            <!-- Utilisateurs de l'entreprise -->
                            <div class="col-md-6 mb-4">
                                <div class="futuristic-panel">
                                    <div class="panel-header">
                                        <h4>Utilisateurs de votre entreprise</h4>
                                    </div>
                                    <div class="panel-content">
                                        <?php if ($apiStatus && !empty($companyUsers)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Nom</th>
                                                            <th>Email</th>
                                                            <th>Rôle</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($companyUsers as $user): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'primary'); ?>">
                                                                        <?php echo htmlspecialchars($user['role']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php elseif (!$apiStatus): ?>
                                            <div class="alert alert-warning mb-0">
                                                <p>Impossible de récupérer les données car l'API n'est pas accessible.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                <p>Aucun utilisateur trouvé.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Utilisateurs du partenaire -->
                            <div class="col-md-6 mb-4">
                                <div class="futuristic-panel">
                                    <div class="panel-header">
                                        <h4>Utilisateurs du partenaire</h4>
                                    </div>
                                    <div class="panel-content">
                                        <?php if ($apiStatus && !empty($partnerUsers)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Nom</th>
                                                            <th>Email</th>
                                                            <th>Entreprise</th>
                                                            <th>Rôle</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($partnerUsers as $user): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'primary'); ?>">
                                                                        <?php echo htmlspecialchars($user['role']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php elseif (!$apiStatus): ?>
                                            <div class="alert alert-warning mb-0">
                                                <p>Impossible de récupérer les données car l'API n'est pas accessible.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                <p>Aucun utilisateur partenaire trouvé.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Tâches -->
                    <div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
                        <div class="row">
                            <!-- Tâches de l'entreprise -->
                            <div class="col-md-6 mb-4">
                                <div class="futuristic-panel">
                                    <div class="panel-header">
                                        <h4>Tâches de votre entreprise</h4>
                                    </div>
                                    <div class="panel-content">
                                        <?php if ($apiStatus && !empty($companyTasks)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Titre</th>
                                                            <th>Assigné à</th>
                                                            <th>Statut</th>
                                                            <th>Priorité</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($companyTasks as $task): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                                                <td><?php echo htmlspecialchars($task['assigned_to_name']); ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                                                        <?php echo htmlspecialchars($task['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?>">
                                                                        <?php echo htmlspecialchars($task['priority']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php elseif (!$apiStatus): ?>
                                            <div class="alert alert-warning mb-0">
                                                <p>Impossible de récupérer les données car l'API n'est pas accessible.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                <p>Aucune tâche trouvée.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tâches du partenaire -->
                            <div class="col-md-6 mb-4">
                                <div class="futuristic-panel">
                                    <div class="panel-header">
                                        <h4>Tâches du partenaire</h4>
                                    </div>
                                    <div class="panel-content">
                                        <?php if ($apiStatus && !empty($partnerTasks)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Titre</th>
                                                            <th>Assigné à</th>
                                                            <th>Statut</th>
                                                            <th>Priorité</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($partnerTasks as $task): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                                                <td><?php echo htmlspecialchars($task['assigned_to_name']); ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo getStatusBadgeClass($task['status']); ?>">
                                                                        <?php echo htmlspecialchars($task['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo getPriorityBadgeClass($task['priority']); ?>">
                                                                        <?php echo htmlspecialchars($task['priority']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php elseif (!$apiStatus): ?>
                                            <div class="alert alert-warning mb-0">
                                                <p>Impossible de récupérer les données car l'API n'est pas accessible.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                <p>Aucune tâche partenaire trouvée.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
