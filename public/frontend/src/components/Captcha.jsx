import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { RefreshCw } from 'lucide-react';

const Captcha = ({ onVerify }) => {
  const [captchaImage, setCaptchaImage] = useState('');
  const [captchaText, setCaptchaText] = useState('');
  const [captchaId, setCaptchaId] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const fetchCaptcha = async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/captcha');
      setCaptchaImage(response.data.image);
      setCaptchaId(response.data.id);
      setError('');
    } catch (err) {
      console.error('Fehler beim Laden des Captchas:', err);
      setError('Fehler beim Laden des Captchas. Bitte versuchen Sie es erneut.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCaptcha();
  }, []);

  const handleVerify = async () => {
    if (!captchaText.trim()) {
      setError('Bitte geben Sie den Captcha-Text ein.');
      return;
    }

    try {
      const response = await api.post('/api/captcha/verify', {
        id: captchaId,
        text: captchaText,
      });

      if (response.data.success) {
        onVerify(true);
        setError('');
      } else {
        onVerify(false);
        setError('Ungültiger Captcha-Text. Bitte versuchen Sie es erneut.');
        fetchCaptcha();
        setCaptchaText('');
      }
    } catch (err) {
      console.error('Fehler bei der Captcha-Überprüfung:', err);
      onVerify(false);
      setError('Fehler bei der Captcha-Überprüfung. Bitte versuchen Sie es erneut.');
      fetchCaptcha();
      setCaptchaText('');
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    handleVerify();
  };

  return (
    <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Captcha-Verifizierung
        </h3>
        <button
          type="button"
          onClick={fetchCaptcha}
          disabled={loading}
          className="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50"
          title="Captcha neu laden"
        >
          <RefreshCw
            size={18}
            className={loading ? 'animate-spin' : ''}
          />
        </button>
      </div>

      {error && (
        <p className="text-red-600 dark:text-red-400 text-sm mb-4">{error}</p>
      )}

      <div className="flex flex-col sm:flex-row gap-4 items-center">
        <div className="flex-shrink-0">
          {captchaImage && (
            <img
              src={captchaImage}
              alt="Captcha"
              className="w-40 h-10 object-contain border border-gray-300 dark:border-gray-600 rounded"
            />
          )}
        </div>

        <form onSubmit={handleSubmit} className="w-full">
          <div className="flex flex-col sm:flex-row gap-2">
            <input
              type="text"
              value={captchaText}
              onChange={(e) => setCaptchaText(e.target.value)}
              placeholder="Captcha-Text eingeben"
              className="input-field flex-grow"
              disabled={loading}
            />
            <button
              type="submit"
              className="btn-primary whitespace-nowrap"
              disabled={loading}
            >
              Überprüfen
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Captcha;