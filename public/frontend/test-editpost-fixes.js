// Test file to verify the EditPost fixes
import config from './src/config.js';
import api from './src/services/api.js';

// Test the config fixes
console.log('Testing config fixes...');
console.log('Upload URL for "test.jpg":', config.getUploadUrl('test.jpg'));
console.log('Expected: http://localhost:8000/api/uploads/test.jpg');
console.log('Match:', config.getUploadUrl('test.jpg') === 'http://localhost:8000/api/uploads/test.jpg');

// Test the buildMediaUrl function logic
const buildMediaUrl = (value) => {
  if (!value) return '';
  if (/^https?:\/\//i.test(value)) return value;
  return `/api/uploads/${value}`;
};

console.log('\nTesting buildMediaUrl function...');
console.log('buildMediaUrl("test.jpg"):', buildMediaUrl('test.jpg'));
console.log('Expected: /api/uploads/test.jpg');
console.log('Match:', buildMediaUrl('test.jpg') === '/api/uploads/test.jpg');

console.log('buildMediaUrl("http://example.com/image.jpg"):', buildMediaUrl('http://example.com/image.jpg'));
console.log('Expected: http://example.com/image.jpg');
console.log('Match:', buildMediaUrl('http://example.com/image.jpg') === 'http://example.com/image.jpg');

console.log('\nAll tests completed!');