
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Vérifier l'état de l'API
$apiStatus = checkApiStatus();

// Traitement pour changer le rôle d'un utilisateur
if (isset($_POST['change_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    $target_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_role = htmlspecialchars(trim($_POST['role']), ENT_QUOTES, 'UTF-8');
    
    // Ne pas permettre de changer son propre rôle
    if ($target_user_id == $user_id) {
        $error = 'Vous ne pouvez pas changer votre propre rôle.';
    } else {
        // Vérifier que le rôle est valide
        $valid_roles = ['user', 'manager', 'admin'];
        if (in_array($new_role, $valid_roles)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $target_user_id])) {
                $success = 'Rôle de l\'utilisateur mis à jour avec succès.';
            } else {
                $error = 'Erreur lors de la mise à jour du rôle.';
            }
        } else {
            $error = 'Rôle invalide.';
        }
    }
}

// Récupérer la liste des utilisateurs
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Récupérer les utilisateurs partenaires via l'API si disponible
$partnerUsers = [];
if ($apiStatus) {
    $partnerUsers = getApiUsers('partner');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* Style spécifique pour cette page pour éviter les problèmes d'édition */
        .futuristic-panel {
            overflow: visible !important;
        }
        .modal-dialog {
            z-index: 1051;
        }
        .modal-content {
            position: relative;
            z-index: 1052;
        }
    </style>
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
                <h1>Gestion des utilisateurs</h1>
                <button type="button" class="futuristic-button" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Ajouter un utilisateur
                </button>
            </header>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs pour séparer les utilisateurs de l'entreprise et les partenaires -->
            <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="company-users-tab" data-bs-toggle="tab" data-bs-target="#company-users" type="button" role="tab" aria-controls="company-users" aria-selected="true">
                        Utilisateurs de l'entreprise
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="partner-users-tab" data-bs-toggle="tab" data-bs-target="#partner-users" type="button" role="tab" aria-controls="partner-users" aria-selected="false">
                        Utilisateurs partenaires
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="userTabsContent">
                <!-- Utilisateurs de l'entreprise -->
                <div class="tab-pane fade show active" id="company-users" role="tabpanel" aria-labelledby="company-users-tab">
                    <div class="futuristic-panel">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover futuristic-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $u): ?>
                                        <tr>
                                            <td><?php echo $u['id']; ?></td>
                                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getRoleBadgeClass($u['role']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($u['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $u['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $u['id']; ?>">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal pour voir les détails de l'utilisateur -->
                                                <div class="modal fade" id="viewUserModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content futuristic-modal">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Détails de l'utilisateur</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="user-details">
                                                                    <div class="text-center mb-4">
                                                                        <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                                                                        <h3><?php echo htmlspecialchars($u['username']); ?></h3>
                                                                        <span class="badge bg-<?php echo getRoleBadgeClass($u['role']); ?> mb-2">
                                                                            <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                                                        </span>
                                                                        <p><?php echo htmlspecialchars($u['email']); ?></p>
                                                                    </div>
                                                                    
                                                                    <ul class="list-group">
                                                                        <li class="list-group-item bg-dark text-light">
                                                                            <strong>ID:</strong> <?php echo $u['id']; ?>
                                                                        </li>
                                                                        <li class="list-group-item bg-dark text-light">
                                                                            <strong>Date de création:</strong> <?php echo formatDate($u['created_at']); ?>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="futuristic-button sm" data-bs-dismiss="modal">Fermer</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Modal pour modifier le rôle -->
                                                <div class="modal fade" id="editRoleModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content futuristic-modal">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Modifier le rôle</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form action="users.php" method="POST" class="edit-form">
                                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="role<?php echo $u['id']; ?>" class="form-label">Rôle</label>
                                                                        <select id="role<?php echo $u['id']; ?>" name="role" class="futuristic-select">
                                                                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                                                            <option value="manager" <?php echo $u['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="d-grid">
                                                                        <button type="submit" name="change_role" class="futuristic-button">Enregistrer</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Utilisateurs partenaires -->
                <div class="tab-pane fade" id="partner-users" role="tabpanel" aria-labelledby="partner-users-tab">
                    <div class="futuristic-panel">
                        <?php if ($apiStatus && !empty($partnerUsers)): ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover futuristic-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom d'utilisateur</th>
                                            <th>Email</th>
                                            <th>Entreprise</th>
                                            <th>Rôle</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($partnerUsers as $u): ?>
                                            <tr>
                                                <td><?php echo $u['id']; ?></td>
                                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                                <td><?php echo htmlspecialchars($u['company_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getRoleBadgeClass($u['role']); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewPartnerModal<?php echo $u['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Modal pour voir les détails de l'utilisateur partenaire -->
                                                    <div class="modal fade" id="viewPartnerModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content futuristic-modal">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Détails du partenaire</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="user-details">
                                                                        <div class="text-center mb-4">
                                                                            <i class="bi bi-building" style="font-size: 5rem;"></i>
                                                                            <h3><?php echo htmlspecialchars($u['username']); ?></h3>
                                                                            <div class="badge bg-info mb-2">Partenaire</div>
                                                                            <span class="badge bg-<?php echo getRoleBadgeClass($u['role']); ?> mb-2">
                                                                                <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                                                            </span>
                                                                            <p><?php echo htmlspecialchars($u['email']); ?></p>
                                                                        </div>
                                                                        
                                                                        <ul class="list-group">
                                                                            <li class="list-group-item bg-dark text-light">
                                                                                <strong>ID:</strong> <?php echo $u['id']; ?>
                                                                            </li>
                                                                            <li class="list-group-item bg-dark text-light">
                                                                                <strong>Entreprise:</strong> <?php echo htmlspecialchars($u['company_name'] ?? 'N/A'); ?>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="futuristic-button sm" data-bs-dismiss="modal">Fermer</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif (!$apiStatus): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                L'API n'est pas accessible. Impossible de récupérer les informations des partenaires.
                            </div>
                            <p class="text-light">Pour afficher les partenaires, assurez-vous que l'API Python est en cours d'exécution.</p>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Aucun utilisateur partenaire trouvé.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal pour ajouter un utilisateur -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content futuristic-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_user.php" method="POST" class="edit-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <div class="futuristic-input-group">
                                <input type="text" id="username" name="username" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="futuristic-input-group">
                                <input type="email" id="email" name="email" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="futuristic-input-group">
                                <input type="password" id="password" name="password" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select id="role" name="role" class="futuristic-select">
                                <option value="user">Utilisateur</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="futuristic-button">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
