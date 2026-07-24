import React from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { Calendar, User } from 'lucide-react';
import config from '../config';

const PostCard = ({ post }) => {
  const fallbackImage = 'https://via.placeholder.com/400x250?text=No+Image';

  const stripHtml = (value = '') => value.replace(/<[^>]+>/g, '');
  const previewText = post.excerpt
    ? stripHtml(post.excerpt)
    : `${stripHtml(post.content || '').substring(0, 150)}...`;
  const previewHtml = post.excerpt && /<[^>]+>/.test(post.excerpt) ? post.excerpt : null;

  return (
    <motion.article
      className="card h-full flex flex-col"
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      whileHover={{ y: -5, boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)' }}
    >
      <div className="relative h-48 overflow-hidden">
        <img
          src={config.getUploadUrl(post.titleImage || post.image)}
          alt={post.title}
          className="w-full h-full object-cover"
          onError={(e) => {
            e.target.onerror = null;
            e.target.src = fallbackImage;
          }}
        />
        {post.category && (
          <span className="absolute top-4 right-4 bg-primary-600 text-white px-2 py-1 rounded text-xs font-medium">
            {post.category.name}
          </span>
        )}
      </div>

      <div className="p-6 flex flex-col flex-grow">
        <div className="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-3">
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

        <h3 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3 flex-grow">
          {post.title}
        </h3>

        <div className="text-gray-600 dark:text-gray-400 mb-4 text-sm line-clamp-3">
          {previewHtml ? (
            <div
              dangerouslySetInnerHTML={{ __html: previewHtml }}
            />
          ) : (
            <p>{previewText}</p>
          )}
        </div>

        <div className="flex justify-between items-center">
          <Link
            to={`/blog/${post.slug}`}
            className="btn-primary text-sm"
          >
            Weiterlesen
          </Link>
        </div>
      </div>
    </motion.article>
  );
};

PostCard.defaultProps = {
  post: {
    id: 1,
    title: 'Beispiel Beitrag',
    slug: 'beispiel-beitrag',
    excerpt: 'Dies ist ein Beispiel für einen Beitrag.',
    content: 'Dies ist der Inhalt des Beitrags.',
    createdAt: new Date().toISOString(),
    image: null,
    author: {
      id: 1,
      username: 'Admin',
    },
    category: {
      id: 1,
      name: 'Allgemein',
    },
    tags: [],
  },
};

export default PostCard;