import React, { createContext, useContext, useState, useEffect } from 'react';
import { getCurrentUser, logout } from '../services/auth';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [theme, setTheme] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('theme') || 'light';
    }
    return 'light';
  });

  useEffect(() => {
    const checkAuth = async () => {
      try {
        // Wir verlassen uns bewusst NICHT mehr auf ein localStorage-Flag,
        // sondern fragen die HttpOnly-Cookies direkt beim Backend ab.
        // Grund: Das Flag konnte durch andere Login-Wege (z.B. LoginForm)
        // unbemerkt out-of-sync geraten, was nach einem Reload faelschlich
        // zum Logout fuehrte, obwohl die Cookies noch gueltig waren.
        const userData = await getCurrentUser();
        setUser(userData);
        localStorage.setItem('is_logged_in', 'true');
      } catch (error) {
        console.error('Auth check failed:', error);
        localStorage.removeItem('is_logged_in');
      } finally {
        setLoading(false);
      }
    };
    checkAuth();
  }, []);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('theme', theme);
      document.documentElement.classList.toggle('dark', theme === 'dark');
    }
  }, [theme]);

  const toggleTheme = () => {
    setTheme(prev => prev === 'light' ? 'dark' : 'light');
  };

  const handleLogin = (userData) => {
    setUser(userData);
  };

  const handleLogout = async () => {
    await logout();
    setUser(null);
  };

  const value = {
    user,
    loading,
    isAuthenticated: !!user,
    login: handleLogin,
    logout: handleLogout,
    theme,
    toggleTheme,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};