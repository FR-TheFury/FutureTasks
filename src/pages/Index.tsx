
import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import ThreeBackground from "../components/ThreeBackground";
import FuturisticButton from "../components/FuturisticButton";

const Index = () => {
  const navigate = useNavigate();
  
  useEffect(() => {
    // Rediriger vers la page de connexion après un court délai
    const timer = setTimeout(() => {
      navigate("/login");
    }, 3000);
    
    return () => clearTimeout(timer);
  }, [navigate]);

  return (
    <div className="min-h-screen flex flex-col items-center justify-center relative">
      <ThreeBackground />
      
      <div className="text-center space-y-6 relative z-10 animate-fade-in">
        <h1 className="text-5xl font-bold bg-gradient-to-r from-futuristic-blue via-futuristic-purple to-futuristic-pink bg-clip-text text-transparent">
          FutureTasks
        </h1>
        <p className="text-xl text-gray-300 max-w-lg">
          Système de gestion des tâches avec contrôle d'accès basé sur les rôles
        </p>
        
        <div className="flex gap-4 justify-center mt-8">
          <FuturisticButton onClick={() => navigate("/login")}>
            Connexion
          </FuturisticButton>
          <FuturisticButton variant="outline" onClick={() => navigate("/register")}>
            Inscription
          </FuturisticButton>
        </div>
      </div>
    </div>
  );
};

export default Index;
