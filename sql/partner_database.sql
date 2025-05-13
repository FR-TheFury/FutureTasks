
-- Base de données pour l'entreprise partenaire

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS partner_tasks;
USE partner_tasks;

-- Table des utilisateurs partenaires
CREATE TABLE IF NOT EXISTS partner_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'manager', 'admin') NOT NULL DEFAULT 'user',
  company_name VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des tâches
CREATE TABLE IF NOT EXISTS partner_tasks (
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
  FOREIGN KEY (assigned_to) REFERENCES partner_users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES partner_users(id) ON DELETE CASCADE
);

-- Insertion d'un administrateur par défaut
-- Mot de passe: partner123 (hashé)
INSERT INTO partner_users (username, email, password, role, company_name) VALUES
('PartnerAdmin', 'admin@partner.com', '$2y$10$vcDYpX6LQnEsE9rgKIjgcObRNm09hed5BbRZBHP26nfM8a/5ScKHe', 'admin', 'Partner Company');

-- Insertion d'un utilisateur standard par défaut
-- Mot de passe: user123 (hashé)
INSERT INTO partner_users (username, email, password, role, company_name) VALUES
('PartnerUser', 'user@partner.com', '$2y$10$K7f.A3BKDuLJGgpX.UDa0OEjTPBMNDKeo3G2BWvFkKQfOaLtcgg3m', 'user', 'Partner Company');
