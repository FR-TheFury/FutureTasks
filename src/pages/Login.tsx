
import { useState } from 'react';
import ThreeBackground from '../components/ThreeBackground';

// Import refactored components
import LoginHeader from '../components/login/LoginHeader';
import LoginForm from '../components/login/LoginForm';
import PartnerRegistrationForm from '../components/login/PartnerRegistrationForm';
import DemoAccounts from '../components/login/DemoAccounts';

const Login = () => {
  const [showPartnerRegister, setShowPartnerRegister] = useState(false);
  
  return (
    <div className="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden">
      <ThreeBackground />
      
      <div className="w-full max-w-md space-y-8 relative z-10">
        {/* Logo/Header */}
        <LoginHeader />
        
        {!showPartnerRegister ? (
          /* Login Form */
          <LoginForm onShowPartnerRegister={() => setShowPartnerRegister(true)} />
        ) : (
          /* Partner Registration Form */
          <PartnerRegistrationForm onBack={() => setShowPartnerRegister(false)} />
        )}
        
        {/* Demo accounts */}
        <DemoAccounts />
      </div>
    </div>
  );
};

export default Login;
