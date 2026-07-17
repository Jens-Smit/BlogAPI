import React from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';

const HeroSection = () => {
  return (
    <section className="relative bg-gradient-to-r from-primary-500 to-purple-600 text-white py-20 lg:py-32">
      <div className="absolute inset-0 bg-black/20"></div>
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <motion.h1
          className="text-4xl md:text-6xl font-bold mb-6"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8 }}
        >
          Willkommen bei BlogAPI
        </motion.h1>

        <motion.p
          className="text-xl md:text-2xl mb-10 max-w-3xl mx-auto"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.2 }}
        >
          Ein moderner Blog, der mit React und Symfony erstellt wurde. 
          Entdecke spannende Artikel und teile deine eigenen Geschichten.
        </motion.p>

        <motion.div
          className="flex flex-col sm:flex-row gap-4 justify-center"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.4 }}
        >
          <Link to="/blog" className="btn-primary px-8 py-3 text-lg">
            Blog durchsuchen
          </Link>
          <Link to="/create-post" className="btn-secondary px-8 py-3 text-lg">
            Jetzt schreiben
          </Link>
        </motion.div>
      </div>
    </section>
  );
};

export default HeroSection;