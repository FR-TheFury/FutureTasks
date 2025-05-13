
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/partner_config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Vérifier si l'utilisateur est connecté et a les droits nécessaires
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$is_partner = isset($_SESSION['is_partner']) && $_SESSION['is_partner'];

// Vérifier si l'ID de la tâche est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tasks.php');
    exit;
}

$task_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$taskSource = isset($_GET['source']) ? $_GET['source'] : 'company';

// Vérifier si l'ID est valide
if (!$task_id) {
    header('Location: tasks.php');
    exit;
}

// Vérifier si l'utilisateur a le droit de modifier cette tâche
$canEdit = false;

if ($is_partner && $taskSource === 'partner') {
    // Les partenaires peuvent modifier leurs propres tâches
    $canEdit = true;
} elseif (!$is_partner && $taskSource === 'company') {
    // Vérifier les droits dans la base de données locale
    $canEdit = canUserEditTask($pdo, $user_id, $role, $task_id);
} elseif (!$is_partner && $role === 'admin') {
    // L'admin local peut tout modifier
    $canEdit = true;
}

if (!$canEdit) {
    header('Location: tasks.php');
    exit;
}

// Récupérer les détails de la tâche selon la source
if ($taskSource === 'partner') {
    // Récupérer depuis l'API
    $apiResponse = callApi('/tasks/' . $task_id . '?db_type=partner');
    if (!$apiResponse['success'] || !isset($apiResponse['data']['task'])) {
        header('Location: tasks.php');
        exit;
    }
    $task = $apiResponse['data']['task'];
} else {
    // Récupérer depuis la base de données locale
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    // Vérifier si la tâche existe
    if (!$task) {
        header('Location: tasks.php');
        exit;
    }
}

$error = '';
$success = '';

// Récupérer la liste des utilisateurs disponibles pour l'assignation
$users = getAllUsersForAssignment();

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $assigned_to = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
    $priority = htmlspecialchars(trim($_POST['priority'] ?? ''), ENT_QUOTES, 'UTF-8');
    $due_date = htmlspecialchars(trim($_POST['due_date'] ?? ''), ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars(trim($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (empty($title) || empty($assigned_to) || empty($priority) || empty($due_date) || empty($status)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } else {
        if ($taskSource === 'partner') {
            // Mettre à jour via l'API
            $taskData = [
                'title' => $title,
                'description' => $description,
                'assigned_to' => $assigned_to,
                'priority' => $priority,
                'due_date' => $due_date,
                'status' => $status
            ];
            
            $updateResponse = updateApiTask($task_id, $taskData, 'partner');
            
            if ($updateResponse['success']) {
                $success = 'Tâche mise à jour avec succès';
                // Redirection vers la page de détail après 2 secondes
                header("Refresh: 2; URL=task_detail.php?id=$task_id&source=partner");
            } else {
                $error = 'Une erreur est survenue lors de la mise à jour de la tâche via l\'API';
            }
        } else {
            // Mettre à jour dans la base de données locale
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET title = ?, 
                    description = ?, 
                    assigned_to = ?, 
                    priority = ?, 
                    due_date = ?, 
                    status = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $description, $assigned_to, $priority, $due_date, $status, $task_id])) {
                $success = 'Tâche mise à jour avec succès';
                
                // Synchroniser avec l'API
                syncTasks('company');
                
                // Redirection vers la page de détail après 2 secondes
                header("Refresh: 2; URL=task_detail.php?id=$task_id&source=company");
            } else {
                $error = 'Une erreur est survenue lors de la mise à jour de la tâche';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la tâche - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* Style spécifique pour cette page pour assurer que le formulaire soit au premier plan */
        .futuristic-panel {
            overflow: visible !important;
            position: relative;
            z-index: 100;
        }
        .futuristic-form {
            position: relative;
            z-index: 10;
        }
        .main-content {
            position: relative;
            z-index: 10;
        }
        #bg-animation {
            z-index: 1;
        }
        .dashboard-container {
            position: relative;
            z-index: 5;
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
                <h1>Modifier la tâche <?php echo $taskSource === 'partner' ? '(Partenaire)' : ''; ?></h1>
                <div class="d-flex">
                    <a href="task_detail.php?id=<?php echo $task_id; ?>&source=<?php echo $taskSource; ?>" class="futuristic-button outline me-2">
                        <i class="bi bi-eye"></i> Voir détails
                    </a>
                    <a href="tasks.php" class="futuristic-button outline">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
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
            
            <div class="futuristic-panel">
                <form action="edit_task.php?id=<?php echo $task_id; ?>&source=<?php echo $taskSource; ?>" method="POST" class="task-form futuristic-form">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="title" class="form-label">Titre *</label>
                            <div class="futuristic-input-group">
                                <input type="text" id="title" name="title" class="futuristic-input" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="assigned_to" class="form-label">Assignée à *</label>
                            <select id="assigned_to" name="assigned_to" class="futuristic-select" required>
                                <option value="">Sélectionner un utilisateur</option>
                                <?php foreach($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $task['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if (isset($user['user_type']) && $user['user_type'] === 'partner'): ?>
                                            (Partenaire)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label for="priority" class="form-label">Priorité *</label>
                            <select id="priority" name="priority" class="futuristic-select" required>
                                <option value="">Sélectionner une priorité</option>
                                <option value="basse" <?php echo $task['priority'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                <option value="moyenne" <?php echo $task['priority'] === 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                                <option value="haute" <?php echo $task['priority'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                <option value="urgente" <?php echo $task['priority'] === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <label for="status" class="form-label">Statut *</label>
                            <select id="status" name="status" class="futuristic-select" required>
                                <option value="à faire" <?php echo $task['status'] === 'à faire' ? 'selected' : ''; ?>>À faire</option>
                                <option value="en cours" <?php echo $task['status'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                                <option value="terminé" <?php echo $task['status'] === 'terminé' ? 'selected' : ''; ?>>Terminé</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <label for="due_date" class="form-label">Date d'échéance *</label>
                            <div class="futuristic-input-group">
                                <input type="date" id="due_date" name="due_date" class="futuristic-input" value="<?php echo $task['due_date']; ?>" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <div class="futuristic-input-group">
                            <textarea id="description" name="description" class="futuristic-textarea" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
                            <div class="input-line"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="task_detail.php?id=<?php echo $task_id; ?>&source=<?php echo $taskSource; ?>" class="futuristic-button outline">Annuler</a>
                        <button type="submit" class="futuristic-button">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
