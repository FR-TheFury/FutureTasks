
import React from 'react';

const DemoAccounts: React.FC = () => {
  return (
    <div className="glass-panel p-4 text-sm">
      <div className="text-center mb-2 text-gray-300">Comptes de démonstration :</div>
      
      <div className="grid grid-cols-1 gap-2 mb-2">
        <div className="text-center font-semibold text-futuristic-blue">Comptes Entreprise</div>
      </div>
      
      <div className="grid grid-cols-3 gap-2 text-xs">
        <div>
          <div className="font-semibold text-futuristic-neon">Admin</div>
          <div>admin@example.com</div>
          <div>admin123</div>
        </div>
        <div>
          <div className="font-semibold text-futuristic-purple">Manager</div>
          <div>manager@example.com</div>
          <div>manager123</div>
        </div>
        <div>
          <div className="font-semibold text-futuristic-blue">Utilisateur</div>
          <div>user@example.com</div>
          <div>user123</div>
        </div>
      </div>
      
      <div className="mt-3 grid grid-cols-1 gap-2 mb-2">
        <div className="text-center font-semibold text-futuristic-purple">Comptes Partenaire</div>
      </div>
      
      <div className="grid grid-cols-2 gap-2 text-xs">
        <div>
          <div className="font-semibold text-futuristic-neon">Admin Partenaire</div>
          <div>partner-admin@example.com</div>
          <div>partner123</div>
        </div>
        <div>
          <div className="font-semibold text-futuristic-blue">Utilisateur Partenaire</div>
          <div>partner-user@example.com</div>
          <div>partner123</div>
        </div>
      </div>
    </div>
  );
};

export default DemoAccounts;
