
from flask import Flask, request, jsonify, make_response
import mysql.connector
from mysql.connector import Error
import json
import os
from functools import wraps
import hashlib
import time
import uuid
import re

app = Flask(__name__)
app.config['JSON_SORT_KEYS'] = False
app.config['JSONIFY_PRETTYPRINT_REGULAR'] = True

# Configuration pour les deux bases de données
db_config = {
    'company': {
        'host': 'localhost',
        'database': 'futuretasks_final',
        'user': 'Projet',
        'password': 'Test.1234'
    },
    'partner': {
        'host': 'localhost',
        'database': 'futuretasks_partner_final',
        'user': 'Projet',
        'password': 'Test.1234'
    }
}

# Fonction pour se connecter à une base de données
def get_db_connection(db_type):
    try:
        connection = mysql.connector.connect(
            host=db_config[db_type]['host'],
            database=db_config[db_type]['database'],
            user=db_config[db_type]['user'],
            password=db_config[db_type]['password']
        )
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Erreur lors de la connexion à MySQL: {e}")
    return None

# Middleware pour l'authentification simple par token
def token_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        token = None
        if 'Authorization' in request.headers:
            auth_header = request.headers['Authorization']
            if auth_header.startswith('Bearer '):
                token = auth_header.split(' ')[1]
        elif 'x-api-token' in request.headers:
            token = request.headers['x-api-token']
        
        if not token:
            return jsonify({'message': 'Token manquant!'}), 401
        
        # En production, vous voudriez vérifier le token dans une base de données ou contre un secret
        valid_token = 'secure_api_token_for_testing'  # Token simple pour les tests
        
        # Pour l'instant, acceptons tous les tokens car nous n'avons pas de système de stockage de tokens
        # En production, cette vérification devrait être plus stricte
        if token != valid_token and not token.startswith('partner_'):
            return jsonify({'message': 'Token invalide!'}), 401
        
        return f(*args, **kwargs)
    
    return decorated

# Route pour CORS preflight requests
@app.route('/', defaults={'path': ''}, methods=['OPTIONS'])
@app.route('/<path:path>', methods=['OPTIONS'])
def handle_options(path):
    response = make_response()
    response.headers.add('Access-Control-Allow-Origin', '*')
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization,x-api-token')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
    return response

# Middleware CORS pour toutes les routes
@app.after_request
def after_request(response):
    response.headers.add('Access-Control-Allow-Origin', '*')
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization,x-api-token')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
    return response

# Route racine pour éviter l'erreur 404
@app.route('/', methods=['GET'])
def index():
    return jsonify({
        'message': 'Bienvenue sur l\'API FutureTasks',
        'version': '1.0',
        'endpoints': [
            '/api/test',
            '/api/users',
            '/api/tasks',
            '/api/tasks/create',
            '/api/tasks/update/{id}',
            '/api/tasks/delete/{id}',
            '/api/sync/users',
            '/api/sync/tasks',
            '/api/partner/register',
            '/api/partner/login',
            '/api/partner/stats'
        ]
    })

# Route pour tester l'API
@app.route('/api/test', methods=['GET'])
def test_api():
    return jsonify({'message': 'API Python connectée et fonctionnelle!', 'status': 'success'})

# Route pour obtenir les utilisateurs des deux bases de données
@app.route('/api/users', methods=['GET'])
@token_required
def get_users():
    db_type = request.args.get('db_type', 'company')
    if db_type not in ['company', 'partner']:
        return jsonify({'message': 'Type de base de données invalide!'}), 400
    
    connection = get_db_connection(db_type)
    if connection:
        try:
            cursor = connection.cursor(dictionary=True)
            table_prefix = "partner_" if db_type == "partner" else ""
            query = f"SELECT id, username, email, role, company_name FROM {table_prefix}users"
            cursor.execute(query)
            users = cursor.fetchall()
            cursor.close()
            connection.close()
            return jsonify({'users': users, 'source': db_type})
        except Error as e:
            return jsonify({'message': f'Erreur: {str(e)}'}), 500
    else:
        return jsonify({'message': 'Impossible de se connecter à la base de données!'}), 500

# Route pour obtenir les tâches des deux bases de données
@app.route('/api/tasks', methods=['GET'])
@token_required
def get_tasks():
    db_type = request.args.get('db_type', 'company')
    if db_type not in ['company', 'partner']:
        return jsonify({'message': 'Type de base de données invalide!'}), 400
    
    connection = get_db_connection(db_type)
    if connection:
        try:
            cursor = connection.cursor(dictionary=True)
            table_prefix = "partner_" if db_type == "partner" else ""
            query = f"""
                SELECT t.*, u.username as assigned_to_name 
                FROM {table_prefix}tasks t 
                JOIN {table_prefix}users u ON t.assigned_to = u.id
                ORDER BY t.due_date ASC
            """
            cursor.execute(query)
            tasks = cursor.fetchall()
            cursor.close()
            connection.close()
            
            # Convertir les dates en chaînes pour la sérialisation JSON
            for task in tasks:
                if 'due_date' in task and task['due_date']:
                    task['due_date'] = task['due_date'].strftime('%Y-%m-%d')
                if 'created_at' in task and task['created_at']:
                    task['created_at'] = task['created_at'].strftime('%Y-%m-%d %H:%M:%S')
                if 'updated_at' in task and task['updated_at']:
                    task['updated_at'] = task['updated_at'].strftime('%Y-%m-%d %H:%M:%S')
            
            return jsonify({'tasks': tasks, 'source': db_type})
        except Error as e:
            return jsonify({'message': f'Erreur: {str(e)}'}), 500
    else:
        return jsonify({'message': 'Impossible de se connecter à la base de données!'}), 500

# Route pour créer une nouvelle tâche
@app.route('/api/tasks/create', methods=['POST'])
@token_required
def create_task():
    db_type = request.args.get('db_type', 'company')
    if db_type not in ['company', 'partner']:
        return jsonify({'message': 'Type de base de données invalide!'}), 400
    
    data = request.get_json()
    if not data:
        return jsonify({'message': 'Aucune donnée reçue'}), 400
    
    required_fields = ['title', 'description', 'assigned_to', 'created_by', 'priority', 'status', 'due_date']
    for field in required_fields:
        if field not in data:
            return jsonify({'message': f'Le champ {field} est requis'}), 400
    
    connection = get_db_connection(db_type)
    if not connection:
        return jsonify({'message': 'Impossible de se connecter à la base de données!'}), 500
    
    try:
        cursor = connection.cursor()
        table_prefix = "partner_" if db_type == "partner" else ""
        
        query = f"""
            INSERT INTO {table_prefix}tasks 
            (title, description, assigned_to, created_by, priority, status, due_date, created_at) 
            VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
        """
        
        cursor.execute(query, (
            data['title'],
            data['description'],
            data['assigned_to'],
            data['created_by'],
            data['priority'],
            data['status'],
            data['due_date']
        ))
        
        connection.commit()
        task_id = cursor.lastrowid
        
        cursor.close()
        connection.close()
        
        return jsonify({
            'message': 'Tâche créée avec succès',
            'task_id': task_id,
            'status': 'success'
        }), 201
        
    except Error as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

# Route pour mettre à jour une tâche existante
@app.route('/api/tasks/update/<int:task_id>', methods=['POST'])
@token_required
def update_task(task_id):
    db_type = request.args.get('db_type', 'company')
    if db_type not in ['company', 'partner']:
        return jsonify({'message': 'Type de base de données invalide!'}), 400
    
    data = request.get_json()
    if not data:
        return jsonify({'message': 'Aucune donnée reçue'}), 400
    
    connection = get_db_connection(db_type)
    if not connection:
        return jsonify({'message': 'Impossible de se connecter à la base de données!'}), 500
    
    try:
        cursor = connection.cursor()
        table_prefix = "partner_" if db_type == "partner" else ""
        
        # Vérifier si la tâche existe
        cursor.execute(f"SELECT id FROM {table_prefix}tasks WHERE id = %s", (task_id,))
        if cursor.fetchone() is None:
            cursor.close()
            connection.close()
            return jsonify({'message': 'Tâche non trouvée'}), 404
        
        # Construire la requête de mise à jour dynamiquement
        update_fields = []
        params = []
        
        for field in ['title', 'description', 'assigned_to', 'priority', 'status', 'due_date']:
            if field in data:
                update_fields.append(f"{field} = %s")
                params.append(data[field])
        
        # Ajouter updated_at
        update_fields.append("updated_at = NOW()")
        
        # S'assurer qu'il y a des champs à mettre à jour
        if not update_fields:
            return jsonify({'message': 'Aucun champ à mettre à jour'}), 400
        
        query = f"UPDATE {table_prefix}tasks SET {', '.join(update_fields)} WHERE id = %s"
        params.append(task_id)
        
        cursor.execute(query, params)
        connection.commit()
        
        affected_rows = cursor.rowcount
        cursor.close()
        connection.close()
        
        if affected_rows > 0:
            return jsonify({
                'message': 'Tâche mise à jour avec succès',
                'status': 'success'
            })
        else:
            return jsonify({
                'message': 'Aucune modification n\'a été effectuée',
                'status': 'warning'
            })
        
    except Error as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

# Route pour supprimer une tâche
@app.route('/api/tasks/delete/<int:task_id>', methods=['POST'])
@token_required
def delete_task(task_id):
    db_type = request.args.get('db_type', 'company')
    if db_type not in ['company', 'partner']:
        return jsonify({'message': 'Type de base de données invalide!'}), 400
    
    connection = get_db_connection(db_type)
    if not connection:
        return jsonify({'message': 'Impossible de se connecter à la base de données!'}), 500
    
    try:
        cursor = connection.cursor()
        table_prefix = "partner_" if db_type == "partner" else ""
        
        # Vérifier si la tâche existe
        cursor.execute(f"SELECT id FROM {table_prefix}tasks WHERE id = %s", (task_id,))
        if cursor.fetchone() is None:
            cursor.close()
            connection.close()
            return jsonify({'message': 'Tâche non trouvée'}), 404
        
        cursor.execute(f"DELETE FROM {table_prefix}tasks WHERE id = %s", (task_id,))
        connection.commit()
        
        affected_rows = cursor.rowcount
        cursor.close()
        connection.close()
        
        if affected_rows > 0:
            return jsonify({
                'message': 'Tâche supprimée avec succès',
                'status': 'success'
            })
        else:
            return jsonify({
                'message': 'Aucune tâche n\'a été supprimée',
                'status': 'warning'
            })
        
    except Error as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

# Route pour synchroniser les utilisateurs entre les deux bases de données
@app.route('/api/sync/users', methods=['POST'])
@token_required
def sync_users():
    source_db = request.args.get('source', 'company')
    target_db = 'partner' if source_db == 'company' else 'company'
    
    source_conn = get_db_connection(source_db)
    target_conn = get_db_connection(target_db)
    
    if not source_conn or not target_conn:
        return jsonify({'message': 'Erreur de connexion aux bases de données!'}), 500
    
    try:
        # Récupérer les utilisateurs de la source
        source_cursor = source_conn.cursor(dictionary=True)
        source_prefix = "partner_" if source_db == "partner" else ""
        source_cursor.execute(f"SELECT username, email, role, company_name FROM {source_prefix}users")
        source_users = source_cursor.fetchall()
        
        # Préparer pour l'insertion dans la cible
        target_cursor = target_conn.cursor()
        target_prefix = "partner_" if target_db == "partner" else ""
        
        synced_count = 0
        for user in source_users:
            # Vérifier si l'utilisateur existe déjà dans la cible (par email)
            target_cursor.execute(f"SELECT id FROM {target_prefix}users WHERE email = %s", (user['email'],))
            existing = target_cursor.fetchone()
            
            if not existing:
                # Insérer l'utilisateur dans la cible
                # Utiliser un mot de passe temporaire haché pour les nouveaux utilisateurs
                temp_password = hashlib.sha256(f"temp_{user['email']}".encode()).hexdigest()
                
                query = f"""
                    INSERT INTO {target_prefix}users 
                    (username, email, password, role, company_name, created_at) 
                    VALUES (%s, %s, %s, %s, %s, NOW())
                """
                target_cursor.execute(query, (
                    user['username'],
                    user['email'],
                    temp_password,
                    user['role'],
                    user['company_name']
                ))
                synced_count += 1
            else:
                # Mettre à jour l'utilisateur existant
                user_id = existing[0]
                query = f"""
                    UPDATE {target_prefix}users 
                    SET username = %s, role = %s, company_name = %s, updated_at = NOW()
                    WHERE id = %s
                """
                target_cursor.execute(query, (
                    user['username'],
                    user['role'],
                    user['company_name'],
                    user_id
                ))
        
        target_conn.commit()
        return jsonify({
            'message': f'{synced_count} utilisateurs synchronisés de {source_db} vers {target_db}',
            'status': 'success'
        })
    except Error as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500
    finally:
        if source_conn.is_connected():
            source_cursor.close()
            source_conn.close()
        if target_conn.is_connected():
            target_cursor.close()
            target_conn.close()

# Route pour synchroniser les tâches entre les deux bases de données
@app.route('/api/sync/tasks', methods=['POST'])
@token_required
def sync_tasks():
    source_db = request.args.get('source', 'company')
    target_db = 'partner' if source_db == 'company' else 'company'
    
    source_conn = get_db_connection(source_db)
    target_conn = get_db_connection(target_db)
    
    if not source_conn or not target_conn:
        return jsonify({'message': 'Erreur de connexion aux bases de données!'}), 500
    
    try:
        # Récupérer les tâches de la source
        source_cursor = source_conn.cursor(dictionary=True)
        source_prefix = "partner_" if source_db == "partner" else ""
        source_cursor.execute(f"""
            SELECT t.*, u1.email as creator_email, u2.email as assignee_email
            FROM {source_prefix}tasks t
            JOIN {source_prefix}users u1 ON t.created_by = u1.id
            JOIN {source_prefix}users u2 ON t.assigned_to = u2.id
        """)
        source_tasks = source_cursor.fetchall()
        
        # Préparer pour l'insertion dans la cible
        target_cursor = target_conn.cursor()
        target_prefix = "partner_" if target_db == "partner" else ""
        
        synced_count = 0
        for task in source_tasks:
            # Trouver les IDs correspondants dans la base cible
            target_cursor.execute(f"SELECT id FROM {target_prefix}users WHERE email = %s", (task['creator_email'],))
            creator = target_cursor.fetchone()
            
            target_cursor.execute(f"SELECT id FROM {target_prefix}users WHERE email = %s", (task['assignee_email'],))
            assignee = target_cursor.fetchone()
            
            if creator and assignee:
                creator_id = creator[0]
                assignee_id = assignee[0]
                
                # Vérifier si la tâche existe déjà dans la cible (par titre et assignation)
                target_cursor.execute(f"""
                    SELECT id FROM {target_prefix}tasks 
                    WHERE title = %s AND assigned_to = %s AND created_by = %s
                """, (task['title'], assignee_id, creator_id))
                existing = target_cursor.fetchone()
                
                if not existing:
                    # Insérer la tâche dans la cible
                    query = f"""
                        INSERT INTO {target_prefix}tasks 
                        (title, description, assigned_to, created_by, priority, status, due_date, created_at) 
                        VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
                    """
                    target_cursor.execute(query, (
                        task['title'],
                        task['description'],
                        assignee_id,
                        creator_id,
                        task['priority'],
                        task['status'],
                        task['due_date']
                    ))
                    synced_count += 1
                else:
                    # Mettre à jour la tâche existante
                    task_id = existing[0]
                    query = f"""
                        UPDATE {target_prefix}tasks 
                        SET description = %s, priority = %s, status = %s, due_date = %s, updated_at = NOW()
                        WHERE id = %s
                    """
                    target_cursor.execute(query, (
                        task['description'],
                        task['priority'],
                        task['status'],
                        task['due_date'],
                        task_id
                    ))
        
        target_conn.commit()
        return jsonify({
            'message': f'{synced_count} tâches synchronisées de {source_db} vers {target_db}',
            'status': 'success'
        })
    except Error as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500
    finally:
        if source_conn.is_connected():
            source_cursor.close()
            source_conn.close()
        if target_conn.is_connected():
            target_cursor.close()
            target_conn.close()

# Route pour l'inscription d'un partenaire
@app.route('/api/partner/register', methods=['POST'])
def partner_register():
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'message': 'Aucune donnée reçue'}), 400
        
        required_fields = ['username', 'email', 'password', 'company_name']
        for field in required_fields:
            if field not in data or not data[field]:
                return jsonify({'message': f'Le champ {field} est requis'}), 400
        
        # Vérifier si l'email est valide
        email_regex = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        if not re.match(email_regex, data['email']):
            return jsonify({'message': 'Email invalide'}), 400
        
        # Vérifier si le mot de passe est suffisamment long
        if len(data['password']) < 6:
            return jsonify({'message': 'Le mot de passe doit contenir au moins 6 caractères'}), 400
        
        connection = get_db_connection('partner')
        if not connection:
            return jsonify({'message': 'Erreur de connexion à la base de données'}), 500
        
        cursor = connection.cursor()
        
        # Vérifier si l'email existe déjà
        cursor.execute("SELECT id FROM partner_users WHERE email = %s", (data['email'],))
        if cursor.fetchone():
            cursor.close()
            connection.close()
            return jsonify({'message': 'Cet email est déjà utilisé'}), 400
        
        # Hacher le mot de passe
        hashed_password = hashlib.sha256(data['password'].encode()).hexdigest()
        
        # Insérer le nouvel utilisateur
        query = """
            INSERT INTO partner_users (username, email, password, role, company_name, created_at)
            VALUES (%s, %s, %s, %s, %s, NOW())
        """
        cursor.execute(query, (
            data['username'],
            data['email'],
            hashed_password,
            data.get('role', 'admin'),  # Par défaut, le premier utilisateur est admin
            data['company_name']
        ))
        
        connection.commit()
        new_user_id = cursor.lastrowid
        
        cursor.close()
        connection.close()
        
        return jsonify({
            'message': 'Inscription réussie',
            'user_id': new_user_id,
            'status': 'success'
        }), 201
        
    except Exception as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

# Route pour la connexion d'un partenaire
@app.route('/api/partner/login', methods=['POST'])
def partner_login():
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'message': 'Aucune donnée reçue'}), 400
        
        if 'email' not in data or 'password' not in data:
            return jsonify({'message': 'Email et mot de passe requis'}), 400
        
        connection = get_db_connection('partner')
        if not connection:
            return jsonify({'message': 'Erreur de connexion à la base de données'}), 500
        
        cursor = connection.cursor(dictionary=True)
        
        # Hacher le mot de passe pour la comparaison
        hashed_password = hashlib.sha256(data['password'].encode()).hexdigest()
        
        # Récupérer l'utilisateur
        cursor.execute("""
            SELECT id, username, email, role, company_name
            FROM partner_users
            WHERE email = %s AND password = %s
        """, (data['email'], hashed_password))
        
        user = cursor.fetchone()
        
        cursor.close()
        connection.close()
        
        if not user:
            return jsonify({'message': 'Email ou mot de passe incorrect'}), 401
        
        # Générer un token simple pour le partenaire
        token = f"partner_{str(uuid.uuid4())}"
        
        return jsonify({
            'message': 'Connexion réussie',
            'user': user,
            'token': token,
            'status': 'success'
        })
        
    except Exception as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

# Route pour obtenir les statistiques du partenaire
@app.route('/api/partner/stats', methods=['GET'])
@token_required
def get_partner_stats():
    try:
        connection = get_db_connection('partner')
        if not connection:
            return jsonify({'message': 'Erreur de connexion à la base de données'}), 500
        
        cursor = connection.cursor(dictionary=True)
        
        # Récupérer le nombre total d'utilisateurs
        cursor.execute("SELECT COUNT(*) as total_users FROM partner_users")
        user_count = cursor.fetchone()['total_users']
        
        # Récupérer le nombre total de tâches
        cursor.execute("SELECT COUNT(*) as total_tasks FROM partner_tasks")
        task_count = cursor.fetchone()['total_tasks']
        
        # Récupérer les tâches par statut
        cursor.execute("""
            SELECT status, COUNT(*) as count 
            FROM partner_tasks 
            GROUP BY status
        """)
        tasks_by_status = cursor.fetchall()
        
        # Récupérer les tâches par priorité
        cursor.execute("""
            SELECT priority, COUNT(*) as count 
            FROM partner_tasks 
            GROUP BY priority
        """)
        tasks_by_priority = cursor.fetchall()
        
        cursor.close()
        connection.close()
        
        return jsonify({
            'stats': {
                'total_users': user_count,
                'total_tasks': task_count,
                'tasks_by_status': tasks_by_status,
                'tasks_by_priority': tasks_by_priority
            },
            'status': 'success'
        })
        
    except Exception as e:
        return jsonify({'message': f'Erreur: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(debug=True)
