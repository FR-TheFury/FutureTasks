
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Traitement du formulaire d'ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = htmlspecialchars(trim($_POST['role'] ?? 'user'), ENT_QUOTES, 'UTF-8');
    
    $error = '';
    
    // Validation de base
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit comporter au moins 6 caractères';
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Cet email est déjà utilisé';
        } else {
            // Hasher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Vérifier que le rôle est valide
            $valid_roles = ['user', 'manager', 'admin'];
            if (!in_array($role, $valid_roles)) {
                $role = 'user';  // Valeur par défaut
            }
            
            // Insérer l'utilisateur dans la base de données
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                // Redirection avec message de succès
                header('Location: users.php?added=1');
                exit;
            } else {
                $error = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
    
    // En cas d'erreur, rediriger vers la page utilisateurs avec le message d'erreur
    if ($error) {
        header('Location: users.php?error=' . urlencode($error));
        exit;
    }
} else {
    // Si ce n'est pas une méthode POST, rediriger vers la page utilisateurs
    header('Location: users.php');
    exit;
}
?>
