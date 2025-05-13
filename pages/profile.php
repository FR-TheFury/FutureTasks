
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
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Vérification du mot de passe actuel
    if (!empty($current_password)) {
        if (password_verify($current_password, $user['password'])) {
            // Mise à jour des données de base
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
            
            // Mise à jour du mot de passe si fourni
            if (!empty($new_password) && !empty($confirm_password)) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        $success = 'Profil mis à jour avec succès, y compris le mot de passe.';
                    } else {
                        $error = 'Le nouveau mot de passe doit comporter au moins 6 caractères.';
                    }
                } else {
                    $error = 'Les nouveaux mots de passe ne correspondent pas.';
                }
            } else {
                $success = 'Profil mis à jour avec succès.';
            }
            
            // Mettre à jour les données de session
            $_SESSION['username'] = $username;
            
            // Rafraîchir les données utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = 'Mot de passe actuel incorrect.';
        }
    } else {
        $error = 'Veuillez entrer votre mot de passe actuel pour confirmer les modifications.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - FutureTasks</title>
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
                <h1>Mon Profil</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role badge bg-primary"><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></span>
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
            
            <div class="row">
                <div class="col-md-8">
                    <div class="futuristic-panel">
                        <h2 class="mb-4">Informations personnelles</h2>
                        <form action="profile.php" method="POST" class="profile-form futuristic-form">
                            <div class="mb-4">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <div class="futuristic-input-group">
                                    <input type="text" id="username" name="username" class="futuristic-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    <div class="input-line"></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <div class="futuristic-input-group">
                                    <input type="email" id="email" name="email" class="futuristic-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="input-line"></div>
                                </div>
                            </div>
                            
                            <h3 class="mt-5 mb-4">Changer le mot de passe</h3>
                            <div class="mb-4">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <div class="futuristic-input-group">
                                    <input type="password" id="current_password" name="current_password" class="futuristic-input">
                                    <div class="input-line"></div>
                                </div>
                                <small class="text-light opacity-75">Nécessaire pour confirmer les modifications</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <div class="futuristic-input-group">
                                    <input type="password" id="new_password" name="new_password" class="futuristic-input">
                                    <div class="input-line"></div>
                                </div>
                                <small class="text-light opacity-75">Laissez vide pour conserver le mot de passe actuel</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <div class="futuristic-input-group">
                                    <input type="password" id="confirm_password" name="confirm_password" class="futuristic-input">
                                    <div class="input-line"></div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="futuristic-button">Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="futuristic-panel">
                        <div class="user-profile-summary text-center">
                            <div class="user-avatar">
                                <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                            </div>
                            <h3 class="mt-3"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <span class="badge bg-primary mb-3"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                            <p class="text-light opacity-75">Membre depuis: <?php echo formatDate($user['created_at']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
