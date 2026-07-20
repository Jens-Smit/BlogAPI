import React, { useState, useEffect } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Mail, Lock, Eye, EyeOff, Loader2 } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import api from '../services/api';

const LoginForm = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const { login: setAuthUser } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (location.state?.successMessage) {
      setSuccess(location.state.successMessage);
    }
  }, [location.state?.successMessage]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await api.post('/login', {
        email,
        password,
      });

      const isSuccessfulLogin = response.status >= 200 && response.status < 300 && !!response.data?.user;

      if (isSuccessfulLogin) {
        try {
          const userResponse = await api.get('/me');
          setAuthUser(userResponse.data);
        } catch (meError) {
          setAuthUser(response.data.user);
        }

        setSuccess(response.data.message || 'Login erfolgreich.');
        navigate('/dashboard', { replace: true });
      } else {
        setError('Ungültige Anmeldedaten. Bitte versuchen Sie es erneut.');
      }
    } catch (err) {
      console.error('Login-Fehler:', err);
      setError(
        err.response?.data?.error ||
        err.response?.data?.message ||
        'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      className="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden p-8"
    >
      <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 text-center">
        Anmelden
      </h2>

      {error && (
        <p className="text-red-600 dark:text-red-400 text-sm mb-4 text-center">{error}</p>
      )}

      {success && (
        <p className="text-green-600 dark:text-green-400 text-sm mb-4 text-center">{success}</p>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="relative">
          <Mail
            size={20}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
          />
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="E-Mail-Adresse"
            className="input-field pl-10"
            required
          />
        </div>

        <div className="relative">
          <Lock
            size={20}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
          />
          <input
            type={showPassword ? 'text' : 'password'}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Passwort"
            className="input-field pl-10 pr-10"
            required
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400"
          >
            {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
          </button>
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full btn-primary flex items-center justify-center gap-2"
        >
          {loading ? (
            <>
              <Loader2 size={20} className="animate-spin" />
              <span>Wird geladen...</span>
            </>
          ) : (
            'Anmelden'
          )}
        </button>
      </form>

      <div className="mt-6 text-center">
        <p className="text-gray-600 dark:text-gray-400 text-sm">
          Noch kein Konto?{' '}
          <Link
            to="/register"
            className="text-primary-600 dark:text-primary-400 hover:underline font-medium"
          >
            Registrieren
          </Link>
        </p>
      </div>
    </motion.div>
  );
};

export default LoginForm;