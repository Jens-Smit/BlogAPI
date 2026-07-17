import React, { useState, useEffect } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Italic, Underline, Heading1, Heading2, Heading3, List, ListOrdered, Quote, Code, Link, Image, Undo, Redo } from 'lucide-react';
import api from '../services/api';

const RichTextEditor = ({ content, onChange, uploadUrl = '/api/posts/upload' }) => {
  const [imageUrl, setImageUrl] = useState('');

  const editor = useEditor({
    extensions: [StarterKit],
    content: content || '<p>Beginne hier mit dem Schreiben...</p>',
    onUpdate: ({ editor }) => {
      onChange(editor.getHTML());
    },
  });

  useEffect(() => {
    if (content !== editor?.getHTML()) {
      editor?.commands.setContent(content || '<p>Beginne hier mit dem Schreiben...</p>');
    }
  }, [content, editor]);

  const addImage = async () => {
    if (!imageUrl) return;

    try {
      const response = await api.post(uploadUrl, { image: imageUrl });
      const url = response.data.url;
      editor?.chain().focus().setImage({ src: url }).run();
      setImageUrl('');
    } catch (error) {
      console.error('Fehler beim Hochladen des Bildes:', error);
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
      {/* Toolbar */}
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

        <div className="flex items-center">
          <input
            type="text"
            placeholder="Bild-URL"
            value={imageUrl}
            onChange={(e) => setImageUrl(e.target.value)}
            className="p-1 text-sm border border-gray-300 dark:border-gray-600 rounded-l bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
          />
          <button
            type="button"
            onClick={addImage}
            className="p-2 rounded-r hover:bg-gray-100 dark:hover:bg-gray-700"
            title="Bild einfügen"
          >
            <Image size={16} />
          </button>
        </div>

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

      {/* Editor Content */}
      <div className="p-4 min-h-[300px] prose dark:prose-invert max-w-none">
        <EditorContent editor={editor} />
      </div>
    </div>
  );
};

export default RichTextEditor;