
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { Trash, UserPlus, Edit, Search } from 'lucide-react';
import { toast } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

// Utilisateurs de démonstration
const DEMO_USERS: User[] = [
  {
    id: '1',
    name: 'Admin User',
    email: 'admin@example.com',
    role: 'admin'
  },
  {
    id: '2',
    name: 'Manager User',
    email: 'manager@example.com',
    role: 'manager'
  },
  {
    id: '3',
    name: 'Normal User',
    email: 'user@example.com',
    role: 'user'
  }
];

const UsersPage = () => {
  const [users, setUsers] = useState<User[]>(DEMO_USERS);
  const [filteredUsers, setFilteredUsers] = useState<User[]>(DEMO_USERS);
  const [searchQuery, setSearchQuery] = useState('');
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [showNewUserDialog, setShowNewUserDialog] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  
  // Formulaire pour nouvel utilisateur
  const [newUserForm, setNewUserForm] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    role: 'user'
  });
  
  const navigate = useNavigate();
  
  useEffect(() => {
    // Vérifier si l'utilisateur est connecté
    const storedUser = localStorage.getItem('currentUser');
    
    if (!storedUser) {
      navigate('/login');
      return;
    }
    
    try {
      const parsedUser = JSON.parse(storedUser);
      setCurrentUser(parsedUser);
      
      // Vérifier que l'utilisateur a les droits d'administration
      if (parsedUser.role !== 'admin' && parsedUser.role !== 'manager') {
        toast.error('Accès non autorisé');
        navigate('/dashboard');
        return;
      }
      
      // Simuler le chargement des utilisateurs depuis une API
      setTimeout(() => {
        // Chargé depuis notre mock ou localStorage
        const storedUsers = localStorage.getItem('users');
        const loadedUsers = storedUsers ? JSON.parse(storedUsers) : DEMO_USERS;
        
        // Fusionner les utilisateurs démo avec ceux créés
        const allUsers = [...DEMO_USERS];
        
        // Ajouter les utilisateurs créés qui ne sont pas des utilisateurs démo
        if (storedUsers) {
          const createdUsers = JSON.parse(storedUsers);
          createdUsers.forEach((createdUser: User) => {
            if (!allUsers.some(user => user.email === createdUser.email)) {
              allUsers.push(createdUser);
            }
          });
        }
        
        setUsers(allUsers);
        setFilteredUsers(allUsers);
        setIsLoading(false);
      }, 800);
    } catch (error) {
      console.error('Error parsing user data:', error);
      navigate('/login');
    }
  }, [navigate]);
  
  useEffect(() => {
    // Filtrer les utilisateurs en fonction de la recherche
    const filtered = users.filter(user => {
      return (
        user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
        user.role.toLowerCase().includes(searchQuery.toLowerCase())
      );
    });
    
    setFilteredUsers(filtered);
  }, [users, searchQuery]);
  
  const handleLogout = () => {
    localStorage.removeItem('currentUser');
    toast.info('Vous avez été déconnecté');
    navigate('/login');
  };
  
  const handleDeleteUser = (userId: string) => {
    // Empêcher la suppression des utilisateurs démo
    if (['1', '2', '3'].includes(userId)) {
      toast.error('Impossible de supprimer les utilisateurs de démonstration');
      return;
    }
    
    // Empêcher la suppression de soi-même
    if (currentUser && userId === currentUser.id) {
      toast.error('Vous ne pouvez pas supprimer votre propre compte');
      return;
    }
    
    const updatedUsers = users.filter(user => user.id !== userId);
    setUsers(updatedUsers);
    
    // Mettre à jour le localStorage
    const storedUsers = JSON.parse(localStorage.getItem('users') || '[]');
    const updatedStoredUsers = storedUsers.filter((user: User) => user.id !== userId);
    localStorage.setItem('users', JSON.stringify(updatedStoredUsers));
    
    toast.success('Utilisateur supprimé');
  };
  
  const resetNewUserForm = () => {
    setNewUserForm({
      name: '',
      email: '',
      password: '',
      confirmPassword: '',
      role: 'user'
    });
    setEditingUser(null);
  };
  
  const handleEditUser = (user: User) => {
    // Empêcher l'édition des utilisateurs démo
    if (['1', '2', '3'].includes(user.id)) {
      toast.error('Impossible de modifier les utilisateurs de démonstration');
      return;
    }
    
    setEditingUser(user);
    setNewUserForm({
      name: user.name,
      email: user.email,
      password: '',
      confirmPassword: '',
      role: user.role
    });
    setShowNewUserDialog(true);
  };
  
  const validateUserForm = () => {
    if (!newUserForm.name.trim()) {
      toast.error('Le nom est requis');
      return false;
    }
    
    if (!newUserForm.email.trim()) {
      toast.error('L\'email est requis');
      return false;
    }
    
    if (!editingUser && !newUserForm.password) {
      toast.error('Le mot de passe est requis');
      return false;
    }
    
    if (!editingUser && newUserForm.password !== newUserForm.confirmPassword) {
      toast.error('Les mots de passe ne correspondent pas');
      return false;
    }
    
    // Vérifier si l'email existe déjà (sauf pour l'édition)
    if (!editingUser && users.some(user => user.email === newUserForm.email)) {
      toast.error('Cet email est déjà utilisé');
      return false;
    }
    
    return true;
  };
  
  const handleSaveUser = () => {
    if (!validateUserForm()) return;
    
    if (editingUser) {
      // Mise à jour d'un utilisateur existant
      const updatedUsers = users.map(user => 
        user.id === editingUser.id 
          ? { 
              ...user, 
              name: newUserForm.name,
              email: newUserForm.email,
              role: newUserForm.role
            } 
          : user
      );
      
      setUsers(updatedUsers);
      
      // Mettre à jour le localStorage
      const storedUsers = JSON.parse(localStorage.getItem('users') || '[]');
      const updatedStoredUsers = storedUsers.map((user: User) => 
        user.id === editingUser.id 
          ? { 
              ...user, 
              name: newUserForm.name,
              email: newUserForm.email,
              role: newUserForm.role
            } 
          : user
      );
      
      localStorage.setItem('users', JSON.stringify(updatedStoredUsers));
      toast.success('Utilisateur mis à jour');
    } else {
      // Création d'un nouvel utilisateur
      const newUser = {
        id: Math.random().toString(36).substr(2, 9),
        name: newUserForm.name,
        email: newUserForm.email,
        role: newUserForm.role
      };
      
      const updatedUsers = [...users, newUser];
      setUsers(updatedUsers);
      
      // Mettre à jour le localStorage
      const storedUsers = JSON.parse(localStorage.getItem('users') || '[]');
      storedUsers.push({...newUser, password: newUserForm.password});
      localStorage.setItem('users', JSON.stringify(storedUsers));
      
      toast.success('Nouvel utilisateur créé');
    }
    
    setShowNewUserDialog(false);
    resetNewUserForm();
  };
  
  if (isLoading || !currentUser) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <ThreeBackground />
        <div className="glass-panel p-8">
          <div className="h-16 w-16 border-4 border-futuristic-blue border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="mt-4 text-center text-gray-300">Chargement...</p>
        </div>
      </div>
    );
  }
  
  return (
    <div className="min-h-screen relative">
      <ThreeBackground />
      
      <Navigation
        userRole={currentUser.role}
        onLogout={handleLogout}
      />
      
      <main className="container mx-auto px-4 py-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
              Gestion des utilisateurs
            </h1>
            <p className="text-gray-400 mt-1">
              Gérez les utilisateurs et leurs permissions
            </p>
          </div>
          
          <div className="flex gap-3 items-center w-full md:w-auto">
            <div className="relative w-full md:w-64">
              <FuturisticInput
                type="text"
                placeholder="Rechercher un utilisateur..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
              <Search size={18} className="absolute left-3 top-3 text-gray-400" />
            </div>
            
            {currentUser.role === 'admin' && (
              <FuturisticButton 
                onClick={() => {
                  resetNewUserForm();
                  setShowNewUserDialog(true);
                }}
                className="flex items-center shrink-0"
              >
                <UserPlus size={18} className="mr-2" /> 
                Nouvel utilisateur
              </FuturisticButton>
            )}
          </div>
        </div>
        
        <div className="futuristic-card overflow-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-white/10">
                <th className="py-3 px-4 text-left font-medium text-gray-300">Nom</th>
                <th className="py-3 px-4 text-left font-medium text-gray-300">Email</th>
                <th className="py-3 px-4 text-left font-medium text-gray-300">Rôle</th>
                <th className="py-3 px-4 text-right font-medium text-gray-300">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredUsers.length === 0 ? (
                <tr>
                  <td colSpan={4} className="py-8 text-center text-gray-400">
                    Aucun utilisateur trouvé
                  </td>
                </tr>
              ) : (
                filteredUsers.map((user) => (
                  <tr 
                    key={user.id} 
                    className="border-b border-white/5 hover:bg-white/5 transition-colors"
                  >
                    <td className="py-3 px-4">{user.name}</td>
                    <td className="py-3 px-4">{user.email}</td>
                    <td className="py-3 px-4">
                      <span className={cn(
                        "px-2 py-1 text-xs rounded-full",
                        {
                          'bg-futuristic-pink/20 text-futuristic-pink': user.role === 'admin',
                          'bg-futuristic-purple/20 text-futuristic-purple': user.role === 'manager',
                          'bg-futuristic-blue/20 text-futuristic-blue': user.role === 'user'
                        }
                      )}>
                        {user.role}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-right space-x-2">
                      <FuturisticButton 
                        variant="ghost"
                        size="sm"
                        onClick={() => handleEditUser(user)}
                        disabled={['1', '2', '3'].includes(user.id) || currentUser.role !== 'admin'}
                        className={cn(
                          {'opacity-50 cursor-not-allowed': ['1', '2', '3'].includes(user.id) || currentUser.role !== 'admin'}
                        )}
                      >
                        <Edit size={16} />
                      </FuturisticButton>
                      <FuturisticButton 
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDeleteUser(user.id)}
                        disabled={['1', '2', '3'].includes(user.id) || currentUser.id === user.id || currentUser.role !== 'admin'}
                        className={cn(
                          "text-destructive",
                          {'opacity-50 cursor-not-allowed': ['1', '2', '3'].includes(user.id) || currentUser.id === user.id || currentUser.role !== 'admin'}
                        )}
                      >
                        <Trash size={16} />
                      </FuturisticButton>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </main>
      
      {/* Modal pour créer/éditer un utilisateur */}
      {showNewUserDialog && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="futuristic-card w-full max-w-md animate-fade-in">
            <h2 className="text-xl font-semibold mb-4">
              {editingUser ? 'Modifier l\'utilisateur' : 'Créer un nouvel utilisateur'}
            </h2>
            
            <div className="space-y-4">
              <FuturisticInput
                type="text"
                label="Nom"
                placeholder="Nom complet"
                value={newUserForm.name}
                onChange={(e) => setNewUserForm({...newUserForm, name: e.target.value})}
                required
              />
              
              <FuturisticInput
                type="email"
                label="Email"
                placeholder="email@example.com"
                value={newUserForm.email}
                onChange={(e) => setNewUserForm({...newUserForm, email: e.target.value})}
                required
              />
              
              {!editingUser && (
                <>
                  <FuturisticInput
                    type="password"
                    label="Mot de passe"
                    placeholder="••••••••"
                    value={newUserForm.password}
                    onChange={(e) => setNewUserForm({...newUserForm, password: e.target.value})}
                    required
                  />
                  
                  <FuturisticInput
                    type="password"
                    label="Confirmer le mot de passe"
                    placeholder="••••••••"
                    value={newUserForm.confirmPassword}
                    onChange={(e) => setNewUserForm({...newUserForm, confirmPassword: e.target.value})}
                    required
                  />
                </>
              )}
              
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-200">Rôle</label>
                <div className="flex flex-wrap gap-2">
                  {['admin', 'manager', 'user'].map(role => (
                    <button
                      key={role}
                      type="button"
                      onClick={() => setNewUserForm({...newUserForm, role})}
                      className={`px-3 py-1.5 text-sm rounded-full transition-all ${
                        newUserForm.role === role
                          ? 'bg-futuristic-blue text-white'
                          : 'bg-white/10 hover:bg-white/20'
                      }`}
                    >
                      {role}
                    </button>
                  ))}
                </div>
              </div>
            </div>
            
            <div className="flex justify-end gap-3 mt-6">
              <FuturisticButton 
                variant="ghost" 
                onClick={() => {
                  setShowNewUserDialog(false);
                  resetNewUserForm();
                }}
              >
                Annuler
              </FuturisticButton>
              <FuturisticButton onClick={handleSaveUser}>
                {editingUser ? 'Enregistrer' : 'Créer'}
              </FuturisticButton>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default UsersPage;
