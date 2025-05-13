
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import FuturisticInput from '../components/FuturisticInput';
import FuturisticButton from '../components/FuturisticButton';
import ThreeBackground from '../components/ThreeBackground';
import { UserPlus, Mail, User, Key } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

interface RegisterFormData {
  name: string;
  email: string;
  password: string;
  confirmPassword: string;
}

const Register = () => {
  const [formData, setFormData] = useState<RegisterFormData>({
    name: '',
    email: '',
    password: '',
    confirmPassword: ''
  });
  const [errors, setErrors] = useState<Partial<RegisterFormData>>({});
  const [isLoading, setIsLoading] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
    // Reset error when typing
    if (errors[name as keyof RegisterFormData]) {
      setErrors({ ...errors, [name]: undefined });
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<RegisterFormData> = {};
    
    if (!formData.name.trim()) {
      newErrors.name = 'Nom requis';
    }
    
    if (!formData.email) {
      newErrors.email = 'Email requis';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email invalide';
    }

    if (!formData.password) {
      newErrors.password = 'Mot de passe requis';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Mot de passe trop court (min. 6 caractères)';
    }
    
    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Les mots de passe ne correspondent pas';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setIsLoading(true);
    
    // Simuler l'enregistrement
    setTimeout(() => {
      // En pratique, nous enverrions ces données à une API
      const newUser = {
        id: Math.random().toString(36).substr(2, 9),
        name: formData.name,
        email: formData.email,
        role: 'user' // Par défaut, un nouvel utilisateur a le rôle "user"
      };
      
      // Stockons les utilisateurs dans localStorage pour simuler une base de données
      const users = JSON.parse(localStorage.getItem('users') || '[]');
      users.push({...newUser, password: formData.password});
      localStorage.setItem('users', JSON.stringify(users));
      
      toast.success('Compte créé avec succès !');
      navigate('/login');
      
      setIsLoading(false);
    }, 1500);
  };

  return (
    <div className="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden">
      <ThreeBackground />
      
      <div className="w-full max-w-md space-y-8 relative z-10">
        {/* Logo/Header */}
        <div className="text-center">
          <div className="flex items-center justify-center space-x-2">
            <div className="h-12 w-12 rounded-full bg-gradient-to-br from-futuristic-blue to-futuristic-purple flex items-center justify-center shadow-lg">
              <UserPlus size={24} className="text-white" />
            </div>
          </div>
          <h1 className="mt-4 text-3xl font-bold bg-gradient-to-r from-futuristic-blue to-futuristic-purple bg-clip-text text-transparent">
            Créer un compte
          </h1>
          <p className="mt-2 text-gray-400">Rejoignez FutureTasks pour gérer vos tâches</p>
        </div>
        
        {/* Registration Form */}
        <div className="futuristic-card backdrop-blur-md">
          <form className="space-y-6" onSubmit={handleSubmit}>
            <FuturisticInput
              type="text"
              name="name"
              label="Nom"
              icon={<User size={18} />}
              placeholder="Votre nom"
              value={formData.name}
              onChange={handleChange}
              error={errors.name}
              autoComplete="name"
              required
            />
            
            <FuturisticInput
              type="email"
              name="email"
              label="Email"
              icon={<Mail size={18} />}
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
              autoComplete="new-password"
              required
            />
            
            <FuturisticInput
              type="password"
              name="confirmPassword"
              label="Confirmer le mot de passe"
              icon={<Key size={18} />}
              placeholder="••••••••"
              value={formData.confirmPassword}
              onChange={handleChange}
              error={errors.confirmPassword}
              autoComplete="new-password"
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
                    Création en cours...
                  </div>
                ) : (
                  <div className="flex items-center justify-center">
                    <UserPlus size={18} className="mr-2" />
                    S'inscrire
                  </div>
                )}
              </FuturisticButton>
            </div>
            
            <div className="text-center text-sm pt-2">
              <button 
                type="button" 
                className="text-futuristic-blue hover:text-futuristic-neon transition-colors"
                onClick={() => navigate('/login')}
              >
                Vous avez déjà un compte ? Connectez-vous
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default Register;
