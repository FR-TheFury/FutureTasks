
import { ButtonHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

export interface FuturisticButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'outline' | 'ghost' | 'neon';
  size?: 'sm' | 'md' | 'lg';
}

const FuturisticButton = forwardRef<HTMLButtonElement, FuturisticButtonProps>(
  ({ className, variant = 'default', size = 'md', children, ...props }, ref) => {
    return (
      <button
        className={cn(
          "relative inline-flex items-center justify-center font-medium transition-all duration-300 rounded-md outline-none",
          "hover:translate-y-[-2px] active:translate-y-[1px]",
          {
            // Size variations
            'text-sm px-3 py-1.5': size === 'sm',
            'text-base px-4 py-2': size === 'md',
            'text-lg px-6 py-3': size === 'lg',
            
            // Variant styles
            'bg-gradient-to-r from-futuristic-blue to-futuristic-purple text-white shadow-lg hover:shadow-futuristic-blue/30': 
              variant === 'default',
            'bg-transparent border border-futuristic-blue/60 text-futuristic-blue hover:bg-futuristic-blue/10': 
              variant === 'outline',
            'bg-transparent text-futuristic-blue hover:bg-futuristic-blue/10': 
              variant === 'ghost',
            'bg-black border border-futuristic-neon text-futuristic-neon shadow-[0_0_10px_rgba(0,255,234,0.4)] hover:shadow-[0_0_15px_rgba(0,255,234,0.6)]': 
              variant === 'neon',
          },
          className
        )}
        ref={ref}
        {...props}
      >
        {variant === 'neon' && (
          <span className="absolute inset-0 bg-futuristic-neon/10 rounded-md blur-sm" />
        )}
        <span className="relative">{children}</span>
      </button>
    );
  }
);

FuturisticButton.displayName = "FuturisticButton";

export default FuturisticButton;
