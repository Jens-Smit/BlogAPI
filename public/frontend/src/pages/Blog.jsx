import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import PostCard from '../components/PostCard';
import api from '../services/api';

const Blog = () => {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filters, setFilters] = useState({
    category: '',
    tag: '',
    search: '',
  });
  const [categories, setCategories] = useState([]);
  const [tags, setTags] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        // Fetch posts
        const postsResponse = await api.get('/api/posts', { params: filters });
        setPosts(postsResponse.data);

        // Fetch categories
        const categoriesResponse = await api.get('/api/categories');
        setCategories(categoriesResponse.data);

        // Fetch tags (assuming there's an endpoint for tags)
        // If not, you can extract tags from posts
        const allTags = postsResponse.data.flatMap(post => post.tags || []);
        const uniqueTags = Array.from(new Set(allTags.map(tag => tag.name)))
          .map(name => ({ name }));
        setTags(uniqueTags);
      } catch (err) {
        console.error('Fehler beim Laden der Daten:', err);
        setError('Fehler beim Laden der Daten. Bitte versuchen Sie es später erneut.');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [filters]);

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  };

  const handleSearch = (e) => {
    e.preventDefault();
    // The useEffect will automatically refetch when filters change
  };

  return (
    <div className="min-h-screen py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <motion.h1
          className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-10"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          Blog
        </motion.h1>

        {error && (
          <p className="text-red-600 dark:text-red-400 mb-8">{error}</p>
        )}

        {/* Filters */}
        <motion.div
          className="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl mb-10"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.1 }}
        >
          <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
            Filter
          </h2>
          <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Suche
              </label>
              <input
                type="text"
                id="search"
                name="search"
                value={filters.search}
                onChange={handleFilterChange}
                placeholder="Suche nach Beiträgen..."
                className="input-field"
              />
            </div>

            <div>
              <label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Kategorie
              </label>
              <select
                id="category"
                name="category"
                value={filters.category}
                onChange={handleFilterChange}
                className="input-field"
              >
                <option value="">Alle Kategorien</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="tag" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tag
              </label>
              <select
                id="tag"
                name="tag"
                value={filters.tag}
                onChange={handleFilterChange}
                className="input-field"
              >
                <option value="">Alle Tags</option>
                {tags.map((tag, index) => (
                  <option key={index} value={tag.name}>
                    {tag.name}
                  </option>
                ))}
              </select>
            </div>
          </form>
        </motion.div>

        {/* Posts */}
        {loading ? (
          <div className="flex justify-center items-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
          </div>
        ) : posts.length > 0 ? (
          <motion.div
            className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.2 }}
          >
            {posts.map((post) => (
              <PostCard key={post.id} post={post} />
            ))}
          </motion.div>
        ) : (
          <p className="text-gray-600 dark:text-gray-400 text-center py-12">
            Keine Beiträge gefunden.
          </p>
        )}
      </div>
    </div>
  );
};

export default Blog;