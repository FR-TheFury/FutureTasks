
import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

export interface FuturisticInputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  icon?: React.ReactNode;
  error?: string;
}

const FuturisticInput = forwardRef<HTMLInputElement, FuturisticInputProps>(
  ({ className, type, label, icon, error, ...props }, ref) => {
    return (
      <div className="space-y-2 w-full">
        {label && (
          <label className="text-sm font-medium text-gray-200 flex items-center gap-2">
            {icon}
            {label}
          </label>
        )}
        <div className={cn(
          "relative group",
          error ? "animate-shake" : ""
        )}>
          <div className="absolute inset-0 rounded-md bg-gradient-to-r from-futuristic-blue to-futuristic-purple opacity-30 group-hover:opacity-40 blur-sm transition-opacity" />
          <input
            type={type}
            className={cn(
              "futuristic-input w-full transition-all duration-300 backdrop-blur-sm",
              "focus:translate-y-[-2px]",
              error ? "ring-2 ring-destructive" : "",
              className
            )}
            ref={ref}
            {...props}
          />
          <div className="h-[2px] bg-gradient-to-r from-transparent via-futuristic-neon to-transparent w-0 group-hover:w-full transition-all duration-300 mx-auto" />
        </div>
        {error && (
          <p className="text-destructive text-xs animate-fade-in">{error}</p>
        )}
      </div>
    );
  }
);

FuturisticInput.displayName = "FuturisticInput";

export default FuturisticInput;
