
<aside class="sidebar">
    <div class="sidebar-header">
        <h2 class="app-title">FutureTasks</h2>
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-house-door"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <li>
                <a href="tasks.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>">
                    <i class="bi bi-list-task"></i>
                    <span>Tâches</span>
                </a>
            </li>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                <li>
                    <a href="add_task.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'add_task.php' ? 'active' : ''; ?>">
                        <i class="bi bi-plus-circle"></i>
                        <span>Nouvelle tâche</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span>Utilisateurs</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i>
                    <span>Mon profil</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Déconnexion</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
