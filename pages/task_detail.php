
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

// Vérifier si l'ID de la tâche est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tasks.php');
    exit;
}

$task_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Vérifier si l'ID est valide
if (!$task_id) {
    header('Location: tasks.php');
    exit;
}

// Récupérer les détails de la tâche
$stmt = $pdo->prepare("
    SELECT t.*, 
           u1.username as assigned_to_name,
           u2.username as created_by_name
    FROM tasks t
    JOIN users u1 ON t.assigned_to = u1.id
    JOIN users u2 ON t.created_by = u2.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

// Vérifier si la tâche existe et si l'utilisateur a les droits pour la voir
if (!$task || (!canUserViewTask($pdo, $user_id, $role, $task_id))) {
    header('Location: tasks.php');
    exit;
}

// Traitement des commentaires si formulaire soumis
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_text = htmlspecialchars(trim($_POST['comment'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("
            INSERT INTO task_comments (task_id, user_id, comment, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$task_id, $user_id, $comment_text])) {
            $comment_success = 'Commentaire ajouté avec succès.';
        } else {
            $comment_error = 'Erreur lors de l\'ajout du commentaire.';
        }
    } else {
        $comment_error = 'Le commentaire ne peut pas être vide.';
    }
}

// Récupérer les commentaires pour cette tâche
$stmt = $pdo->prepare("
    SELECT tc.*, u.username
    FROM task_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.task_id = ?
    ORDER BY tc.created_at DESC
");
$stmt->execute([$task_id]);
$comments = $stmt->fetchAll();

// Fonction pour vérifier si un utilisateur peut voir une tâche
function canUserViewTask($pdo, $user_id, $role, $task_id) {
    if ($role === 'admin') {
        return true;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        return false;
    }
    
    if ($role === 'manager') {
        return true;  // Les managers peuvent voir toutes les tâches
    }
    
    return $task['assigned_to'] === $user_id;  // Les utilisateurs ne peuvent voir que leurs propres tâches
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail de la tâche - FutureTasks</title>
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
                <div class="d-flex align-items-center">
                    <a href="tasks.php" class="btn btn-sm btn-outline-light me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h1><?php echo htmlspecialchars($task['title']); ?></h1>
                </div>
                
                <?php if (canUserEditTask($pdo, $user_id, $role, $task_id)): ?>
                <div class="d-flex">
                    <button type="button" class="futuristic-button sm me-2" data-bs-toggle="modal" data-bs-target="#statusModal">
                        <i class="bi bi-pencil-square"></i> Modifier le statut
                    </button>
                    
                    <?php if ($role === 'admin' || $role === 'manager'): ?>
                        <a href="edit_task.php?id=<?php echo $task_id; ?>" class="futuristic-button sm">
                            <i class="bi bi-gear"></i> Modifier
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </header>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="futuristic-panel mb-4">
                        <h2 class="mb-3">Détails</h2>
                        
                        <div class="task-priority mb-3">
                            <span class="badge bg-<?php echo getPriorityClass($task['priority']); ?> fs-6">
                                <?php echo htmlspecialchars(ucfirst($task['priority'])); ?>
                            </span>
                            <span class="badge bg-<?php echo getStatusClass($task['status']); ?> ms-2 fs-6">
                                <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="task-description mb-4">
                            <h3>Description</h3>
                            <div class="bg-dark p-3 rounded">
                                <?php if (!empty($task['description'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Aucune description fournie.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="task-comments mt-5">
                            <h3>Commentaires</h3>
                            
                            <?php if ($comment_error): ?>
                                <div class="alert alert-danger"><?php echo $comment_error; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($comment_success): ?>
                                <div class="alert alert-success"><?php echo $comment_success; ?></div>
                            <?php endif; ?>
                            
                            <form action="task_detail.php?id=<?php echo $task_id; ?>" method="POST" class="mb-4">
                                <div class="mb-3">
                                    <div class="futuristic-input-group">
                                        <textarea name="comment" class="futuristic-textarea" rows="3" placeholder="Ajouter un commentaire..."></textarea>
                                        <div class="input-line"></div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="futuristic-button sm">Ajouter</button>
                                </div>
                            </form>
                            
                            <div class="comments-list">
                                <?php if (count($comments) > 0): ?>
                                    <?php foreach($comments as $comment): ?>
                                        <div class="comment-item">
                                            <div class="comment-header">
                                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                            </div>
                                            <div class="comment-content">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <p>Aucun commentaire pour le moment.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="futuristic-panel mb-4">
                        <h3>Informations</h3>
                        <ul class="task-info-list">
                            <li>
                                <span class="info-label">Assignée à</span>
                                <span class="info-value"><?php echo htmlspecialchars($task['assigned_to_name']); ?></span>
                            </li>
                            <li>
                                <span class="info-label">Créée par</span>
                                <span class="info-value"><?php echo htmlspecialchars($task['created_by_name']); ?></span>
                            </li>
                            <li>
                                <span class="info-label">Date d'échéance</span>
                                <span class="info-value"><?php echo formatDate($task['due_date']); ?></span>
                            </li>
                            <li>
                                <span class="info-label">Date de création</span>
                                <span class="info-value"><?php echo formatDate($task['created_at']); ?></span>
                            </li>
                            <?php if ($task['updated_at']): ?>
                            <li>
                                <span class="info-label">Dernière mise à jour</span>
                                <span class="info-value"><?php echo formatDate($task['updated_at']); ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if (canUserEditTask($pdo, $user_id, $role, $task_id)): ?>
                    <div class="futuristic-panel">
                        <h3>Actions rapides</h3>
                        <div class="d-grid gap-2">
                            <form action="tasks.php" method="POST">
                                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                <div class="d-grid gap-2">
                                    <?php if ($task['status'] !== 'à faire'): ?>
                                        <button type="submit" name="status" value="à faire" class="futuristic-button sm outline w-100">
                                            Marquer comme à faire
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['status'] !== 'en cours'): ?>
                                        <button type="submit" name="status" value="en cours" class="futuristic-button sm outline w-100">
                                            Marquer comme en cours
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['status'] !== 'terminé'): ?>
                                        <button type="submit" name="status" value="terminé" class="futuristic-button sm outline w-100">
                                            Marquer comme terminé
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                            
                            <?php if ($role === 'admin'): ?>
                                <button type="button" class="futuristic-button sm danger w-100 mt-3" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal pour changer le statut -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content futuristic-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le statut</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="tasks.php" method="POST">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <div class="mb-3">
                            <label for="status" class="form-label">Nouveau statut</label>
                            <select id="status" name="status" class="futuristic-select">
                                <option value="à faire" <?php echo $task['status'] === 'à faire' ? 'selected' : ''; ?>>À faire</option>
                                <option value="en cours" <?php echo $task['status'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                                <option value="terminé" <?php echo $task['status'] === 'terminé' ? 'selected' : ''; ?>>Terminé</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="futuristic-button">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal pour supprimer la tâche (admin seulement) -->
    <?php if ($role === 'admin'): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content futuristic-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cette tâche? Cette action est irréversible.</p>
                    <form action="delete_task.php" method="POST">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="futuristic-button outline" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="futuristic-button danger">Supprimer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
