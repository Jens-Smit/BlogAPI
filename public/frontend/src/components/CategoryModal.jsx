import React, { useState, useEffect } from 'react';
import { X, Plus } from 'lucide-react';
import api from '../services/api';
import { getCategories } from '../services/categories';

const CategoryModal = ({ isOpen, onClose, onCategoryCreated }) => {
  const [name, setName] = useState('');
  const [parentId, setParentId] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [categories, setCategories] = useState([]);
  const [loadingCategories, setLoadingCategories] = useState(false);

  useEffect(() => {
    const fetchCategories = async () => {
      setLoadingCategories(true);
      try {
        const data = await getCategories();
        setCategories(data);
      } catch (err) {
        console.error('Fehler beim Abrufen der Kategorien:', err);
      } finally {
        setLoadingCategories(false);
      }
    };

    if (isOpen) {
      fetchCategories();
    }
  }, [isOpen]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    if (!name.trim()) {
      setError('Kategoriename ist erforderlich.');
      return;
    }

    setLoading(true);

    try {
      const response = await api.post('/categories', {
        name: name.trim(),
        parentId: parentId || null
      });

      if (response.data.id) {
        setSuccess(true);
        setName('');
        setParentId('');

        // Rufe die Callback-Funktion auf, um die Kategorienliste zu aktualisieren
        if (typeof onCategoryCreated === 'function') {
          onCategoryCreated();
        }

        // Schließe das Modal nach 2 Sekunden automatisch
        setTimeout(() => {
          onClose();
        }, 2000);
      } else {
        setError('Fehler beim Erstellen der Kategorie.');
      }
    } catch (err) {
      console.error('Fehler beim Erstellen der Kategorie:', err);
      setError(
        err.response?.data?.error ||
        'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
      );
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
            Neue Kategorie erstellen
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            aria-label="Schließen"
          >
            <X size={24} />
          </button>
        </div>

        {error && (
          <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
        )}

        {success && (
          <p className="text-green-600 dark:text-green-400 mb-4">
            Kategorie erfolgreich erstellt!
          </p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="categoryName" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Kategoriename
            </label>
            <input
              type="text"
              id="categoryName"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Name der Kategorie"
              className="input-field w-full"
              required
              maxLength={100}
            />
          </div>

          <div>
            <label htmlFor="parentCategory" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Übergeordnete Kategorie (optional)
            </label>
            {loadingCategories ? (
              <div className="input-field w-full flex items-center">
                <span className="animate-spin mr-2">⠋</span>
                <span>Kategorien werden geladen...</span>
              </div>
            ) : (
              <select
                id="parentCategory"
                value={parentId}
                onChange={(e) => setParentId(e.target.value)}
                className="input-field w-full"
              >
                <option value="">Keine übergeordnete Kategorie</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            )}
            <p className="text-gray-500 dark:text-gray-400 text-xs mt-1">
              Wählen Sie eine übergeordnete Kategorie oder lassen Sie das Feld leer, um eine Hauptkategorie zu erstellen.
            </p>
          </div>

          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600"
              disabled={loading}
            >
              Abbrechen
            </button>
            <button
              type="submit"
              className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"
              disabled={loading}
            >
              {loading ? (
                <>
                  <span className="animate-spin">⠋</span>
                  <span>Wird erstellt...</span>
                </>
              ) : (
                <>
                  <Plus size={16} />
                  <span>Kategorie erstellen</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CategoryModal;