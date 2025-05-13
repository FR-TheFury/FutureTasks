
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

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifier si c'est une tentative de connexion partenaire
        $isPartnerLogin = isset($_POST['partner_login']) && $_POST['partner_login'] === '1';
        
        if ($isPartnerLogin) {
            // Connexion via l'API pour les partenaires
            $credentials = [
                'email' => $email,
                'password' => $password
            ];
            
            $response = loginPartner($credentials);
            
            if ($response['success'] && isset($response['data']['token'])) {
                // Stocker les informations de session pour le partenaire
                $_SESSION['user_id'] = $response['data']['user']['id'];
                $_SESSION['username'] = $response['data']['user']['username'];
                $_SESSION['email'] = $response['data']['user']['email'];
                $_SESSION['role'] = $response['data']['user']['role'];
                $_SESSION['api_token'] = $response['data']['token'];
                $_SESSION['account_type'] = 'partner';
                $_SESSION['company_name'] = $response['data']['user']['company_name'];
                
                // Rediriger vers le tableau de bord
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect pour le compte partenaire.';
            }
        } else {
            // Connexion locale pour les utilisateurs de l'entreprise
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Stocker les informations de session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['account_type'] = 'company';
                
                // Rediriger vers le tableau de bord
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }
    }
}

$pageTitle = 'Connexion';
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
                <p class="tagline">Système de gestion des tâches</p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="auth-tabs">
                        <button class="auth-tab active" data-tab="login">Entreprise</button>
                        <button class="auth-tab" data-tab="partner">Partenaire</button>
                    </div>
                    
                    <div class="auth-box">
                        <div class="auth-content" id="login-content">
                            <h2>Connexion</h2>
                            
                            <?php if ($error && !isset($_POST['partner_login'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="login.php" method="post">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
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
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="futuristic-button w-100">CONNEXION ENTREPRISE</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="auth-content hidden" id="partner-content">
                            <h2>Connexion Partenaire</h2>
                            
                            <?php if ($error && isset($_POST['partner_login'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="login.php" method="post">
                                <input type="hidden" name="partner_login" value="1">
                                
                                <div class="form-group">
                                    <label for="partner_email" class="form-label">Email Partenaire</label>
                                    <div class="futuristic-input-group">
                                        <input type="email" id="partner_email" name="email" class="futuristic-input" required>
                                        <div class="input-line"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="partner_password" class="form-label">Mot de passe</label>
                                    <div class="futuristic-input-group">
                                        <input type="password" id="partner_password" name="password" class="futuristic-input" required>
                                        <div class="input-line"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="futuristic-button w-100">CONNEXION PARTENAIRE</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="auth-box">
                        <h2>Devenir Partenaire</h2>
                        <form action="register_partner.php" method="post">
                            <div class="form-group">
                                <label for="company_name" class="form-label">Nom de votre entreprise</label>
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
                                    <input type="email" id="register_email" name="email" class="futuristic-input" required>
                                    <div class="input-line"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="futuristic-input-group">
                                    <input type="password" id="register_password" name="password" class="futuristic-input" required>
                                    <div class="input-line"></div>
                                </div>
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
                            
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    En créant un compte, vous acceptez nos conditions d'utilisation et notre politique de confidentialité
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="demo-accounts">
                <h3>Comptes de démonstration:</h3>
                <div class="account-list">
                    <div class="demo-account">
                        <span class="badge admin">Admin</span>
                        <div>Admin@gmail.com / Test.1234</div>
                    </div>
                    <div class="demo-account">
                        <span class="badge manager">Manager</span>
                        <div>Manager@gmail.com / Test.1234</div>
                    </div>
                    <div class="demo-account">
                        <span class="badge user">Utilisateur</span>
                        <div>User@gmail.com / Test.1234</div>
                    </div>
                    <div class="demo-account">
                        <span class="badge user">Partenaire</span>
                        <div>Partenaire@gmail.com / Test.1234</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../assets/js/background.js"></script>
    <script>
        // Gestion des onglets
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.auth-tab');
            const contents = document.querySelectorAll('.auth-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-tab');
                    
                    // Désactiver tous les onglets et contenu
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.add('hidden'));
                    
                    // Activer l'onglet cliqué et son contenu
                    this.classList.add('active');
                    document.getElementById(targetId + '-content').classList.remove('hidden');
                });
            });
        });
    </script>
</body>
</html>
