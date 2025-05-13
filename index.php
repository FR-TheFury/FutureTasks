
<?php
session_start();
// Redirection vers la page de connexion si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
} else {
    // Redirection vers le tableau de bord
    header('Location: pages/dashboard.php');
    exit;
}
?>
