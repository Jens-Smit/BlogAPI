import React from 'react';
import { motion } from 'framer-motion';
import LoginForm from '../components/LoginForm';

const Login = () => {
  return (
    <div className="min-h-screen flex items-center justify-center py-16">
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md"
      >
        <LoginForm />
      </motion.div>
    </div>
  );
};

export default Login;