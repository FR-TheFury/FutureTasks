
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { Task } from '../components/TaskCard';
import { toast } from '@/components/ui/sonner';

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

const NewTaskPage = () => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    assignedRoles: [] as string[]
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
      
      // Vérifier que l'utilisateur a les droits pour créer des tâches
      if (parsedUser.role !== 'admin' && parsedUser.role !== 'manager') {
        toast.error('Vous n\'avez pas les droits pour créer des tâches');
        navigate('/dashboard');
        return;
      }
      
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
  
  const toggleRoleSelection = (role: string) => {
    if (formData.assignedRoles.includes(role)) {
      setFormData({
        ...formData,
        assignedRoles: formData.assignedRoles.filter(r => r !== role)
      });
    } else {
      setFormData({
        ...formData,
        assignedRoles: [...formData.assignedRoles, role]
      });
    }
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.title.trim()) {
      toast.error('Le titre est requis');
      return;
    }
    
    if (formData.assignedRoles.length === 0) {
      toast.error('Veuillez sélectionner au moins un rôle');
      return;
    }
    
    // Créer une nouvelle tâche
    const newTask: Task = {
      id: Math.random().toString(36).substr(2, 9),
      title: formData.title,
      description: formData.description,
      status: 'pending',
      assignedRoles: formData.assignedRoles
    };
    
    // Récupérer les tâches existantes
    const storedTasks = localStorage.getItem('tasks');
    const tasks = storedTasks ? JSON.parse(storedTasks) : [];
    
    // Ajouter la nouvelle tâche
    tasks.push(newTask);
    
    // Sauvegarder dans localStorage
    localStorage.setItem('tasks', JSON.stringify(tasks));
    
    toast.success('Tâche créée avec succès');
    navigate('/tasks');
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
            Créer une nouvelle tâche
          </h1>
          <p className="text-gray-400 mt-1">
            Définissez les détails et les permissions pour cette tâche
          </p>
        </div>
        
        <div className="futuristic-card max-w-2xl mx-auto">
          <form onSubmit={handleSubmit} className="space-y-6">
            <FuturisticInput
              type="text"
              label="Titre de la tâche"
              placeholder="Ex: Préparer la présentation pour le client"
              value={formData.title}
              onChange={(e) => setFormData({...formData, title: e.target.value})}
              required
            />
            
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-200">Description</label>
              <textarea
                className="futuristic-input w-full h-32 resize-none"
                placeholder="Description détaillée de la tâche..."
                value={formData.description}
                onChange={(e) => setFormData({...formData, description: e.target.value})}
              />
            </div>
            
            <div className="space-y-3">
              <label className="text-sm font-medium text-gray-200">Rôles autorisés</label>
              <p className="text-xs text-gray-400">
                Sélectionnez les rôles qui auront accès à cette tâche
              </p>
              
              <div className="flex flex-wrap gap-3">
                {['admin', 'manager', 'user'].map(role => (
                  <button
                    key={role}
                    type="button"
                    onClick={() => toggleRoleSelection(role)}
                    className={`px-4 py-2 rounded-full text-sm transition-all ${
                      formData.assignedRoles.includes(role)
                        ? 'bg-futuristic-blue text-white shadow-lg shadow-futuristic-blue/20'
                        : 'bg-white/10 hover:bg-white/20'
                    }`}
                  >
                    {role}
                  </button>
                ))}
              </div>
            </div>
            
            <div className="flex items-center justify-end space-x-3 pt-4">
              <FuturisticButton
                type="button"
                variant="ghost"
                onClick={() => navigate('/tasks')}
              >
                Annuler
              </FuturisticButton>
              <FuturisticButton type="submit">
                Créer la tâche
              </FuturisticButton>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
};

export default NewTaskPage;
