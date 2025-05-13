
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');

// Vérifier si l'utilisateur est connecté et est admin ou manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de la tâche est présent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    
    // Vérifier si l'ID est valide
    if ($task_id) {
        // Supprimer d'abord les commentaires associés à la tâche
        $stmt = $pdo->prepare("DELETE FROM task_comments WHERE task_id = ?");
        $stmt->execute([$task_id]);
        
        // Supprimer la tâche
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        if ($stmt->execute([$task_id])) {
            header('Location: tasks.php?deleted=1');
            exit;
        } else {
            header('Location: tasks.php?error=delete_failed');
            exit;
        }
    }
}

// Redirection par défaut
header('Location: tasks.php');
exit;
?>
