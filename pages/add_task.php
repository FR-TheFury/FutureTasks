
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Vérifier si l'utilisateur est connecté et a les droits nécessaires
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$error = '';
$success = '';

// Récupérer tous les utilisateurs disponibles pour l'assignation (entreprise et partenaires)
$users = getAllUsersForAssignment();

// Traitement du formulaire d'ajout de tâche
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Utilisation de htmlspecialchars au lieu de FILTER_SANITIZE_STRING
    $title = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $assigned_to = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
    $priority = htmlspecialchars(trim($_POST['priority'] ?? ''), ENT_QUOTES, 'UTF-8');
    $due_date = htmlspecialchars(trim($_POST['due_date'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (empty($title) || empty($assigned_to) || empty($priority) || empty($due_date)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } else {
        // Déterminer si l'utilisateur assigné est un partenaire
        $isPartnerUser = false;
        $assignedUserType = 'company';
        
        foreach ($users as $user) {
            if ($user['id'] == $assigned_to && isset($user['user_type']) && $user['user_type'] === 'partner') {
                $isPartnerUser = true;
                $assignedUserType = 'partner';
                break;
            }
        }
        
        if ($isPartnerUser) {
            // Créer la tâche dans la base de données partenaire via l'API
            $taskData = [
                'title' => $title,
                'description' => $description,
                'assigned_to' => $assigned_to,
                'priority' => $priority,
                'due_date' => $due_date,
                'status' => 'à faire',
                'created_by' => $user_id
            ];
            
            $response = createApiTask($taskData, 'partner');
            
            if ($response['success']) {
                $success = 'Tâche créée avec succès et assignée au partenaire';
                
                // Redirection vers la page des tâches après 2 secondes
                header("Refresh: 2; URL=tasks.php");
            } else {
                $error = 'Une erreur est survenue lors de la création de la tâche pour le partenaire: ' . ($response['error'] ?? 'Erreur inconnue');
            }
        } else {
            // Créer la tâche dans la base de données locale
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, priority, due_date, status, created_by, created_at) 
                                  VALUES (?, ?, ?, ?, ?, 'à faire', ?, NOW())");
            
            if ($stmt->execute([$title, $description, $assigned_to, $priority, $due_date, $user_id])) {
                $success = 'Tâche créée avec succès';
                
                // Synchroniser avec l'API
                syncTasks('company');
                
                // Redirection vers la page des tâches après 2 secondes
                header("Refresh: 2; URL=tasks.php");
            } else {
                $error = 'Une erreur est survenue lors de la création de la tâche';
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
    <title>Ajouter une tâche - FutureTasks</title>
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
                <h1>Ajouter une tâche</h1>
                <a href="tasks.php" class="futuristic-button outline">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
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
                <form action="add_task.php" method="POST" class="task-form">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="title" class="form-label">Titre *</label>
                            <div class="futuristic-input-group">
                                <input type="text" id="title" name="title" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="assigned_to" class="form-label">Assignée à *</label>
                            <select id="assigned_to" name="assigned_to" class="futuristic-select" required>
                                <option value="">Sélectionner un utilisateur</option>
                                
                                <!-- Utilisateurs de l'entreprise -->
                                <optgroup label="Utilisateurs de l'entreprise">
                                    <?php foreach($users as $user): ?>
                                        <?php if (!isset($user['user_type']) || $user['user_type'] === 'company'): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                
                                <!-- Utilisateurs partenaires -->
                                <optgroup label="Utilisateurs partenaires">
                                    <?php 
                                    $hasPartners = false;
                                    foreach($users as $user): 
                                        if (isset($user['user_type']) && $user['user_type'] === 'partner'):
                                            $hasPartners = true;
                                    ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['username']); ?> 
                                                <?php if (isset($user['company_name'])): ?>
                                                    (<?php echo htmlspecialchars($user['company_name']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if (!$hasPartners): ?>
                                        <option value="" disabled>Aucun partenaire disponible</option>
                                    <?php endif; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="priority" class="form-label">Priorité *</label>
                            <select id="priority" name="priority" class="futuristic-select" required>
                                <option value="">Sélectionner une priorité</option>
                                <option value="basse">Basse</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="haute">Haute</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="due_date" class="form-label">Date d'échéance *</label>
                            <div class="futuristic-input-group">
                                <input type="date" id="due_date" name="due_date" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <div class="futuristic-input-group">
                            <textarea id="description" name="description" class="futuristic-textarea" rows="4"></textarea>
                            <div class="input-line"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="tasks.php" class="futuristic-button outline">Annuler</a>
                        <button type="submit" class="futuristic-button">Créer la tâche</button>
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
