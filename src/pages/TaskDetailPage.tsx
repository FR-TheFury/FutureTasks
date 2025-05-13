
import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Navigation from '../components/Navigation';
import ThreeBackground from '../components/ThreeBackground';
import FuturisticButton from '../components/FuturisticButton';
import { Calendar, Clock, User, Edit, Trash, CheckSquare, AlertTriangle, Info } from 'lucide-react';
import { toast } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';

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

const TaskDetailPage = () => {
  const { taskId } = useParams<{ taskId: string }>();
  const [task, setTask] = useState<Task | null>(null);
  const [currentUser, setCurrentUser] = useState<any>(null);
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
      const parsedUser = JSON.parse(storedUser);
      setCurrentUser(parsedUser);
      
      // Simuler le chargement de la tâche depuis une API
      setTimeout(() => {
        // Dans un vrai système, nous ferions un appel API pour obtenir les données de la tâche
        const storedTasks = localStorage.getItem('tasks') || '[]';
        const tasks = JSON.parse(storedTasks);
        const foundTask = tasks.find((t: Task) => t.id === taskId);
        
        if (foundTask) {
          setTask(foundTask);
        } else {
          toast.error('Tâche non trouvée');
          navigate('/tasks');
        }
        
        setIsLoading(false);
      }, 500);
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
  
  const handleEditTask = () => {
    navigate(`/tasks/edit/${taskId}`);
  };
  
  const handleDeleteTask = () => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
      try {
        const storedTasks = localStorage.getItem('tasks') || '[]';
        const tasks = JSON.parse(storedTasks);
        const updatedTasks = tasks.filter((t: Task) => t.id !== taskId);
        
        localStorage.setItem('tasks', JSON.stringify(updatedTasks));
        toast.success('Tâche supprimée avec succès');
        navigate('/tasks');
      } catch (error) {
        console.error('Error deleting task:', error);
        toast.error('Erreur lors de la suppression de la tâche');
      }
    }
  };
  
  const handleUpdateStatus = (status: Task['status']) => {
    try {
      const storedTasks = localStorage.getItem('tasks') || '[]';
      const tasks = JSON.parse(storedTasks);
      const updatedTasks = tasks.map((t: Task) => {
        if (t.id === taskId) {
          return { ...t, status, updated_at: new Date().toISOString() };
        }
        return t;
      });
      
      localStorage.setItem('tasks', JSON.stringify(updatedTasks));
      setTask(prev => prev ? { ...prev, status, updated_at: new Date().toISOString() } : null);
      toast.success(`Statut mis à jour : ${getStatusLabel(status)}`);
    } catch (error) {
      console.error('Error updating task status:', error);
      toast.error('Erreur lors de la mise à jour du statut');
    }
  };
  
  const getStatusLabel = (status: Task['status']) => {
    switch (status) {
      case 'pending': return 'En attente';
      case 'in_progress': return 'En cours';
      case 'completed': return 'Terminée';
      case 'cancelled': return 'Annulée';
      default: return status;
    }
  };
  
  const getStatusColor = (status: Task['status']) => {
    switch (status) {
      case 'pending': return 'bg-futuristic-blue/20 text-futuristic-blue';
      case 'in_progress': return 'bg-futuristic-purple/20 text-futuristic-purple';
      case 'completed': return 'bg-green-500/20 text-green-500';
      case 'cancelled': return 'bg-red-500/20 text-red-500';
      default: return 'bg-gray-500/20 text-gray-500';
    }
  };
  
  const getPriorityLabel = (priority: Task['priority']) => {
    switch (priority) {
      case 'low': return 'Faible';
      case 'medium': return 'Moyenne';
      case 'high': return 'Élevée';
      case 'urgent': return 'Urgente';
      default: return priority;
    }
  };
  
  const getPriorityColor = (priority: Task['priority']) => {
    switch (priority) {
      case 'low': return 'bg-green-500/20 text-green-500';
      case 'medium': return 'bg-blue-500/20 text-blue-500';
      case 'high': return 'bg-orange-500/20 text-orange-500';
      case 'urgent': return 'bg-red-500/20 text-red-500';
      default: return 'bg-gray-500/20 text-gray-500';
    }
  };
  
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  };
  
  const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
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
        <div className="max-w-4xl mx-auto">
          <div className="mb-6">
            <button 
              onClick={() => navigate('/tasks')}
              className="flex items-center text-gray-400 hover:text-white transition-colors"
            >
              <span className="mr-2">←</span>
              Retour aux tâches
            </button>
          </div>
          
          <div className="futuristic-card">
            <div className="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6">
              <div>
                <h1 className="text-2xl md:text-3xl font-bold text-white mb-2">
                  {task.title}
                </h1>
                
                <div className="flex flex-wrap items-center gap-3 my-3">
                  <span className={cn("px-3 py-1 rounded-full text-xs", getStatusColor(task.status))}>
                    {getStatusLabel(task.status)}
                  </span>
                  
                  <span className={cn("px-3 py-1 rounded-full text-xs", getPriorityColor(task.priority))}>
                    Priorité: {getPriorityLabel(task.priority)}
                  </span>
                  
                  <span className="flex items-center text-gray-400 text-sm">
                    <Calendar size={14} className="mr-1" />
                    Échéance: {formatDate(task.due_date)}
                  </span>
                </div>
              </div>
              
              {(currentUser.role === 'admin' || currentUser.role === 'manager') && (
                <div className="flex gap-2">
                  <FuturisticButton 
                    variant="ghost" 
                    onClick={handleEditTask}
                  >
                    <Edit size={16} className="mr-1" /> Modifier
                  </FuturisticButton>
                  
                  <FuturisticButton 
                    variant="ghost"
                    onClick={handleDeleteTask}
                    className="text-red-500 hover:text-red-400"
                  >
                    <Trash size={16} className="mr-1" /> Supprimer
                  </FuturisticButton>
                </div>
              )}
            </div>
            
            <div className="border-t border-white/10 pt-6 mb-6">
              <h2 className="text-lg font-semibold mb-3 flex items-center">
                <Info size={18} className="mr-2 text-futuristic-blue" />
                Description
              </h2>
              <div className="bg-white/5 p-4 rounded-md text-gray-200">
                {task.description || 'Aucune description fournie.'}
              </div>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <h2 className="text-lg font-semibold mb-3 flex items-center">
                  <User size={18} className="mr-2 text-futuristic-purple" />
                  Assigné à
                </h2>
                <div className="bg-white/5 p-4 rounded-md flex items-center">
                  <div className="h-10 w-10 rounded-full bg-gradient-to-br from-futuristic-blue to-futuristic-purple flex items-center justify-center mr-3">
                    <User size={20} className="text-white" />
                  </div>
                  <div>
                    <p className="text-white">{task.assigned_to_name}</p>
                    <p className="text-xs text-gray-400">ID: {task.assigned_to}</p>
                  </div>
                </div>
              </div>
              
              <div>
                <h2 className="text-lg font-semibold mb-3 flex items-center">
                  <Clock size={18} className="mr-2 text-futuristic-neon" />
                  Informations temporelles
                </h2>
                <div className="bg-white/5 p-4 rounded-md space-y-2">
                  <p className="text-sm flex justify-between">
                    <span className="text-gray-400">Créée le:</span>
                    <span className="text-white">{formatDateTime(task.created_at)}</span>
                  </p>
                  <p className="text-sm flex justify-between">
                    <span className="text-gray-400">Dernière mise à jour:</span>
                    <span className="text-white">{formatDateTime(task.updated_at)}</span>
                  </p>
                  <p className="text-sm flex justify-between">
                    <span className="text-gray-400">Date d'échéance:</span>
                    <span className="text-white">{formatDate(task.due_date)}</span>
                  </p>
                </div>
              </div>
            </div>
            
            <div className="border-t border-white/10 pt-6">
              <h2 className="text-lg font-semibold mb-4">Mettre à jour le statut</h2>
              <div className="flex flex-wrap gap-2">
                <FuturisticButton 
                  variant={task.status === 'pending' ? 'default' : 'outline'} 
                  onClick={() => handleUpdateStatus('pending')}
                  size="sm"
                >
                  En attente
                </FuturisticButton>
                
                <FuturisticButton 
                  variant={task.status === 'in_progress' ? 'default' : 'outline'} 
                  onClick={() => handleUpdateStatus('in_progress')}
                  size="sm"
                >
                  En cours
                </FuturisticButton>
                
                <FuturisticButton 
                  variant={task.status === 'completed' ? 'default' : 'outline'} 
                  onClick={() => handleUpdateStatus('completed')}
                  size="sm"
                  className={task.status === 'completed' ? 'bg-green-600 hover:bg-green-700' : ''}
                >
                  <CheckSquare size={16} className="mr-1" /> Terminée
                </FuturisticButton>
                
                <FuturisticButton 
                  variant={task.status === 'cancelled' ? 'default' : 'outline'} 
                  onClick={() => handleUpdateStatus('cancelled')}
                  size="sm"
                  className={task.status === 'cancelled' ? 'bg-red-600 hover:bg-red-700' : ''}
                >
                  Annulée
                </FuturisticButton>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default TaskDetailPage;
