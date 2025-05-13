
import { useState } from 'react';
import { Check, Edit, Trash } from 'lucide-react';
import FuturisticButton from './FuturisticButton';
import { cn } from '@/lib/utils';

export interface Task {
  id: string;
  title: string;
  description: string;
  status: 'pending' | 'completed';
  assignedRoles: string[];
}

interface TaskCardProps {
  task: Task;
  userRole: string;
  onComplete: (id: string) => void;
  onDelete: (id: string) => void;
  onEdit?: (task: Task) => void;
}

const TaskCard: React.FC<TaskCardProps> = ({ task, userRole, onComplete, onDelete, onEdit }) => {
  const [isHovered, setIsHovered] = useState(false);
  const canModify = task.assignedRoles.includes(userRole) || userRole === 'admin';
  
  return (
    <div
      className={cn(
        "futuristic-card group transition-all duration-300 mb-4",
        task.status === 'completed' ? "opacity-70" : "",
        isHovered ? "translate-y-[-3px]" : ""
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className={cn(
        "absolute top-0 left-0 w-1 h-full rounded-l-xl transition-colors",
        task.status === 'completed' ? "bg-green-500" : "bg-futuristic-blue"
      )} />
      
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <h3 className={cn(
            "text-lg font-semibold transition-all",
            task.status === 'completed' ? "line-through text-gray-400" : ""
          )}>
            {task.title}
          </h3>
          <div className="flex gap-2">
            {task.status === 'pending' && canModify && (
              <>
                <FuturisticButton 
                  variant="outline" 
                  size="sm" 
                  onClick={() => onComplete(task.id)}
                  className="opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <Check size={16} />
                </FuturisticButton>
                {onEdit && (
                  <FuturisticButton 
                    variant="outline" 
                    size="sm" 
                    onClick={() => onEdit(task)}
                    className="opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <Edit size={16} />
                  </FuturisticButton>
                )}
              </>
            )}
            {(userRole === 'admin' || userRole === 'manager') && (
              <FuturisticButton 
                variant="outline" 
                size="sm" 
                onClick={() => onDelete(task.id)}
                className="text-destructive border-destructive opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Trash size={16} />
              </FuturisticButton>
            )}
          </div>
        </div>
        
        <p className={cn(
          "text-gray-300 text-sm",
          task.status === 'completed' ? "text-gray-500" : ""
        )}>
          {task.description}
        </p>
        
        <div className="pt-2 flex items-center justify-between">
          <div className="flex gap-2">
            {task.assignedRoles.map((role) => (
              <span 
                key={role} 
                className="text-xs py-1 px-2 rounded-full bg-white/10 text-gray-300"
              >
                {role}
              </span>
            ))}
          </div>
          
          {!canModify && (
            <span className="text-xs text-gray-400">
              Accès limité
            </span>
          )}
        </div>
      </div>
    </div>
  );
};

export default TaskCard;
