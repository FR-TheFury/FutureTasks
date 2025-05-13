
<?php
// Configuration de la base de données
$db_host = 'localhost';
$db_name = 'futuretasks';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Constantes de l'application
define('SITE_NAME', 'FutureTasks');
define('APP_VERSION', '1.0.0');

// Accès selon les rôles
define('ROLE_PERMISSIONS', [
    'user' => ['view_own_tasks', 'update_own_task_status'],
    'manager' => ['view_own_tasks', 'view_team_tasks', 'create_task', 'update_task', 'assign_task'],
    'admin' => ['view_all_tasks', 'create_task', 'update_task', 'delete_task', 'manage_users', 'assign_task']
]);
