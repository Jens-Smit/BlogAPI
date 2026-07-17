import React from 'react';
import { motion } from 'framer-motion';
import ContactForm from '../components/ContactForm';

const Contact = () => {
  return (
    <div className="min-h-screen flex items-center justify-center py-16">
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-2xl"
      >
        <motion.h1
          className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8 text-center"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          Kontakt
        </motion.h1>
        <ContactForm />
      </motion.div>
    </div>
  );
};

export default Contact;