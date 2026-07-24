// Testdatei für die Konfiguration
import config from './src/config.js';

console.log('Konfigurationstest');
console.log('==================');

console.log('API Base URL:', config.apiBaseUrl);
console.log('Upload URL für "test.jpg":', config.getUploadUrl('test.jpg'));
console.log('API URL für "posts":', config.getApiUrl('posts'));

console.log('\nKonfiguration erfolgreich geladen!');