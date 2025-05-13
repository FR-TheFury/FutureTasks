
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navigation from '../components/Navigation';
import TaskCard, { Task } from '../components/TaskCard';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import { Plus } from 'lucide-react';
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

const Dashboard = () => {
  const [tasks, setTasks] = useState<Task[]>(MOCK_TASKS);
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
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
        // Chargé depuis notre mock
        const storedTasks = localStorage.getItem('tasks');
        setTasks(storedTasks ? JSON.parse(storedTasks) : MOCK_TASKS);
        setIsLoading(false);
      }, 800);
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
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
              Tableau de bord
            </h1>
            <p className="text-gray-400 mt-1">
              Bienvenue, {user.name} ({user.role})
            </p>
          </div>
          
          <FuturisticButton 
            onClick={() => navigate('/tasks/new')}
            className="flex items-center"
          >
            <Plus size={18} className="mr-2" /> 
            Nouvelle tâche
          </FuturisticButton>
        </div>
        
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Tâches en cours */}
          <div className="futuristic-card">
            <h2 className="text-xl font-semibold mb-4 flex items-center">
              <div className="h-3 w-3 rounded-full bg-futuristic-blue animate-pulse mr-2"></div>
              Tâches en cours
            </h2>
            
            {tasks.filter(task => task.status === 'pending').length === 0 ? (
              <div className="text-center py-8 text-gray-400">
                Aucune tâche en cours
              </div>
            ) : (
              <div className="space-y-4">
                {tasks
                  .filter(task => task.status === 'pending')
                  .map(task => (
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
          
          {/* Tâches terminées */}
          <div className="futuristic-card">
            <h2 className="text-xl font-semibold mb-4 flex items-center">
              <div className="h-3 w-3 rounded-full bg-green-500 animate-pulse mr-2"></div>
              Tâches terminées
            </h2>
            
            {tasks.filter(task => task.status === 'completed').length === 0 ? (
              <div className="text-center py-8 text-gray-400">
                Aucune tâche terminée
              </div>
            ) : (
              <div className="space-y-4">
                {tasks
                  .filter(task => task.status === 'completed')
                  .map(task => (
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
        </div>
      </main>
    </div>
  );
};

export default Dashboard;
