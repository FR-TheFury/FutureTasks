
-- Base de données pour FutureTasks

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS futuretasks;
USE futuretasks;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'manager', 'admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des tâches
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  assigned_to INT NOT NULL,
  created_by INT NOT NULL,
  priority ENUM('basse', 'moyenne', 'haute', 'urgente') NOT NULL DEFAULT 'moyenne',
  status ENUM('à faire', 'en cours', 'terminé') NOT NULL DEFAULT 'à faire',
  due_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des commentaires de tâches
CREATE TABLE IF NOT EXISTS task_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion d'un administrateur par défaut
-- Mot de passe: admin123 (hashé)
INSERT INTO users (username, email, password, role) VALUES
('Admin', 'admin@futuretasks.com', '$2y$10$vcDYpX6LQnEsE9rgKIjgcObRNm09hed5BbRZBHP26nfM8a/5ScKHe', 'admin');

-- Insertion d'un manager par défaut
-- Mot de passe: manager123 (hashé)
INSERT INTO users (username, email, password, role) VALUES
('Manager', 'manager@futuretasks.com', '$2y$10$gRXNWWs8j3VoZFOIQRhdCeuY9YpBe3MxU1D.0I5LOlP7xRwwy9rUy', 'manager');

-- Insertion d'un utilisateur standard par défaut
-- Mot de passe: user123 (hashé)
INSERT INTO users (username, email, password, role) VALUES
('User', 'user@futuretasks.com', '$2y$10$K7f.A3BKDuLJGgpX.UDa0OEjTPBMNDKeo3G2BWvFkKQfOaLtcgg3m', 'user');

-- Insertion de quelques tâches d'exemple
INSERT INTO tasks (title, description, assigned_to, created_by, priority, status, due_date) VALUES
('Configurer l\'environnement de développement', 'Installer et configurer tous les outils nécessaires pour le développement du projet.', 3, 1, 'haute', 'terminé', DATE_ADD(CURRENT_DATE, INTERVAL -5 DAY)),
('Concevoir la base de données', 'Créer le schéma de base de données pour le projet avec toutes les relations.', 2, 1, 'haute', 'terminé', DATE_ADD(CURRENT_DATE, INTERVAL -2 DAY)),
('Développer l\'interface utilisateur', 'Créer les maquettes et implémenter l\'interface utilisateur selon les spécifications.', 3, 2, 'moyenne', 'en cours', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)),
('Tester les fonctionnalités principales', 'Effectuer des tests sur toutes les fonctionnalités principales de l\'application.', 3, 2, 'moyenne', 'à faire', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY)),
('Déployer l\'application', 'Déployer l\'application sur le serveur de production.', 2, 1, 'urgente', 'à faire', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY));
