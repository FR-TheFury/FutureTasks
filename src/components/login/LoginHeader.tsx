
import { User } from 'lucide-react';

const LoginHeader: React.FC = () => {
  return (
    <div className="text-center">
      <div className="flex items-center justify-center space-x-2">
        <div className="h-12 w-12 rounded-full bg-gradient-to-br from-futuristic-blue to-futuristic-purple flex items-center justify-center shadow-lg">
          <User size={24} className="text-white" />
        </div>
      </div>
      <h1 className="mt-4 text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
        FutureTasks
      </h1>
      <p className="mt-2 text-gray-400">Connectez-vous pour accéder à votre espace</p>
    </div>
  );
};

export default LoginHeader;
