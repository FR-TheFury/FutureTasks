
import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { Calendar, Clock, User, AlertTriangle, Save } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

interface Task {
  id: string;
  title: string;
  description: string;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  due_date: string;
  assigned_to: string;
  assigned_to_name: string;
  created_at: string;
  updated_at: string;
}

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

const EditTaskPage = () => {
  const { taskId } = useParams<{ taskId: string }>();
  const [task, setTask] = useState<Task | null>(null);
  const [currentUser, setCurrentUser] = useState<any>(null);
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    status: 'pending',
    priority: 'medium',
    due_date: '',
    assigned_to: ''
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
      
      // Vérifier les permissions
      if (parsedUser.role !== 'admin' && parsedUser.role !== 'manager') {
        toast.error('Vous n\'avez pas les droits pour modifier les tâches');
        navigate('/tasks');
        return;
      }
      
      // Charger la liste des utilisateurs
      const storedUsers = localStorage.getItem('users');
      let userList = [];
      
      if (storedUsers) {
        userList = JSON.parse(storedUsers);
      } else {
        // Utilisateurs par défaut
        userList = [
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
      }
      
      setUsers(userList);
      
      // Charger la tâche
      const storedTasks = localStorage.getItem('tasks') || '[]';
      const tasks = JSON.parse(storedTasks);
      const foundTask = tasks.find((t: Task) => t.id === taskId);
      
      if (foundTask) {
        setTask(foundTask);
        
        // Formater la date pour l'input de type date
        const dueDate = new Date(foundTask.due_date);
        const formattedDate = dueDate.toISOString().split('T')[0];
        
        setFormData({
          title: foundTask.title,
          description: foundTask.description,
          status: foundTask.status,
          priority: foundTask.priority,
          due_date: formattedDate,
          assigned_to: foundTask.assigned_to
        });
      } else {
        toast.error('Tâche non trouvée');
        navigate('/tasks');
      }
      
      setIsLoading(false);
    } catch (error) {
      console.error('Error loading task:', error);
      navigate('/login');
    }
  }, [navigate, taskId]);
  
  const handleLogout = () => {
    localStorage.removeItem('currentUser');
    toast.info('Vous avez été déconnecté');
    navigate('/login');
  };
  
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.title.trim()) {
      toast.error('Le titre est requis');
      return;
    }
    
    if (!formData.due_date) {
      toast.error('La date d\'échéance est requise');
      return;
    }
    
    if (!formData.assigned_to) {
      toast.error('Vous devez assigner la tâche à un utilisateur');
      return;
    }
    
    try {
      const storedTasks = localStorage.getItem('tasks') || '[]';
      const tasks = JSON.parse(storedTasks);
      
      // Trouver l'utilisateur assigné pour obtenir son nom
      const assignedUser = users.find(u => u.id === formData.assigned_to);
      
      const updatedTasks = tasks.map((t: Task) => {
        if (t.id === taskId) {
          return { 
            ...t,
            title: formData.title,
            description: formData.description,
            status: formData.status,
            priority: formData.priority,
            due_date: new Date(formData.due_date).toISOString(),
            assigned_to: formData.assigned_to,
            assigned_to_name: assignedUser?.name || 'Utilisateur inconnu',
            updated_at: new Date().toISOString()
          };
        }
        return t;
      });
      
      localStorage.setItem('tasks', JSON.stringify(updatedTasks));
      toast.success('Tâche mise à jour avec succès');
      navigate(`/tasks/${taskId}`);
    } catch (error) {
      console.error('Error updating task:', error);
      toast.error('Erreur lors de la mise à jour de la tâche');
    }
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
  
  if (!task) {
    return (
      <div className="min-h-screen relative">
        <ThreeBackground />
        
        <Navigation
          userRole={currentUser.role}
          onLogout={handleLogout}
        />
        
        <main className="container mx-auto px-4 py-16">
          <div className="glass-panel p-8 max-w-2xl mx-auto text-center">
            <AlertTriangle size={48} className="mx-auto text-yellow-500 mb-4" />
            <h1 className="text-2xl font-bold text-white mb-4">Tâche non trouvée</h1>
            <p className="text-gray-300 mb-6">La tâche que vous recherchez n'existe pas ou a été supprimée.</p>
            <FuturisticButton onClick={() => navigate('/tasks')}>
              Retour aux tâches
            </FuturisticButton>
          </div>
        </main>
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
        <div className="max-w-3xl mx-auto">
          <div className="mb-6">
            <button 
              onClick={() => navigate(`/tasks/${taskId}`)}
              className="flex items-center text-gray-400 hover:text-white transition-colors"
            >
              <span className="mr-2">←</span>
              Retour aux détails de la tâche
            </button>
          </div>
          
          <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent mb-6">
            Modifier la tâche
          </h1>
          
          <div className="futuristic-card">
            <form onSubmit={handleSubmit} className="space-y-6">
              <FuturisticInput
                type="text"
                name="title"
                label="Titre"
                value={formData.title}
                onChange={handleChange}
                required
              />
              
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-200">
                  Description
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  rows={5}
                  className="w-full bg-transparent border border-white/20 rounded-md p-3 focus:ring-2 focus:ring-futuristic-blue focus:border-transparent outline-none transition-all text-white"
                />
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-200">
                    Statut
                  </label>
                  <select
                    name="status"
                    value={formData.status}
                    onChange={handleChange}
                    className="w-full bg-white/5 border border-white/20 rounded-md p-3 focus:ring-2 focus:ring-futuristic-blue focus:border-transparent outline-none transition-all text-white"
                  >
                    <option value="pending" className="bg-gray-800">En attente</option>
                    <option value="in_progress" className="bg-gray-800">En cours</option>
                    <option value="completed" className="bg-gray-800">Terminée</option>
                    <option value="cancelled" className="bg-gray-800">Annulée</option>
                  </select>
                </div>
                
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-200">
                    Priorité
                  </label>
                  <select
                    name="priority"
                    value={formData.priority}
                    onChange={handleChange}
                    className="w-full bg-white/5 border border-white/20 rounded-md p-3 focus:ring-2 focus:ring-futuristic-blue focus:border-transparent outline-none transition-all text-white"
                  >
                    <option value="low" className="bg-gray-800">Faible</option>
                    <option value="medium" className="bg-gray-800">Moyenne</option>
                    <option value="high" className="bg-gray-800">Élevée</option>
                    <option value="urgent" className="bg-gray-800">Urgente</option>
                  </select>
                </div>
                
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-200">
                    Date d'échéance
                  </label>
                  <input
                    type="date"
                    name="due_date"
                    value={formData.due_date}
                    onChange={handleChange}
                    className="w-full bg-white/5 border border-white/20 rounded-md p-3 focus:ring-2 focus:ring-futuristic-blue focus:border-transparent outline-none transition-all text-white"
                    required
                  />
                </div>
                
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-200">
                    Assigné à
                  </label>
                  <select
                    name="assigned_to"
                    value={formData.assigned_to}
                    onChange={handleChange}
                    className="w-full bg-white/5 border border-white/20 rounded-md p-3 focus:ring-2 focus:ring-futuristic-blue focus:border-transparent outline-none transition-all text-white"
                    required
                  >
                    <option value="" className="bg-gray-800">Sélectionnez un utilisateur</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id} className="bg-gray-800">
                        {user.name} ({user.email})
                      </option>
                    ))}
                  </select>
                </div>
              </div>
              
              <div className="pt-4 flex justify-end space-x-4 border-t border-white/10">
                <FuturisticButton 
                  type="button" 
                  variant="ghost" 
                  onClick={() => navigate(`/tasks/${taskId}`)}
                >
                  Annuler
                </FuturisticButton>
                
                <FuturisticButton type="submit">
                  <Save size={18} className="mr-2" />
                  Enregistrer les modifications
                </FuturisticButton>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  );
};

export default EditTaskPage;
