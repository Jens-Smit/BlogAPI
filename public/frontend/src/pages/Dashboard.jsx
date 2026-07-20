import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Plus, Edit3, Trash2, Eye, Loader2 } from 'lucide-react';
import api from '../services/api';

const Dashboard = () => {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [deletingId, setDeletingId] = useState(null);

  useEffect(() => {
    const fetchPosts = async () => {
      setLoading(true);
      try {
        const response = await api.get('/posts?author=me'); // Assuming there's an endpoint to get current user's posts
        setPosts(response.data);
      } catch (err) {
        console.error('Fehler beim Laden der Beiträge:', err);
        setError('Fehler beim Laden Ihrer Beiträge.');
      } finally {
        setLoading(false);
      }
    };

    fetchPosts();
  }, []);

  const handleDelete = async (id) => {
    if (!window.confirm('Möchten Sie diesen Beitrag wirklich löschen?')) return;

    setDeletingId(id);
    try {
      await api.delete(`/api/posts/${id}`);
      setPosts((prev) => prev.filter((post) => post.id !== id));
    } catch (err) {
      console.error('Fehler beim Löschen des Beitrags:', err);
      setError('Fehler beim Löschen des Beitrags.');
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="min-h-screen py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          <div className="flex items-center justify-between mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
              Dashboard
            </h1>
            <Link to="/create-post" className="btn-primary flex items-center gap-2">
              <Plus size={20} />
              <span>Neuer Beitrag</span>
            </Link>
          </div>

          {error && (
            <p className="text-red-600 dark:text-red-400 mb-6">{error}</p>
          )}

          {loading ? (
            <div className="flex justify-center items-center py-12">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
            </div>
          ) : posts.length > 0 ? (
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-900">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Titel
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Kategorie
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Erstellt am
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      Aktionen
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                  {posts.map((post) => (
                    <motion.tr
                      key={post.id}
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      transition={{ duration: 0.3 }}
                    >
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          {(post.titleImage || post.image) && (
                            <img
                              src={post.titleImage || post.image}
                              alt={post.title}
                              className="w-10 h-10 rounded-full mr-3"
                            />
                          )}
                          <div>
                            <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                              {post.title}
                            </div>
                            <div className="text-sm text-gray-500 dark:text-gray-400">
                              {post.slug}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className="bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 px-2 py-1 rounded text-xs font-medium">
                          {post.category?.name || 'Keine'}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {new Date(post.createdAt).toLocaleDateString('de-DE')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <div className="flex justify-end space-x-2">
                          <Link
                            to={`/blog/${post.slug}`}
                            className="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                            title="Anzeigen"
                          >
                            <Eye size={18} />
                          </Link>
                          <Link
                            to={`/edit-post/${post.id}`}
                            className="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                            title="Bearbeiten"
                          >
                            <Edit3 size={18} />
                          </Link>
                          <button
                            onClick={() => handleDelete(post.id)}
                            disabled={deletingId === post.id}
                            className="text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 disabled:opacity-50"
                            title="Löschen"
                          >
                            {deletingId === post.id ? (
                              <Loader2 size={18} className="animate-spin" />
                            ) : (
                              <Trash2 size={18} />
                            )}
                          </button>
                        </div>
                      </td>
                    </motion.tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-12">
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                Sie haben noch keine Beiträge erstellt.
              </p>
              <Link to="/create-post" className="btn-primary inline-flex items-center gap-2">
                <Plus size={20} />
                <span>Ersten Beitrag erstellen</span>
              </Link>
            </div>
          )}
        </motion.div>
      </div>
    </div>
  );
};

export default Dashboard;