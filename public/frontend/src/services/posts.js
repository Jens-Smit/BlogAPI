import api from './api';

// Get all posts
const getPosts = async (params = {}) => {
  try {
    const response = await api.get('/api/posts', { params });
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Beiträge:', error);
    throw error;
  }
};

// Get a single post by slug
const getPostBySlug = async (slug) => {
  try {
    const response = await api.get(`/api/posts/${slug}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen des Beitrags:', error);
    throw error;
  }
};

// Create a new post
const createPost = async (postData) => {
  try {
    const response = await api.post('/api/posts', postData);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Erstellen des Beitrags:', error);
    throw error;
  }
};

// Update a post
const updatePost = async (id, postData) => {
  try {
    const response = await api.post(`/api/posts/${id}`, postData);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Aktualisieren des Beitrags:', error);
    throw error;
  }
};

// Delete a post
const deletePost = async (id) => {
  try {
    const response = await api.delete(`/api/posts/${id}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Löschen des Beitrags:', error);
    throw error;
  }
};

// Upload an image for a post
const uploadImage = async (imageFile) => {
  try {
    const formData = new FormData();
    formData.append('image', imageFile);

    const response = await api.post('/api/posts/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data;
  } catch (error) {
    console.error('Fehler beim Hochladen des Bildes:', error);
    throw error;
  }
};

export { getPosts, getPostBySlug, createPost, updatePost, deletePost, uploadImage };