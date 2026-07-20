import React, { useState, useEffect, useRef } from 'react';
import api from '../services/api';
import { RefreshCw } from 'lucide-react';

let hasLoadedCaptchaOnce = false;

const Captcha = ({ onVerify }) => {
  const [captchaImages, setCaptchaImages] = useState([]);
  const [clickCounts, setClickCounts] = useState([]);
  const [initialRotations, setInitialRotations] = useState([]);
  const [captchaId, setCaptchaId] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [verified, setVerified] = useState(false);
  const requestInFlightRef = useRef(false);

  const fetchCaptcha = async (forceRefresh = false) => {
    if (requestInFlightRef.current) {
      return;
    }

    if (!forceRefresh && hasLoadedCaptchaOnce) {
      return;
    }

    requestInFlightRef.current = true;
    setLoading(true);

    try {
      const response = await api.get('/captcha/generate');
      setCaptchaImages(response.data.imageParts || []);
      setClickCounts(Array.from({ length: response.data.imageParts?.length || 4 }, () => 0));
      setInitialRotations(response.data.initialRotations || []);
      setCaptchaId(response.data.captchaId || '');
      setVerified(false);
      setError('');
      hasLoadedCaptchaOnce = true;
    } catch (err) {
      console.error('Fehler beim Laden des Captchas:', err);
      setError('Fehler beim Laden des Captchas. Bitte versuchen Sie es erneut.');
    } finally {
      requestInFlightRef.current = false;
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCaptcha(false);
  }, []);

  const handlePieceClick = (index) => {
    setClickCounts((prev) => {
      const next = [...prev];
      next[index] = (next[index] + 1) % 8;
      return next;
    });
  };

  const handleVerify = async () => {
    if (!captchaId) {
      setError('Captcha wurde noch nicht geladen.');
      return;
    }

    try {
      const response = await api.post('/captcha/verify', {
        captchaId,
        userClicks: clickCounts,
      });

      if (response.data.success) {
        setVerified(true);
        onVerify(true);
        setError('');
      } else {
        setVerified(false);
        onVerify(false);
        setError('Die Teile passen noch nicht zusammen. Bitte versuchen Sie es erneut.');
        fetchCaptcha(true);
      }
    } catch (err) {
      console.error('Fehler bei der Captcha-Überprüfung:', err);
      onVerify(false);
      setError('Fehler bei der Captcha-Überprüfung. Bitte versuchen Sie es erneut.');
      fetchCaptcha(true);
    }
  };

  return (
    <div className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Captcha-Verifizierung
        </h3>
        <button
          type="button"
          onClick={() => fetchCaptcha(true)}
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

      <div className="flex flex-col gap-4">
        {!verified && (
          <>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Klicke auf die Teile, um sie schrittweise zurückzudrehen, bis sie zusammenpassen.
            </p>

            <div className="grid grid-cols-2 gap-2 w-full max-w-md">
              {captchaImages.map((image, index) => (
                <button
                  key={`${captchaId}-${index}`}
                  type="button"
                  onClick={() => handlePieceClick(index)}
                  className="p-1 rounded border border-gray-300 dark:border-gray-600 bg-white hover:bg-gray-50 dark:hover:bg-gray-700"
                  aria-label={`Captcha Teil ${index + 1} drehen`}
                >
                  <img
                    src={image}
                    alt={`Captcha Teil ${index + 1}`}
                    className="w-full h-24 object-contain"
                    style={{ transform: `rotate(${((initialRotations[index] || 0) - clickCounts[index] * 45 + 360) % 360}deg)` }}
                  />
                </button>
              ))}
            </div>
          </>
        )}

        <div className="w-full flex items-center justify-start gap-3">
          {!verified ? (
            <button
              type="button"
              onClick={handleVerify}
              className="btn-primary whitespace-nowrap w-full sm:w-auto disabled:opacity-50"
              disabled={loading}
            >
              Überprüfen
            </button>
          ) : (
            <div className="flex items-center gap-2 text-green-600 dark:text-green-400 font-medium animate-pulse">
              <span className="relative flex h-5 w-5 items-center justify-center">
                <span className="absolute inline-flex h-full w-full rounded-full bg-green-200 dark:bg-green-900/50 animate-ping opacity-75"></span>
                <span className="relative inline-flex h-4 w-4 rounded-full bg-green-500"></span>
              </span>
              <span>Captcha erfolgreich verifiziert</span>
            </div>
            
          )}
        </div>
      </div>
    </div>
  );
};

export default Captcha;