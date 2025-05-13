
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/functions.php');

$error = '';
$success = '';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Utilisation de htmlspecialchars au lieu de FILTER_SANITIZE_STRING
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = isset($_POST['role']) ? htmlspecialchars(trim($_POST['role'] ?? ''), ENT_QUOTES, 'UTF-8') : 'user';
    
    // Validation de base
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
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
            
            // Insérer l'utilisateur dans la base de données
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                $success = 'Compte créé avec succès. Vous pouvez maintenant vous connecter.';
                
                // Redirection vers la page de connexion après 2 secondes
                header("Refresh: 2; URL=login.php");
            } else {
                $error = 'Une erreur est survenue. Veuillez réessayer.';
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
    <title>Inscription - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <!-- Background Three.js -->
    <div id="bg-animation"></div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="register-container futuristic-panel">
                    <div class="text-center mb-4">
                        <h1 class="futuristic-title">Inscription</h1>
                        <p class="text-light">Créer un nouveau compte</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger animate__animated animate__fadeIn">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success animate__animated animate__fadeIn">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <div class="futuristic-input-group">
                                <input type="text" id="username" name="username" class="futuristic-input" placeholder="Nom d'utilisateur" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="futuristic-input-group">
                                <input type="email" id="email" name="email" class="futuristic-input" placeholder="Email" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="futuristic-input-group">
                                <input type="password" id="password" name="password" class="futuristic-input" placeholder="Mot de passe" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="futuristic-input-group">
                                <input type="password" id="confirm_password" name="confirm_password" class="futuristic-input" placeholder="Confirmer le mot de passe" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="text-light mb-2">Rôle</label>
                            <select name="role" class="futuristic-select">
                                <option value="user">Utilisateur</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="futuristic-button">S'inscrire</button>
                            <a href="login.php" class="futuristic-button outline">Déjà inscrit? Connectez-vous</a>
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
