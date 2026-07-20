import React, { useState, useEffect, useRef } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Italic, Underline, Heading1, Heading2, Heading3, List, ListOrdered, Quote, Code, Link, Image as ImageIcon, Undo, Redo, Upload, X, Star, PlusCircle } from 'lucide-react';
import api from '../services/api';
import './RichTextEditor.css';

const escapeHtml = (value = '') => value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

const RichTextEditor = ({ content, onChange, uploadUrl = '/posts/upload', onMediaFilesChange, onFeaturedImageChange }) => {
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState('');
  const [uploadedImages, setUploadedImages] = useState([]);
  const [featuredImageId, setFeaturedImageId] = useState(null);
  const [dragOver, setDragOver] = useState(false);
  const fileInputRef = useRef(null);
  const dropZoneRef = useRef(null);

  const editor = useEditor({
    extensions: [StarterKit],
    content: content || '<p>Beginne hier mit dem Schreiben...</p>',
    onUpdate: ({ editor }) => {
      onChange(editor.getHTML());
    },
    editorProps: {
      attributes: {
        class: 'rich-text-editor-content',
      },
    },
  });

  useEffect(() => {
    if (content !== editor?.getHTML()) {
      editor?.commands.setContent(content || '<p>Beginne hier mit dem Schreiben...</p>');
    }
  }, [content, editor]);

  useEffect(() => {
    if (onMediaFilesChange) {
      onMediaFilesChange(uploadedImages.map((image) => image.file));
    }
  }, [uploadedImages, onMediaFilesChange]);

  const insertImage = (image, size = 'full') => {
    if (!editor) return;

    // Define different size options
    const sizeStyles = {
      'full': 'max-width:100%;',
      'large': 'max-width:80%;',
      'medium': 'max-width:60%;',
      'small': 'max-width:40%;',
      'custom': 'max-width:300px;',
    };

    const selectedStyle = sizeStyles[size] || sizeStyles['full'];
    const html = `<img src="${image.url}" alt="${escapeHtml(image.name)}" style="${selectedStyle}height:auto;margin:1rem 0;border-radius:0.5rem;" />`;
    editor.chain().focus().insertContent(html).run();
  };

  const insertVideo = (image) => {
    if (!editor) return;

    const videoHtml = `<video controls style="max-width:100%;height:auto;margin:1rem 0;border-radius:0.5rem;"><source src="${image.url}" type="video/${image.name.split('.').pop()}">Your browser does not support the video tag.</video>`;
    editor.chain().focus().insertContent(videoHtml).run();
  };

  const handleFileChange = async (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    setUploading(true);
    setUploadError('');

    try {
      const newImages = [];

      for (const file of files) {
        const formData = new FormData();
        formData.append('file', file);

        const response = await api.post(uploadUrl, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        // Extract the URL from the response, handling different possible structures
        const mediaUrl = response.data.url || response.data.path || response.data.filePath || `/uploads/${response.data.filename}`;
        newImages.push({
          id: `${Date.now()}-${Math.random()}`,
          name: file.name,
          url: mediaUrl,
          file,
          isFeatured: false,
        });
      }

      setUploadedImages((prev) => [...prev, ...newImages]);
    } catch (error) {
      console.error('Fehler beim Hochladen des Bildes:', error);
      setUploadError('Fehler beim Hochladen der Medien. Bitte versuchen Sie es erneut.');
    } finally {
      setUploading(false);
      event.target.value = '';
    }
  };

  const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragOver(false);

    const files = Array.from(event.dataTransfer.files || []);
    if (!files.length) return;

    // Trigger file input change manually
    const dataTransfer = new DataTransfer();
    files.forEach(file => dataTransfer.items.add(file));
    fileInputRef.current.files = dataTransfer.files;

    // Manually trigger the file change handler
    handleFileChange({ target: { files } });
  };

  const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragOver(true);
  };

  const handleDragLeave = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragOver(false);
  };

  const handleDragEnter = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragOver(true);
  };

  const toggleFeaturedImage = (imageId) => {
    setFeaturedImageId(prevId => prevId === imageId ? null : imageId);
    setUploadedImages(prevImages =>
      prevImages.map(image =>
        image.id === imageId
          ? { ...image, isFeatured: !image.isFeatured }
          : { ...image, isFeatured: false }
      )
    );

    // Find the featured image and notify parent component
    const featuredImage = uploadedImages.find(img => img.id === imageId);
    if (onFeaturedImageChange && featuredImage) {
      onFeaturedImageChange(featuredImage.file);
    } else if (onFeaturedImageChange) {
      onFeaturedImageChange(null);
    }
  };

  const setLink = () => {
    const url = window.prompt('URL eingeben:');
    if (url) {
      editor?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    }
  };

  if (!editor) {
    return null;
  }

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
      <div className="flex flex-wrap gap-1 p-2 border-b border-gray-200 dark:border-gray-700">
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBold().run()}
          disabled={!editor.can().chain().focus().toggleBold().run()}
          className={`p-2 rounded ${editor.isActive('bold') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Fett"
        >
          <Bold size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          disabled={!editor.can().chain().focus().toggleItalic().run()}
          className={`p-2 rounded ${editor.isActive('italic') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Kursiv"
        >
          <Italic size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleUnderline().run()}
          className={`p-2 rounded ${editor.isActive('underline') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Unterstrichen"
        >
          <Underline size={16} />
        </button>

        <div className="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
          className={`p-2 rounded ${editor.isActive('heading', { level: 1 }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Überschrift 1"
        >
          <Heading1 size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
          className={`p-2 rounded ${editor.isActive('heading', { level: 2 }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Überschrift 2"
        >
          <Heading2 size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
          className={`p-2 rounded ${editor.isActive('heading', { level: 3 }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Überschrift 3"
        >
          <Heading3 size={16} />
        </button>

        <div className="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          className={`p-2 rounded ${editor.isActive('bulletList') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Aufzählungsliste"
        >
          <List size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          className={`p-2 rounded ${editor.isActive('orderedList') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Nummerierte Liste"
        >
          <ListOrdered size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
          className={`p-2 rounded ${editor.isActive('blockquote') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Zitat"
        >
          <Quote size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().toggleCodeBlock().run()}
          className={`p-2 rounded ${editor.isActive('codeBlock') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Code-Block"
        >
          <Code size={16} />
        </button>

        <div className="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

        <button
          type="button"
          onClick={setLink}
          className={`p-2 rounded ${editor.isActive('link') ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Link einfügen"
        >
          <Link size={16} />
        </button>

        <label className="flex items-center gap-2 rounded px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
          <input type="file" multiple accept="image/*,video/*" onChange={handleFileChange} className="hidden" ref={fileInputRef} />
          <Upload size={16} />
          <span>{uploading ? 'Hochladen...' : 'Medien wählen'}</span>
        </label>

        <div className="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

        <button
          type="button"
          onClick={() => editor.chain().focus().undo().run()}
          disabled={!editor.can().chain().focus().undo().run()}
          className="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Rückgängig"
        >
          <Undo size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().redo().run()}
          disabled={!editor.can().chain().focus().redo().run()}
          className="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
          title="Wiederholen"
        >
          <Redo size={16} />
        </button>
      </div>

      {uploadError && (
        <p className="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{uploadError}</p>
      )}

      {uploadedImages.length > 0 && (
        <div className="grid gap-3 p-4 sm:grid-cols-2 border-b border-gray-200 dark:border-gray-700">
          {uploadedImages.map((image) => (
            <div key={image.id} className={`rounded border-2 border-gray-200 dark:border-gray-700 p-2 ${image.isFeatured ? 'border-primary-500 dark:border-primary-400' : ''}`}>
              {image.name.match(/\.(mp4|webm|ogg)$/i) ? (
                <video src={image.url} controls className="h-24 w-full rounded object-cover" />
              ) : (
                <img src={image.url} alt={image.name} className="h-24 w-full rounded object-cover" />
              )}
              <div className="mt-2 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                  <select
                    className="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700"
                    onChange={(e) => {
                      const size = e.target.value;
                      if (size === 'insert') {
                        insertImage(image, 'full');
                      } else if (size === 'large') {
                        insertImage(image, 'large');
                      } else if (size === 'medium') {
                        insertImage(image, 'medium');
                      } else if (size === 'small') {
                        insertImage(image, 'small');
                      } else if (size === 'custom') {
                        insertImage(image, 'custom');
                      } else if (size === 'insert-video' && image.name.match(/\.(mp4|webm|ogg)$/i)) {
                        insertVideo(image);
                      }
                    }}
                    defaultValue=""
                  >
                    <option value="" disabled>Größe wählen</option>
                    <option value="insert">Vollständige Breite</option>
                    <option value="large">Groß (80%)</option>
                    <option value="medium">Mittel (60%)</option>
                    <option value="small">Klein (40%)</option>
                    <option value="custom">Benutzerdefiniert (300px)</option>
                    {image.name.match(/\.(mp4|webm|ogg)$/i) && (
                      <option value="insert-video">Als Video einfügen</option>
                    )}
                  </select>
                  <button
                    type="button"
                    onClick={() => insertImage(image)}
                    className="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                    title="Mit Standardgröße einfügen"
                  >
                    <PlusCircle size={16} className="text-primary-600 dark:text-primary-400" />
                  </button>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => toggleFeaturedImage(image.id)}
                    className={`p-1 rounded ${image.isFeatured ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
                    title={image.isFeatured ? "Als Featured markiert" : "Als Featured markieren"}
                  >
                    <Star size={14} className={image.isFeatured ? 'fill-current text-primary-600' : ''} />
                  </button>
                  <button
                    type="button"
                    onClick={() => setUploadedImages((prev) => prev.filter((item) => item.id !== image.id))}
                    className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                    title="Entfernen"
                  >
                    <X size={14} />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <div
        ref={dropZoneRef}
        className={`p-4 min-h-[300px] prose dark:prose-invert max-w-none ${dragOver ? 'border-2 border-dashed border-primary-500 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-0'}`}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDragEnter={handleDragEnter}
      >
        {dragOver ? (
          <div className="absolute inset-0 flex items-center justify-center bg-primary-50 dark:bg-primary-900/20 rounded-lg">
            <div className="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
              <Upload size={48} className="mx-auto text-primary-600 dark:text-primary-400" />
              <p className="mt-2 font-medium text-primary-600 dark:text-primary-400">Dateien hier ablegen</p>
            </div>
          </div>
        ) : null}
        <div className="rich-text-editor">
          <EditorContent editor={editor} />
        </div>
      </div>
    </div>
  );
};

export default RichTextEditor;