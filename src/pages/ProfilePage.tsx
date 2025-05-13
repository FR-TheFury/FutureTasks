
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { User, Mail, Key } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

interface UserProfile {
  id: string;
  name: string;
  email: string;
  role: string;
}

const ProfilePage = () => {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
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
      setFormData({
        name: parsedUser.name,
        email: parsedUser.email,
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
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
  
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validation des mots de passe si modification
    if (formData.newPassword) {
      if (formData.newPassword !== formData.confirmPassword) {
        toast.error('Les nouveaux mots de passe ne correspondent pas');
        return;
      }
      
      // Normalement, nous vérifierions le mot de passe actuel avec le backend
      // Mais pour cette démo, nous simulons simplement
    }
    
    // Dans un vrai système, nous enverrions les modifications au backend
    const updatedUser = {
      ...user!,
      name: formData.name,
      email: formData.email
    };
    
    localStorage.setItem('currentUser', JSON.stringify(updatedUser));
    setUser(updatedUser);
    
    toast.success('Profil mis à jour avec succès');
    setIsEditing(false);
  };
  
  if (isLoading) {
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
        userRole={user?.role || 'user'}
        onLogout={handleLogout}
      />
      
      <main className="container mx-auto px-4 py-8">
        <div className="max-w-2xl mx-auto">
          <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent mb-8">
            Mon Profil
          </h1>
          
          <div className="futuristic-card">
            <div className="flex justify-between items-center mb-6">
              <div className="flex items-center space-x-4">
                <div className="h-16 w-16 rounded-full bg-gradient-to-br from-futuristic-blue to-futuristic-purple flex items-center justify-center">
                  <User size={32} className="text-white" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white">{user?.name}</h2>
                  <p className="text-gray-300">{user?.email}</p>
                  <span className="inline-block mt-1 px-3 py-1 text-xs rounded-full bg-futuristic-blue/20 text-futuristic-blue">
                    {user?.role === 'admin' ? 'Administrateur' : user?.role === 'manager' ? 'Manager' : 'Utilisateur'}
                  </span>
                </div>
              </div>
              
              {!isEditing && (
                <FuturisticButton onClick={() => setIsEditing(true)}>
                  Modifier
                </FuturisticButton>
              )}
            </div>
            
            {isEditing ? (
              <form onSubmit={handleSubmit} className="space-y-6">
                <FuturisticInput
                  type="text"
                  name="name"
                  label="Nom"
                  icon={<User size={18} />}
                  value={formData.name}
                  onChange={handleChange}
                  required
                />
                
                <FuturisticInput
                  type="email"
                  name="email"
                  label="Email"
                  icon={<Mail size={18} />}
                  value={formData.email}
                  onChange={handleChange}
                  required
                />
                
                <div className="border-t border-white/10 my-6 pt-6">
                  <h3 className="text-lg font-medium mb-4">Changer le mot de passe</h3>
                  
                  <FuturisticInput
                    type="password"
                    name="currentPassword"
                    label="Mot de passe actuel"
                    icon={<Key size={18} />}
                    value={formData.currentPassword}
                    onChange={handleChange}
                  />
                  
                  <FuturisticInput
                    type="password"
                    name="newPassword"
                    label="Nouveau mot de passe"
                    icon={<Key size={18} />}
                    value={formData.newPassword}
                    onChange={handleChange}
                  />
                  
                  <FuturisticInput
                    type="password"
                    name="confirmPassword"
                    label="Confirmer le nouveau mot de passe"
                    icon={<Key size={18} />}
                    value={formData.confirmPassword}
                    onChange={handleChange}
                  />
                </div>
                
                <div className="flex justify-end space-x-4 pt-4">
                  <FuturisticButton 
                    variant="ghost" 
                    onClick={() => setIsEditing(false)}
                    type="button"
                  >
                    Annuler
                  </FuturisticButton>
                  <FuturisticButton type="submit">
                    Enregistrer
                  </FuturisticButton>
                </div>
              </form>
            ) : (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    Nom d'utilisateur
                  </label>
                  <p className="text-white bg-white/5 py-2 px-3 rounded-md">
                    {user?.name}
                  </p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    Email
                  </label>
                  <p className="text-white bg-white/5 py-2 px-3 rounded-md">
                    {user?.email}
                  </p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-400 mb-1">
                    Rôle
                  </label>
                  <p className="text-white bg-white/5 py-2 px-3 rounded-md">
                    {user?.role === 'admin' ? 'Administrateur' : user?.role === 'manager' ? 'Manager' : 'Utilisateur'}
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
};

export default ProfilePage;
