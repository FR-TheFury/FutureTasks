
import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { Home, ListTodo, Users, Settings, LogOut } from 'lucide-react';
import FuturisticButton from './FuturisticButton';

interface NavigationProps {
  userRole?: string;
  onLogout: () => void;
}

interface NavItem {
  name: string;
  path: string;
  icon: React.ReactNode;
  allowedRoles: string[];
}

const Navigation: React.FC<NavigationProps> = ({ userRole = 'user', onLogout }) => {
  const [isOpen, setIsOpen] = useState(false);
  const location = useLocation();
  
  const navItems: NavItem[] = [
    { 
      name: 'Accueil', 
      path: '/dashboard', 
      icon: <Home size={20} />, 
      allowedRoles: ['user', 'manager', 'admin'] 
    },
    { 
      name: 'Tâches', 
      path: '/tasks', 
      icon: <ListTodo size={20} />, 
      allowedRoles: ['user', 'manager', 'admin'] 
    },
    { 
      name: 'Utilisateurs', 
      path: '/users', 
      icon: <Users size={20} />, 
      allowedRoles: ['admin', 'manager'] 
    },
    { 
      name: 'Paramètres', 
      path: '/settings', 
      icon: <Settings size={20} />, 
      allowedRoles: ['admin'] 
    },
  ];
  
  return (
    <div className="sticky top-0 z-40 w-full backdrop-blur-md bg-background/80 border-b border-border/50">
      <div className="container mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="text-xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
              FutureTasks
            </div>
            <div className="h-2 w-2 rounded-full bg-futuristic-neon animate-pulse-neon ml-1"></div>
          </div>
          
          <nav className="hidden md:flex items-center space-x-4">
            {navItems
              .filter(item => item.allowedRoles.includes(userRole))
              .map(item => (
                <Link
                  key={item.path}
                  to={item.path}
                  className={cn(
                    "px-3 py-2 rounded-md flex items-center gap-2 transition-all duration-300",
                    "hover:bg-white/10",
                    location.pathname === item.path 
                      ? "text-futuristic-neon font-medium" 
                      : "text-gray-300"
                  )}
                >
                  {item.icon}
                  {item.name}
                  {location.pathname === item.path && (
                    <div className="absolute bottom-0 left-0 h-[2px] w-full bg-futuristic-neon"></div>
                  )}
                </Link>
            ))}
            
            <div className="ml-4">
              <FuturisticButton 
                variant="outline" 
                size="sm"
                onClick={onLogout}
                className="flex items-center gap-2"
              >
                <LogOut size={16} />
                Déconnexion
              </FuturisticButton>
            </div>
          </nav>
          
          <div className="flex md:hidden">
            <button 
              onClick={() => setIsOpen(!isOpen)}
              className="text-gray-300 hover:text-white"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" className="w-6 h-6">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={isOpen ? "M6 18L18 6M6 6l12 12" : "M4 6h16M4 12h16M4 18h16"} />
              </svg>
            </button>
          </div>
        </div>
      </div>
      
      {isOpen && (
        <div className="md:hidden glass-panel animate-fade-in mt-2 mx-4 py-3 px-2">
          {navItems
            .filter(item => item.allowedRoles.includes(userRole))
            .map(item => (
              <Link
                key={item.path}
                to={item.path}
                onClick={() => setIsOpen(false)}
                className={cn(
                  "flex items-center gap-3 px-4 py-2 my-1 rounded-md",
                  location.pathname === item.path 
                    ? "bg-white/10 text-futuristic-neon" 
                    : "text-gray-300 hover:bg-white/5"
                )}
              >
                {item.icon}
                {item.name}
              </Link>
          ))}
          
          <button
            onClick={() => {
              setIsOpen(false);
              onLogout();
            }}
            className="flex items-center gap-3 px-4 py-2 my-1 w-full text-left rounded-md text-gray-300 hover:bg-white/5"
          >
            <LogOut size={20} />
            Déconnexion
          </button>
        </div>
      )}
    </div>
  );
};

export default Navigation;
