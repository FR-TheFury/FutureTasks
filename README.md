
# FutureTasks - Système à double base de données

Ce projet est un système de gestion de tâches qui utilise deux bases de données séparées : une pour votre entreprise et une pour votre partenaire, avec une API Python qui facilite la communication entre les deux.

## Configuration requise

- Serveur web avec PHP 7.4+
- MySQL ou MariaDB
- Python 3.7+ avec pip
- Modules Python : Flask, mysql-connector-python

## Installation

### 1. Configuration des bases de données

1. Créez la base de données principale:
   ```
   mysql -u root -p < sql/database.sql
   ```

2. Créez la base de données partenaire:
   ```
   mysql -u root -p < sql/partner_database.sql
   ```

3. Modifiez les fichiers de configuration selon vos paramètres:
   - `includes/config.php` pour la base de données principale
   - `includes/partner_config.php` pour la base de données partenaire

### 2. Configuration de l'API Python

1. Installez les dépendances Python:
   ```
   cd api
   pip install -r requirements.txt
   ```

2. Démarrez l'API (en production, utilisez un serveur WSGI comme Gunicorn):
   ```
   python api.py
   ```

3. L'API sera accessible à l'adresse: http://localhost:5000

### 3. Configuration du site web

1. Placez les fichiers dans votre répertoire web:
   - Pour XAMPP : `C:\xampp\htdocs\futuretasks`
   - Pour WAMP : `C:\wamp\www\futuretasks`
   - Pour MAMP : `/Applications/MAMP/htdocs/futuretasks`

2. Accédez au site via http://localhost/futuretasks

## Utilisation

1. Connectez-vous avec les identifiants par défaut:
   - Admin: admin@futuretasks.com / admin123
   - Manager: manager@futuretasks.com / manager123
   - Utilisateur: user@futuretasks.com / user123

2. Accédez à la page "Synchronisation des données" pour gérer la synchronisation entre les deux bases de données.

## Structure des fichiers

- `api/` - Contient l'API Python
- `includes/` - Fichiers PHP inclus (configuration, fonctions)
- `pages/` - Pages de l'application
- `sql/` - Scripts SQL pour la création des bases de données

## Sécurité

Notes importantes sur la sécurité:
- Le token API utilisé dans ce code est destiné aux tests uniquement. En production, utilisez un système d'authentification plus sécurisé.
- Protégez les informations de connexion à la base de données.
- Utilisez HTTPS en production pour sécuriser la communication entre le site et l'API.
