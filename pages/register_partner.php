
<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/partner_config.php');
require_once('../includes/functions.php');
require_once('../includes/api_functions.php');

// Si l'utilisateur est déjà connecté, redirection vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Traitement de l'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = htmlspecialchars(trim($_POST['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $contact_name = htmlspecialchars(trim($_POST['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation de base
    if (empty($company_name) || empty($contact_name) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit comporter au moins 6 caractères.';
    } else {
        // Préparation des données pour l'API
        $partnerData = [
            'company_name' => $company_name,
            'contact_name' => $contact_name,
            'email' => $email,
            'password' => $password
        ];
        
        // Appel à l'API pour créer un nouveau partenaire
        $response = registerPartner($partnerData);
        
        if ($response['success']) {
            $success = 'Votre compte partenaire a été créé avec succès. Vous pouvez maintenant vous connecter.';
            // Redirection vers la page de connexion après 2 secondes
            header("Refresh: 2; URL=login.php");
        } else {
            $error = $response['message'] ?? 'Une erreur est survenue lors de la création du compte.';
        }
    }
}

$pageTitle = 'Inscription Partenaire';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - FutureTasks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <!-- Background Three.js -->
    <div id="bg-animation" class="bg-intense"></div>
    
    <div class="container">
        <div class="auth-container">
            <div class="logo-container">
                <div class="app-logo">
                    <div class="logo-icon"></div>
                    <h1>FUTURETASKS</h1>
                </div>
                <p class="tagline">Rejoignez notre réseau de partenaires</p>
            </div>
            
            <div class="auth-box">
                <h2>Inscription Partenaire</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="login.php" class="futuristic-button">Aller à la page de connexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="register_partner.php" method="post">
                        <div class="form-group">
                            <label for="company_name" class="form-label">Nom de l'entreprise</label>
                            <div class="futuristic-input-group">
                                <input type="text" id="company_name" name="company_name" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_name" class="form-label">Nom du contact</label>
                            <div class="futuristic-input-group">
                                <input type="text" id="contact_name" name="contact_name" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email professionnel</label>
                            <div class="futuristic-input-group">
                                <input type="email" id="email" name="email" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="futuristic-input-group">
                                <input type="password" id="password" name="password" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                            <small class="text-muted">Au moins 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <div class="futuristic-input-group">
                                <input type="password" id="confirm_password" name="confirm_password" class="futuristic-input" required>
                                <div class="input-line"></div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="futuristic-button w-100">CRÉER UN COMPTE PARTENAIRE</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="register-link">Déjà partenaire? Connectez-vous ici</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
</body>
</html>
