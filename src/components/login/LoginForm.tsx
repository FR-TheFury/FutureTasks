import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import FuturisticInput from '../FuturisticInput';
import FuturisticButton from '../FuturisticButton';
import { LogIn, User, Key, Building } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

interface LoginFormData {
  email: string;
  password: string;
  accountType: 'company' | 'partner';
}

interface LoginFormProps {
  onShowPartnerRegister: () => void;
}

const LoginForm = ({ onShowPartnerRegister }: LoginFormProps) => {
  const [formData, setFormData] = useState<LoginFormData>({
    email: '',
    password: '',
    accountType: 'company'
  });
  const [errors, setErrors] = useState<Partial<LoginFormData>>({});
  const [isLoading, setIsLoading] = useState(false);
  
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
    // Reset error when typing
    if (errors[name as keyof LoginFormData]) {
      setErrors({ ...errors, [name]: undefined });
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<LoginFormData> = {};
    if (!formData.email) {
      newErrors.email = 'Email requis';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email invalide';
    }

    if (!formData.password) {
      newErrors.password = 'Mot de passe requis';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setIsLoading(true);
    
    // Simuler le délai de connexion
    setTimeout(() => {
      // Choisir la base d'utilisateurs en fonction du type de compte
      const userDatabase = formData.accountType === 'company' ? MOCK_USERS : MOCK_PARTNER_USERS;
      
      const user = userDatabase.find(
        (u) => u.email === formData.email && u.password === formData.password
      );
      
      if (user) {
        // Stocker les informations de l'utilisateur dans localStorage
        localStorage.setItem('currentUser', JSON.stringify({
          id: user.id,
          email: user.email,
          name: user.name,
          role: user.role,
          accountType: formData.accountType // Ajouter le type de compte
        }));
        
        toast.success(`Bienvenue ${user.name} !`);
        navigate('/dashboard');
      } else {
        setErrors({ email: 'Email ou mot de passe incorrect' });
        toast.error('Échec de la connexion');
      }
      
      setIsLoading(false);
    }, 1000);
  };

  return (
    <div className="futuristic-card backdrop-blur-md">
      <form className="space-y-6" onSubmit={handleSubmit}>
        <div className="flex justify-center space-x-4 mb-4">
          <button
            type="button"
            className={`px-4 py-2 rounded-full transition-all ${
              formData.accountType === 'company'
                ? 'bg-futuristic-blue text-white'
                : 'bg-white/10 hover:bg-white/20 text-gray-300'
            }`}
            onClick={() => setFormData({...formData, accountType: 'company'})}
          >
            Entreprise
          </button>
          <button
            type="button"
            className={`px-4 py-2 rounded-full transition-all ${
              formData.accountType === 'partner'
                ? 'bg-futuristic-purple text-white'
                : 'bg-white/10 hover:bg-white/20 text-gray-300'
            }`}
            onClick={() => setFormData({...formData, accountType: 'partner'})}
          >
            Partenaire
          </button>
        </div>
        
        <FuturisticInput
          type="email"
          name="email"
          label="Email"
          icon={<User size={18} />}
          placeholder="votre@email.com"
          value={formData.email}
          onChange={handleChange}
          error={errors.email}
          autoComplete="email"
          required
        />
        
        <FuturisticInput
          type="password"
          name="password"
          label="Mot de passe"
          icon={<Key size={18} />}
          placeholder="••••••••"
          value={formData.password}
          onChange={handleChange}
          error={errors.password}
          autoComplete="current-password"
          required
        />
        
        <div className="!mt-8">
          <FuturisticButton
            type="submit"
            className="w-full"
            disabled={isLoading}
          >
            {isLoading ? (
              <div className="flex items-center justify-center">
                <div className="h-5 w-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                Connexion en cours...
              </div>
            ) : (
              <div className="flex items-center justify-center">
                <LogIn size={18} className="mr-2" />
                Se connecter
              </div>
            )}
          </FuturisticButton>
        </div>
        
        <div className="text-center text-sm pt-2">
          <button 
            type="button" 
            className="text-futuristic-blue hover:text-futuristic-neon transition-colors"
            onClick={() => navigate('/register')}
          >
            Vous n'avez pas de compte ? Inscrivez-vous
          </button>
        </div>
        
        {formData.accountType === 'partner' && (
          <div className="text-center text-sm pt-2">
            <button 
              type="button" 
              className="text-futuristic-purple hover:text-futuristic-neon transition-colors"
              onClick={onShowPartnerRegister}
            >
              <Building size={14} className="inline mr-1" />
              Créer un compte partenaire
            </button>
          </div>
        )}
      </form>
    </div>
  );
};

// Mock data moved from parent component
interface User {
  id: string;
  email: string;
  password: string;
  name: string;
  role: string;
}

// Simulons une base de données d'utilisateurs
const MOCK_USERS: User[] = [
  {
    id: '1',
    email: 'admin@example.com',
    password: 'admin123',
    name: 'Admin User',
    role: 'admin'
  },
  {
    id: '2',
    email: 'manager@example.com',
    password: 'manager123',
    name: 'Manager User',
    role: 'manager'
  },
  {
    id: '3',
    email: 'user@example.com',
    password: 'user123',
    name: 'Normal User',
    role: 'user'
  }
];

// Simulons une base de données pour les utilisateurs partenaires
const MOCK_PARTNER_USERS: User[] = [
  {
    id: 'p1',
    email: 'partner-admin@example.com',
    password: 'partner123',
    name: 'Partenaire Admin',
    role: 'admin'
  },
  {
    id: 'p2',
    email: 'partner-user@example.com',
    password: 'partner123',
    name: 'Partenaire User',
    role: 'user'
  }
];

export default LoginForm;
