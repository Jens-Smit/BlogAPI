import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Calendar, User, ArrowLeft } from 'lucide-react';
import api from '../services/api';
import config from '../config';

const PostDetail = () => {
  const { slug } = useParams();
  const [post, setPost] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchPost = async () => {
      setLoading(true);
      try {
        const response = await api.get(`/posts/${slug}`);
        setPost(response.data);
      } catch (err) {
        console.error('Fehler beim Laden des Beitrags:', err);
        setError('Beitrag nicht gefunden oder Fehler beim Laden.');
      } finally {
        setLoading(false);
      }
    };

    fetchPost();
  }, [slug]);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center text-center px-4">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">{error}</h2>
        <Link to="/blog" className="btn-secondary flex items-center gap-2">
          <ArrowLeft size={20} />
          Zurück zum Blog
        </Link>
      </div>
    );
  }

  if (!post) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-600 dark:text-gray-400">Kein Beitrag gefunden.</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen py-16">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <motion.article
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="card"
        >
          {/* Header */}
          <header className="p-6 border-b border-gray-200 dark:border-gray-700">
            <Link to="/blog" className="btn-secondary flex items-center gap-2 mb-6 w-fit">
              <ArrowLeft size={20} />
              Zurück zum Blog
            </Link>

            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                  {post.title}
                </h1>
                <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                  <div className="flex items-center mr-4">
                    <Calendar size={14} className="mr-1" />
                    <span>{new Date(post.createdAt).toLocaleDateString('de-DE')}</span>
                  </div>
                  {post.author && (
                    <div className="flex items-center">
                      <User size={14} className="mr-1" />
                      <span>{post.author.username}</span>
                    </div>
                  )}
                </div>
              </div>
              {post.category && (
                <span className="bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 px-3 py-1 rounded-full text-sm font-medium">
                  {post.category.name}
                </span>
              )}
            </div>
          </header>

          {/* Image */}
          {(post.titleImage || post.image) && (
            <div className="relative h-96 overflow-hidden">
              <img
                src={config.getUploadUrl(post.titleImage || post.image)}
                alt={post.title}
                className="w-full h-full object-cover"
              />
            </div>
          )}

          {/* Content */}
          <div className="p-6">
            <div
              className="prose dark:prose-invert max-w-none"
              dangerouslySetInnerHTML={{ __html: post.content }}
            />
          </div>
        </motion.article>
      </div>
    </div>
  );
};

export default PostDetail;