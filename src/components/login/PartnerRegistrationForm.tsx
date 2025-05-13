
import { useState } from 'react';
import FuturisticInput from '../FuturisticInput';
import FuturisticButton from '../FuturisticButton';
import { UserPlus, User, Key, Building } from 'lucide-react';
import { toast } from '@/components/ui/sonner';

interface PartnerRegistrationFormProps {
  onBack: () => void;
}

const PartnerRegistrationForm = ({ onBack }: PartnerRegistrationFormProps) => {
  const [isLoading, setIsLoading] = useState(false);
  const [partnerFormData, setPartnerFormData] = useState({
    companyName: '',
    name: '',
    email: '',
    password: '',
    confirmPassword: ''
  });
  
  const handlePartnerFormChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setPartnerFormData({ ...partnerFormData, [name]: value });
  };
  
  const validatePartnerForm = (): boolean => {
    if (!partnerFormData.companyName.trim()) {
      toast.error('Nom de la société requis');
      return false;
    }
    
    if (!partnerFormData.name.trim()) {
      toast.error('Nom requis');
      return false;
    }
    
    if (!partnerFormData.email.trim() || !/\S+@\S+\.\S+/.test(partnerFormData.email)) {
      toast.error('Email invalide');
      return false;
    }
    
    if (!partnerFormData.password) {
      toast.error('Mot de passe requis');
      return false;
    }
    
    if (partnerFormData.password.length < 6) {
      toast.error('Le mot de passe doit comporter au moins 6 caractères');
      return false;
    }
    
    if (partnerFormData.password !== partnerFormData.confirmPassword) {
      toast.error('Les mots de passe ne correspondent pas');
      return false;
    }
    
    return true;
  };
  
  const handlePartnerRegister = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validatePartnerForm()) return;
    
    setIsLoading(true);
    
    // Simuler l'enregistrement d'un partenaire
    setTimeout(() => {
      // Dans un système réel, nous enverrions ces données à une API
      const newPartner = {
        id: `p${Math.random().toString(36).substr(2, 9)}`,
        name: partnerFormData.name,
        email: partnerFormData.email,
        password: partnerFormData.password,
        companyName: partnerFormData.companyName,
        role: 'admin', // Par défaut, le premier utilisateur d'un partenaire est admin
        accountType: 'partner'
      };
      
      // Stocker les partenaires dans localStorage pour simuler une base de données
      const partners = JSON.parse(localStorage.getItem('partner_users') || '[]');
      partners.push(newPartner);
      localStorage.setItem('partner_users', JSON.stringify(partners));
      
      toast.success('Compte partenaire créé avec succès !');
      onBack();
      
      setIsLoading(false);
    }, 1500);
  };

  return (
    <div className="futuristic-card backdrop-blur-md">
      <h2 className="text-xl font-bold text-white mb-4 flex items-center">
        <Building size={20} className="mr-2 text-futuristic-purple" />
        Création de compte partenaire
      </h2>
      
      <form className="space-y-6" onSubmit={handlePartnerRegister}>
        <FuturisticInput
          type="text"
          name="companyName"
          label="Nom de la société"
          icon={<Building size={18} />}
          placeholder="Nom de votre entreprise"
          value={partnerFormData.companyName}
          onChange={handlePartnerFormChange}
          required
        />
        
        <FuturisticInput
          type="text"
          name="name"
          label="Nom du responsable"
          icon={<User size={18} />}
          placeholder="Votre nom"
          value={partnerFormData.name}
          onChange={handlePartnerFormChange}
          required
        />
        
        <FuturisticInput
          type="email"
          name="email"
          label="Email professionnel"
          icon={<User size={18} />}
          placeholder="votre@email.com"
          value={partnerFormData.email}
          onChange={handlePartnerFormChange}
          required
        />
        
        <FuturisticInput
          type="password"
          name="password"
          label="Mot de passe"
          icon={<Key size={18} />}
          placeholder="••••••••"
          value={partnerFormData.password}
          onChange={handlePartnerFormChange}
          required
        />
        
        <FuturisticInput
          type="password"
          name="confirmPassword"
          label="Confirmer le mot de passe"
          icon={<Key size={18} />}
          placeholder="••••••••"
          value={partnerFormData.confirmPassword}
          onChange={handlePartnerFormChange}
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
                Créer le compte partenaire
              </div>
            )}
          </FuturisticButton>
        </div>
        
        <div className="text-center text-sm pt-2">
          <button 
            type="button" 
            className="text-futuristic-blue hover:text-futuristic-neon transition-colors"
            onClick={onBack}
          >
            Retour à la connexion
          </button>
        </div>
      </form>
    </div>
  );
};

export default PartnerRegistrationForm;
