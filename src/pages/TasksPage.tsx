
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import TaskCard, { Task } from '../components/TaskCard';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import FuturisticInput from '../components/FuturisticInput';
import { Dialog } from '@/components/ui/dialog';
import { Plus, Search } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

// Simulons une base de données de tâches
const MOCK_TASKS: Task[] = [
  {
    id: '1',
    title: 'Analyser les données du projet X',
    description: 'Examiner les résultats du dernier trimestre et préparer un rapport',
    status: 'pending',
    assignedRoles: ['manager', 'admin']
  },
  {
    id: '2',
    title: 'Réviser la documentation technique',
    description: 'Mettre à jour la documentation du projet avec les dernières modifications',
    status: 'pending',
    assignedRoles: ['user', 'manager', 'admin']
  },
  {
    id: '3',
    title: 'Configurer le nouveau serveur',
    description: 'Installer et configurer le système sur le nouveau serveur',
    status: 'completed',
    assignedRoles: ['admin']
  },
  {
    id: '4',
    title: 'Répondre aux emails des clients',
    description: 'Traiter les demandes reçues durant la semaine',
    status: 'pending',
    assignedRoles: ['user', 'manager']
  },
  {
    id: '5',
    title: 'Organiser la réunion hebdomadaire',
    description: 'Préparer l\'agenda et inviter tous les participants',
    status: 'pending',
    assignedRoles: ['manager', 'admin']
  }
];

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

interface NewTaskForm {
  title: string;
  description: string;
  assignedRoles: string[];
}

const TasksPage = () => {
  const [tasks, setTasks] = useState<Task[]>(MOCK_TASKS);
  const [filteredTasks, setFilteredTasks] = useState<Task[]>(MOCK_TASKS);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'completed'>('all');
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [showNewTaskDialog, setShowNewTaskDialog] = useState(false);
  const [newTaskForm, setNewTaskForm] = useState<NewTaskForm>({
    title: '',
    description: '',
    assignedRoles: []
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
      setUser(JSON.parse(storedUser));
      
      // Simuler le chargement des tâches depuis une API
      setTimeout(() => {
        // Chargé depuis notre mock ou localStorage
        const storedTasks = localStorage.getItem('tasks');
        const loadedTasks = storedTasks ? JSON.parse(storedTasks) : MOCK_TASKS;
        setTasks(loadedTasks);
        setFilteredTasks(loadedTasks);
        setIsLoading(false);
      }, 800);
    } catch (error) {
      console.error('Error parsing user data:', error);
      navigate('/login');
    }
  }, [navigate]);
  
  useEffect(() => {
    // Filtrer les tâches en fonction de la recherche et du statut
    const filtered = tasks.filter(task => {
      // Filtre par recherche
      const matchesSearch = task.title.toLowerCase().includes(searchQuery.toLowerCase()) || 
                           task.description.toLowerCase().includes(searchQuery.toLowerCase());
      
      // Filtre par statut
      const matchesStatus = filterStatus === 'all' || task.status === filterStatus;
      
      return matchesSearch && matchesStatus;
    });
    
    setFilteredTasks(filtered);
  }, [tasks, searchQuery, filterStatus]);
  
  const handleLogout = () => {
    localStorage.removeItem('currentUser');
    toast.info('Vous avez été déconnecté');
    navigate('/login');
  };
  
  const handleCompleteTask = (taskId: string) => {
    const updatedTasks = tasks.map(task => 
      task.id === taskId 
        ? { ...task, status: 'completed' as const } 
        : task
    );
    
    setTasks(updatedTasks);
    localStorage.setItem('tasks', JSON.stringify(updatedTasks));
    toast.success('Tâche marquée comme terminée');
  };
  
  const handleDeleteTask = (taskId: string) => {
    const updatedTasks = tasks.filter(task => task.id !== taskId);
    
    setTasks(updatedTasks);
    localStorage.setItem('tasks', JSON.stringify(updatedTasks));
    toast.success('Tâche supprimée');
  };
  
  const toggleRoleSelection = (role: string) => {
    if (newTaskForm.assignedRoles.includes(role)) {
      setNewTaskForm({
        ...newTaskForm,
        assignedRoles: newTaskForm.assignedRoles.filter(r => r !== role)
      });
    } else {
      setNewTaskForm({
        ...newTaskForm,
        assignedRoles: [...newTaskForm.assignedRoles, role]
      });
    }
  };
  
  const handleCreateTask = () => {
    if (!newTaskForm.title.trim()) {
      toast.error('Le titre est requis');
      return;
    }
    
    if (newTaskForm.assignedRoles.length === 0) {
      toast.error('Veuillez sélectionner au moins un rôle');
      return;
    }
    
    const newTask: Task = {
      id: Math.random().toString(36).substr(2, 9),
      title: newTaskForm.title,
      description: newTaskForm.description,
      status: 'pending',
      assignedRoles: newTaskForm.assignedRoles
    };
    
    const updatedTasks = [...tasks, newTask];
    setTasks(updatedTasks);
    localStorage.setItem('tasks', JSON.stringify(updatedTasks));
    
    setShowNewTaskDialog(false);
    setNewTaskForm({
      title: '',
      description: '',
      assignedRoles: []
    });
    
    toast.success('Nouvelle tâche créée');
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
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
              Gestion des tâches
            </h1>
            <p className="text-gray-400 mt-1">
              Organisez et suivez vos tâches
            </p>
          </div>
          
          <div className="flex gap-3 items-center w-full md:w-auto">
            <div className="relative w-full md:w-64">
              <FuturisticInput
                type="text"
                placeholder="Rechercher une tâche..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
              <Search size={18} className="absolute left-3 top-3 text-gray-400" />
            </div>
            
            {(user.role === 'admin' || user.role === 'manager') && (
              <FuturisticButton 
                onClick={() => setShowNewTaskDialog(true)}
                className="flex items-center shrink-0"
              >
                <Plus size={18} className="mr-2" /> 
                Nouvelle tâche
              </FuturisticButton>
            )}
          </div>
        </div>
        
        <div className="mb-6 flex flex-wrap gap-3">
          <button 
            onClick={() => setFilterStatus('all')}
            className={`px-4 py-2 rounded-full text-sm transition-all ${
              filterStatus === 'all' 
                ? 'bg-futuristic-blue text-white' 
                : 'bg-white/10 hover:bg-white/20'
            }`}
          >
            Toutes
          </button>
          <button 
            onClick={() => setFilterStatus('pending')}
            className={`px-4 py-2 rounded-full text-sm transition-all ${
              filterStatus === 'pending' 
                ? 'bg-futuristic-blue text-white' 
                : 'bg-white/10 hover:bg-white/20'
            }`}
          >
            En cours
          </button>
          <button 
            onClick={() => setFilterStatus('completed')}
            className={`px-4 py-2 rounded-full text-sm transition-all ${
              filterStatus === 'completed' 
                ? 'bg-green-500 text-white' 
                : 'bg-white/10 hover:bg-white/20'
            }`}
          >
            Terminées
          </button>
        </div>
        
        <div className="futuristic-card">
          {filteredTasks.length === 0 ? (
            <div className="text-center py-12 text-gray-400">
              {searchQuery ? (
                <>
                  <div className="text-6xl mb-4">🔍</div>
                  <p>Aucune tâche ne correspond à votre recherche</p>
                </>
              ) : (
                <>
                  <div className="text-6xl mb-4">📝</div>
                  <p>Aucune tâche disponible</p>
                </>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              {filteredTasks.map(task => (
                <TaskCard
                  key={task.id}
                  task={task}
                  userRole={user.role}
                  onComplete={handleCompleteTask}
                  onDelete={handleDeleteTask}
                />
              ))}
            </div>
          )}
        </div>
      </main>
      
      {/* Modal pour créer une nouvelle tâche */}
      {showNewTaskDialog && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="futuristic-card w-full max-w-md animate-fade-in">
            <h2 className="text-xl font-semibold mb-4">Créer une nouvelle tâche</h2>
            
            <div className="space-y-4">
              <FuturisticInput
                type="text"
                label="Titre"
                placeholder="Titre de la tâche"
                value={newTaskForm.title}
                onChange={(e) => setNewTaskForm({...newTaskForm, title: e.target.value})}
                required
              />
              
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-200">Description</label>
                <textarea
                  className="futuristic-input w-full h-24 resize-none"
                  placeholder="Description détaillée de la tâche"
                  value={newTaskForm.description}
                  onChange={(e) => setNewTaskForm({...newTaskForm, description: e.target.value})}
                />
              </div>
              
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-200">Rôles assignés</label>
                <div className="flex flex-wrap gap-2">
                  {['admin', 'manager', 'user'].map(role => (
                    <button
                      key={role}
                      type="button"
                      onClick={() => toggleRoleSelection(role)}
                      className={`px-3 py-1.5 text-sm rounded-full transition-all ${
                        newTaskForm.assignedRoles.includes(role)
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
                onClick={() => setShowNewTaskDialog(false)}
              >
                Annuler
              </FuturisticButton>
              <FuturisticButton onClick={handleCreateTask}>
                Créer la tâche
              </FuturisticButton>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default TasksPage;
