
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { toast } from '@/components/ui/sonner';
import { User, Lock, Shield } from 'lucide-react';

interface UserInfo {
  id: string;
  name: string;
  email: string;
  role: string;
}

const SettingsPage = () => {
  const [user, setUser] = useState<UserInfo | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'profile' | 'security' | 'roles'>('profile');
  
  // Formulaires
  const [profileForm, setProfileForm] = useState({
    name: '',
    email: ''
  });
  
  const [passwordForm, setPasswordForm] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
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
      setUser(parsedUser);
      
      // Vérifier que l'utilisateur a les droits d'administration
      if (parsedUser.role !== 'admin') {
        toast.error('Accès non autorisé');
        navigate('/dashboard');
        return;
      }
      
      // Initialiser le formulaire
      setProfileForm({
        name: parsedUser.name,
        email: parsedUser.email
      });
      
      setIsLoading(false);
    } catch (error) {
      console.error('Error parsing user data:', error);
      navigate('/login');
    }
  }, [navigate]);
  
  const handleLogout = () => {
    localStorage.removeItem('currentUser');
    toast.info('Vous avez été déconnecté');
    navigate('/login');
  };
  
  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) return;
    
    // Mettre à jour l'utilisateur en localStorage
    const updatedUser = {
      ...user,
      name: profileForm.name,
      email: profileForm.email
    };
    
    localStorage.setItem('currentUser', JSON.stringify(updatedUser));
    setUser(updatedUser);
    
    toast.success('Profil mis à jour avec succès');
  };
  
  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!passwordForm.currentPassword) {
      toast.error('Le mot de passe actuel est requis');
      return;
    }
    
    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      toast.error('Les nouveaux mots de passe ne correspondent pas');
      return;
    }
    
    if (passwordForm.newPassword.length < 6) {
      toast.error('Le mot de passe doit contenir au moins 6 caractères');
      return;
    }
    
    // Dans une vraie app, on vérifierait l'ancien mot de passe avec l'API
    // Pour cette démo, nous simulons simplement la mise à jour
    toast.success('Mot de passe mis à jour avec succès');
    
    setPasswordForm({
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    });
  };
  
  if (isLoading || !user) {
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
        userRole={user.role}
        onLogout={handleLogout}
      />
      
      <main className="container mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
            Paramètres
          </h1>
          <p className="text-gray-400 mt-1">
            Gérez votre profil et les paramètres du système
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div className="futuristic-card">
            <nav>
              <ul className="space-y-1">
                <li>
                  <button
                    onClick={() => setActiveTab('profile')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-md transition-all ${
                      activeTab === 'profile' 
                        ? 'bg-white/10 text-futuristic-blue' 
                        : 'hover:bg-white/5'
                    }`}
                  >
                    <User size={18} />
                    Profil
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => setActiveTab('security')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-md transition-all ${
                      activeTab === 'security' 
                        ? 'bg-white/10 text-futuristic-blue' 
                        : 'hover:bg-white/5'
                    }`}
                  >
                    <Lock size={18} />
                    Sécurité
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => setActiveTab('roles')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-md transition-all ${
                      activeTab === 'roles' 
                        ? 'bg-white/10 text-futuristic-blue' 
                        : 'hover:bg-white/5'
                    }`}
                  >
                    <Shield size={18} />
                    Rôles et permissions
                  </button>
                </li>
              </ul>
            </nav>
          </div>
          
          <div className="md:col-span-3">
            <div className="futuristic-card">
              {activeTab === 'profile' && (
                <>
                  <h2 className="text-xl font-semibold mb-6">Informations de profil</h2>
                  
                  <form onSubmit={handleProfileSubmit} className="space-y-6">
                    <FuturisticInput
                      type="text"
                      label="Nom"
                      value={profileForm.name}
                      onChange={(e) => setProfileForm({...profileForm, name: e.target.value})}
                      required
                    />
                    
                    <FuturisticInput
                      type="email"
                      label="Email"
                      value={profileForm.email}
                      onChange={(e) => setProfileForm({...profileForm, email: e.target.value})}
                      required
                    />
                    
                    <div className="pt-2">
                      <FuturisticButton type="submit">
                        Enregistrer les modifications
                      </FuturisticButton>
                    </div>
                  </form>
                </>
              )}
              
              {activeTab === 'security' && (
                <>
                  <h2 className="text-xl font-semibold mb-6">Changer le mot de passe</h2>
                  
                  <form onSubmit={handlePasswordSubmit} className="space-y-6">
                    <FuturisticInput
                      type="password"
                      label="Mot de passe actuel"
                      value={passwordForm.currentPassword}
                      onChange={(e) => setPasswordForm({...passwordForm, currentPassword: e.target.value})}
                      required
                    />
                    
                    <FuturisticInput
                      type="password"
                      label="Nouveau mot de passe"
                      value={passwordForm.newPassword}
                      onChange={(e) => setPasswordForm({...passwordForm, newPassword: e.target.value})}
                      required
                    />
                    
                    <FuturisticInput
                      type="password"
                      label="Confirmer le nouveau mot de passe"
                      value={passwordForm.confirmPassword}
                      onChange={(e) => setPasswordForm({...passwordForm, confirmPassword: e.target.value})}
                      required
                    />
                    
                    <div className="pt-2">
                      <FuturisticButton type="submit">
                        Modifier le mot de passe
                      </FuturisticButton>
                    </div>
                  </form>
                </>
              )}
              
              {activeTab === 'roles' && (
                <>
                  <h2 className="text-xl font-semibold mb-6">Rôles et permissions</h2>
                  
                  <div className="space-y-6">
                    <div className="p-4 bg-black/30 rounded-md border border-white/10">
                      <h3 className="text-lg font-medium mb-2 text-futuristic-pink">Admin</h3>
                      <ul className="list-disc list-inside text-sm space-y-1 text-gray-300">
                        <li>Accès complet à toutes les fonctionnalités</li>
                        <li>Gestion des utilisateurs (création, modification, suppression)</li>
                        <li>Gestion des tâches (création, modification, suppression)</li>
                        <li>Accès aux paramètres du système</li>
                      </ul>
                    </div>
                    
                    <div className="p-4 bg-black/30 rounded-md border border-white/10">
                      <h3 className="text-lg font-medium mb-2 text-futuristic-purple">Manager</h3>
                      <ul className="list-disc list-inside text-sm space-y-1 text-gray-300">
                        <li>Visualisation de la liste des utilisateurs</li>
                        <li>Gestion des tâches (création, modification, suppression)</li>
                        <li>Assignation des tâches aux utilisateurs</li>
                      </ul>
                    </div>
                    
                    <div className="p-4 bg-black/30 rounded-md border border-white/10">
                      <h3 className="text-lg font-medium mb-2 text-futuristic-blue">Utilisateur</h3>
                      <ul className="list-disc list-inside text-sm space-y-1 text-gray-300">
                        <li>Visualisation des tâches qui lui sont assignées</li>
                        <li>Mise à jour du statut de ses tâches</li>
                        <li>Modification de son profil personnel</li>
                      </ul>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default SettingsPage;
