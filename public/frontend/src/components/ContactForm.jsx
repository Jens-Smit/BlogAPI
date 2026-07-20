import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { User, Mail, MessageSquare, Loader2 } from 'lucide-react';
import api from '../services/api';
import Captcha from './Captcha';

const ContactForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    message: '',
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const [captchaVerified, setCaptchaVerified] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    setError('');
    setSuccess('');

    if (!captchaVerified) {
      setError('Bitte bestätigen Sie das Captcha.');
      return;
    }

    setLoading(true);

    try {
      const response = await api.post('/contact', {
        name: formData.name,
        email: formData.email,
        message: formData.message,
      });

      if (response.data.success) {
        setSuccess('Ihre Nachricht wurde erfolgreich gesendet!');
        setFormData({ name: '', email: '', message: '' });
        setCaptchaVerified(false);
      } else {
        setError(
          response.data.message ||
          'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
        );
      }
    } catch (err) {
      console.error('Fehler beim Senden der Nachricht:', err);
      setError(
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
        Kontaktformular
      </h2>

      {error && (
        <p className="text-red-600 dark:text-red-400 text-sm mb-4 text-center">{error}</p>
      )}

      {success && (
        <p className="text-green-600 dark:text-green-400 text-sm mb-4 text-center">{success}</p>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="relative">
          <User
            size={20}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
          />
          <input
            type="text"
            name="name"
            value={formData.name}
            onChange={handleChange}
            placeholder="Ihr Name"
            className="input-field pl-10"
            required
          />
        </div>

        <div className="relative">
          <Mail
            size={20}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
          />
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="Ihre E-Mail-Adresse"
            className="input-field pl-10"
            required
          />
        </div>

        <div className="relative">
          <MessageSquare
            size={20}
            className="absolute left-3 top-5 text-gray-400 dark:text-gray-500"
          />
          <textarea
            name="message"
            value={formData.message}
            onChange={handleChange}
            placeholder="Ihre Nachricht"
            rows={5}
            className="input-field pl-10 resize-none"
            required
          />
        </div>

        <Captcha onVerify={setCaptchaVerified} />

        <button
          type="submit"
          disabled={loading || !captchaVerified}
          className="w-full btn-primary flex items-center justify-center gap-2 disabled:opacity-50"
        >
          {loading ? (
            <>
              <Loader2 size={20} className="animate-spin" />
              <span>Wird gesendet...</span>
            </>
          ) : (
            'Nachricht senden'
          )}
        </button>
      </form>
    </motion.div>
  );
};

export default ContactForm;