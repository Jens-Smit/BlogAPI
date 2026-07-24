import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Plus, Loader2 } from 'lucide-react';
import RichTextEditor from '../components/RichTextEditor';
import CategoryModal from '../components/CategoryModal';
import api from '../services/api';
import { createPost } from '../services/posts';

const buildMediaUrl = (value) => {
  if (!value) return '';
  if (/^https?:\/\//i.test(value)) return value;
  return `/api/public/uploads/${value}`;
};

const CreatePost = () => {
  const [formData, setFormData] = useState({
    title: '',
    slug: '',
    excerpt: '',
    content: '<p>Beginne hier mit dem Schreiben...</p>',
    category: '',
    image: '',
  });
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [uploadedMediaFiles, setUploadedMediaFiles] = useState([]);
  const [featuredImageFile, setFeaturedImageFile] = useState(null);
  const [featuredImagePreview, setFeaturedImagePreview] = useState('');
  const [featuredImageFromEditor, setFeaturedImageFromEditor] = useState(null);
  const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);

  const navigate = useNavigate();

  useEffect(() => {
    const fetchData = async () => {
      try {
        const categoriesResponse = await api.get('/categories');
        setCategories(categoriesResponse.data);
      } catch (err) {
        console.error('Fehler beim Laden der Kategorien:', err);
        setError('Fehler beim Laden der Kategorien.');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));

    // Auto-generate slug from title
    if (name === 'title') {
      const slug = value
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
      setFormData((prev) => ({ ...prev, slug }));
    }
  };

  const handleFeaturedImageChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setFeaturedImageFile(file);
    setFeaturedImagePreview(URL.createObjectURL(file));
  };

  const handleFeaturedImageFromEditor = (file) => {
    setFeaturedImageFromEditor(file);
    if (file) {
      setFeaturedImagePreview(URL.createObjectURL(file));
    } else {
      setFeaturedImagePreview('');
    }
  };

  const refreshCategories = async () => {
    try {
      const categoriesResponse = await api.get('/categories');
      setCategories(categoriesResponse.data);
    } catch (err) {
      console.error('Fehler beim Aktualisieren der Kategorien:', err);
      setError('Fehler beim Aktualisieren der Kategorienliste.');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    // Use featured image from editor if available, otherwise use the manually uploaded one
    const finalFeaturedImage = featuredImageFromEditor || featuredImageFile;

    if (!finalFeaturedImage && !featuredImagePreview) {
      setError('Bitte wählen Sie ein Beitragsbild aus.');
      return;
    }

    setSubmitting(true);

    try {
      const postData = new FormData();
      postData.append('title', formData.title);
      postData.append('content', formData.content);
      postData.append('categoryId', formData.category);
      postData.append('slug', formData.slug);
      postData.append('excerpt', formData.excerpt || '');

      if (finalFeaturedImage) {
        postData.append('titleImage', finalFeaturedImage);
      }

      uploadedMediaFiles.forEach((file) => {
        if (file) {
          postData.append('images', file);
        }
      });

      const data = await createPost(postData);

      if (data.id) {
        navigate(`/blog/${data.slug || formData.slug}`);
      } else {
        setError('Fehler beim Erstellen des Beitrags.');
      }
    } catch (err) {
      console.error('Fehler beim Erstellen des Beitrags:', err);
      setError(
        err.response?.data?.message ||
        err.response?.data?.error ||
        'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen py-16">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          <div className="flex items-center justify-between mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
              Beitrag erstellen
            </h1>
          </div>

          {error && (
            <p className="text-red-600 dark:text-red-400 mb-6">{error}</p>
          )}

          <form onSubmit={handleSubmit} className="space-y-8">
            <div>
              <label htmlFor="title" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Titel
              </label>
              <input
                type="text"
                id="title"
                name="title"
                value={formData.title}
                onChange={handleChange}
                placeholder="Titel des Beitrags"
                className="input-field"
                required
              />
            </div>

            <div>
              <label htmlFor="slug" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Slug (URL-freundlicher Titel)
              </label>
              <input
                type="text"
                id="slug"
                name="slug"
                value={formData.slug}
                onChange={handleChange}
                placeholder="slug-des-beitrags"
                className="input-field"
                required
              />
            </div>

            

            <div>
              <div className="flex justify-between items-center mb-1">
                <label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Kategorie
                </label>
                <button
                  type="button"
                  onClick={() => setIsCategoryModalOpen(true)}
                  className="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 flex items-center gap-1"
                >
                  <Plus size={16} />
                  <span>Neue Kategorie</span>
                </button>
              </div>
              <select
                id="category"
                name="category"
                value={formData.category}
                onChange={handleChange}
                className="input-field"
                required
              >
                <option value="">Wählen Sie eine Kategorie</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
              {categories.length === 0 && (
                <p className="text-gray-500 dark:text-gray-400 text-sm mt-2">
                  Keine Kategorien verfügbar. Bitte erstellen Sie zuerst Kategorien im Admin-Bereich.
                </p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Beitragsbild
              </label>
              <input
                type="file"
                accept="image/*"
                onChange={handleFeaturedImageChange}
                className="input-field"
                style={{ display: "none" }}
              />
              {featuredImagePreview && (
                <img src={featuredImagePreview} alt="Beitragsbild Vorschau" className="mt-3 h-48 w-full rounded object-cover" />
              )}
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Oder wählen Sie ein Bild aus dem RichTextEditor als Featured Image
              </p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Inhalt
              </label>
              <RichTextEditor
                content={formData.content}
                onChange={(content) => setFormData((prev) => ({ ...prev, content }))}
                onMediaFilesChange={setUploadedMediaFiles}
                onFeaturedImageChange={handleFeaturedImageFromEditor}
              />
            </div>

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={submitting}
                className="btn-primary flex items-center gap-2"
              >
                {submitting ? (
                  <>
                    <Loader2 size={20} className="animate-spin" />
                    <span>Wird erstellt...</span>
                  </>
                ) : (
                  <>
                    <Plus size={20} />
                    <span>Beitrag erstellen</span>
                  </>
                )}
              </button>
            </div>
          </form>
        </motion.div>
      </div>

      <CategoryModal
        isOpen={isCategoryModalOpen}
        onClose={() => setIsCategoryModalOpen(false)}
        onCategoryCreated={refreshCategories}
      />
    </div>
  );
};

export default CreatePost;