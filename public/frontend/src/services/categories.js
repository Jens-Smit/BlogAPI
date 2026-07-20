import api from './api';

// Get all categories
const getCategories = async () => {
  try {
    const response = await api.get('/categories');
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Kategorien:', error);
    throw error;
  }
};

// Get a single category by ID
const getCategoryById = async (id) => {
  try {
    const response = await api.get(`/categories/${id}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Kategorie:', error);
    throw error;
  }
};

// Create a new category
const createCategory = async (categoryData) => {
  try {
    const response = await api.post('/categories', categoryData);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Erstellen der Kategorie:', error);
    throw error;
  }
};

// Update a category
const updateCategory = async (id, categoryData) => {
  try {
    const response = await api.post(`/categories/${id}`, categoryData);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Aktualisieren der Kategorie:', error);
    throw error;
  }
};

// Delete a category
const deleteCategory = async (id) => {
  try {
    const response = await api.delete(`/api/categories/${id}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Löschen der Kategorie:', error);
    throw error;
  }
};

export { getCategories, getCategoryById, createCategory, updateCategory, deleteCategory };