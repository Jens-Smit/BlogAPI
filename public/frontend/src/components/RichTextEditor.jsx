import React, { useState, useEffect, useRef } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import TiptapUnderline from '@tiptap/extension-underline';
import TiptapLink from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import { Node, mergeAttributes } from '@tiptap/core';
import { NodeSelection } from '@tiptap/pm/state';
import { Bold, Italic, Underline, Heading1, Heading2, Heading3, List, ListOrdered, Quote, Code, Link, Upload, X, Star, PlusCircle, AlignLeft, AlignCenter, AlignRight } from 'lucide-react';
import api from '../services/api';
import './RichTextEditor.css';

// 1. Eigene Image-Erweiterung mit stabiler Style-Handling
const CustomImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      style: {
        default: 'max-width: 100%; height: auto; display: block; margin-left: 0px; margin-right: auto; border-radius: 0.5rem;',
        parseHTML: element => element.getAttribute('style'),
        renderHTML: attributes => {
          if (!attributes.style) return {};
          return { style: attributes.style };
        },
      },
    };
  },
});

// 2. Video Node-Definition mit konsistentem Attribute-Parsing
const Video = Node.create({
  name: 'video',
  group: 'block',
  atom: true,
  addAttributes() {
    return {
      src: { default: null },
      controls: { default: true },
      style: {
        default: 'max-width: 100%; height: auto; display: block; margin-left: 0px; margin-right: auto; border-radius: 0.5rem;',
        parseHTML: element => element.getAttribute('style'),
        renderHTML: attributes => {
          if (!attributes.style) return {};
          return { style: attributes.style };
        },
      },
    };
  },
  parseHTML() {
    return [{ tag: 'video' }];
  },
  renderHTML({ HTMLAttributes }) {
    return ['video', mergeAttributes(HTMLAttributes, { controls: '' }), ['source', { src: HTMLAttributes.src }]];
  },
});

const RichTextEditor = ({ content, onChange, uploadUrl = '/posts/upload', onMediaFilesChange, onFeaturedImageChange, initialMedia = [] }) => {
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState('');
  const [uploadedImages, setUploadedImages] = useState(initialMedia);
  const [featuredImageId, setFeaturedImageId] = useState(null);
  const [dragOver, setDragOver] = useState(false);
  const [mediaToolbarState, setMediaToolbarState] = useState(null);
  const fileInputRef = useRef(null);
  const dropZoneRef = useRef(null);
  const editorWrapperRef = useRef(null);

  const editor = useEditor({
    extensions: [
      StarterKit,
      CustomImage.configure({ inline: false, allowBase64: false }),
      TiptapUnderline,
      TiptapLink.configure({ openOnClick: false }),
      Video,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
    ],
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
      const newFiles = uploadedImages.filter(image => image.file).map((image) => image.file);
      onMediaFilesChange(newFiles);
    }
  }, [uploadedImages, onMediaFilesChange]);

  // Click handler zur Erkennung von Media-Elementen und Ermittlung der echten Node-Position
  useEffect(() => {
    if (!editor?.view?.dom) return;

    const handleEditorClick = (event) => {
      const mediaElement = event.target.closest('img, video');

      if (!mediaElement || !editor) {
        setMediaToolbarState(null);
        return;
      }

      // Exakte Ermittlung der Pos im Dokument für den geklickten DOM-Knoten
      const posInfo = editor.view.posAtDOM(mediaElement, 0);
      
      // NodeSelection direkt setzen, damit das Bild/Video im Editor als ausgewählt gilt
      try {
        const tr = editor.state.tr;
        const selection = NodeSelection.create(editor.state.doc, posInfo);
        editor.view.dispatch(tr.setSelection(selection));
      } catch (error) {
        console.debug('Could not create NodeSelection', error);
      }

      if (!editorWrapperRef.current) return;

      const wrapperRect = editorWrapperRef.current.getBoundingClientRect();
      const mediaRect = mediaElement.getBoundingClientRect();

      setMediaToolbarState({
        visible: true,
        mediaPos: posInfo,
        position: {
          top: mediaRect.top - wrapperRect.top - 8,
          left: mediaRect.left - wrapperRect.left + mediaRect.width / 2,
        },
      });
    };

    const editorDom = editor.view.dom;
    editorDom.addEventListener('click', handleEditorClick);

    return () => editorDom.removeEventListener('click', handleEditorClick);
  }, [editor]);

  // Funktion zum sauberen Anwenden der Randausrichtung im Inline-Style
  const applyMediaAlignment = (alignment) => {
    if (!editor) return;

    const alignmentStyles = {
      left: 'margin-left: 0px; margin-right: auto;',
      center: 'margin-left: auto; margin-right: auto;',
      right: 'margin-left: auto; margin-right: 0px;',
    };

    const { state, view } = editor;
    const { selection } = state;

    let targetPos = mediaToolbarState?.mediaPos;
    let node = null;

    // 1. Prüfen, ob die aktuelle Auswahl bereits eine NodeSelection auf Bild oder Video ist
    if (selection instanceof NodeSelection && (selection.node.type.name === 'image' || selection.node.type.name === 'video')) {
      node = selection.node;
      targetPos = selection.from;
    } else if (typeof targetPos === 'number') {
      // 2. Fallback über die im Klick-Event gespeicherte Position
      node = state.doc.nodeAt(targetPos);
    }

    if (!node || (node.type.name !== 'image' && node.type.name !== 'video')) return;

    const currentStyle = node.attrs.style || '';

    // Extrahiere vorhandene Style-Eigenschaften außer Margins und Display
    const preservedStyles = currentStyle
      .split(';')
      .map(s => s.trim())
      .filter(s => s && !s.toLowerCase().startsWith('margin') && !s.toLowerCase().startsWith('display'));

    // Bilde das neue Style-Attribut zusammen
    const newStyle = [
      ...preservedStyles,
      'display: block',
      alignmentStyles[alignment]
    ].join('; ') + ';';

    // Transaktion ausführen, um die Attribute direkt am Knoten zu aktualisieren
    const tr = state.tr.setNodeMarkup(targetPos, undefined, {
      ...node.attrs,
      style: newStyle,
    });

    view.dispatch(tr);
  };

  const insertImage = (image, size = 'full') => {
    if (!editor) return;

    const sizeStyles = {
      'full': 'max-width:100%;',
      'large': 'max-width:80%;',
      'medium': 'max-width:60%;',
      'small': 'max-width:40%;',
      'custom': 'max-width:300px;',
    };

    const selectedStyle = sizeStyles[size] || sizeStyles['full'];
    const fullStyle = `${selectedStyle} height:auto;display:block;margin-left:0px;margin-right:auto;border-radius:0.5rem;`;

    editor.chain().focus().setImage({
      src: image.url,
      alt: image.name,
      style: fullStyle,
    }).run();
  };

  const insertVideo = (image, size = 'full') => {
    if (!editor) return;

    const sizeStyles = {
      'full': 'max-width:100%;',
      'large': 'max-width:80%;',
      'medium': 'max-width:60%;',
      'small': 'max-width:40%;',
      'custom': 'max-width:300px;',
    };

    const selectedStyle = sizeStyles[size] || sizeStyles['full'];
    const fullStyle = `${selectedStyle} height:auto;display:block;margin-left:0px;margin-right:auto;border-radius:0.5rem;`;

    editor.chain().focus().insertContent({
      type: 'video',
      attrs: {
        src: image.url,
        style: fullStyle,
      },
    }).run();
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

    const dataTransfer = new DataTransfer();
    files.forEach(file => dataTransfer.items.add(file));
    fileInputRef.current.files = dataTransfer.files;

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
          onClick={() => editor.chain().focus().setTextAlign('left').run()}
          className={`p-2 rounded ${editor.isActive({ textAlign: 'left' }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Linksbündig"
        >
          <AlignLeft size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().setTextAlign('center').run()}
          className={`p-2 rounded ${editor.isActive({ textAlign: 'center' }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Zentriert"
        >
          <AlignCenter size={16} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().setTextAlign('right').run()}
          className={`p-2 rounded ${editor.isActive({ textAlign: 'right' }) ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'}`}
          title="Rechtsbündig"
        >
          <AlignRight size={16} />
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
                      if (image.name.match(/\.(mp4|webm|ogg)$/i)) {
                        insertVideo(image, size === 'insert-video' ? 'full' : size);
                      } else {
                        insertImage(image, size);
                      }
                    }}
                    defaultValue=""
                  >
                    <option value="" disabled>Größe wählen</option>
                    <option value="full">Vollständige Breite</option>
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
                    onClick={() => image.name.match(/\.(mp4|webm|ogg)$/i) ? insertVideo(image) : insertImage(image)}
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

      <div ref={editorWrapperRef} className="relative">
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
          {mediaToolbarState?.visible && (
            <div
              className="absolute z-20 flex items-center gap-1 rounded-lg border border-primary-200 dark:border-primary-700 bg-primary-50 dark:bg-primary-900/80 p-1 shadow-lg backdrop-blur-sm"
              style={{
                top: mediaToolbarState.position?.top ?? 0,
                left: mediaToolbarState.position?.left ?? 0,
                transform: 'translate(-50%, -100%)',
              }}
              onMouseDown={(event) => event.stopPropagation()}
            >
              <button
                type="button"
                onClick={() => applyMediaAlignment('left')}
                className="p-2 rounded hover:bg-primary-100 dark:hover:bg-primary-800 text-primary-600 dark:text-primary-300"
                title="Medien links ausrichten"
              >
                <AlignLeft size={16} />
              </button>

              <button
                type="button"
                onClick={() => applyMediaAlignment('center')}
                className="p-2 rounded hover:bg-primary-100 dark:hover:bg-primary-800 text-primary-600 dark:text-primary-300"
                title="Medien zentrieren"
              >
                <AlignCenter size={16} />
              </button>

              <button
                type="button"
                onClick={() => applyMediaAlignment('right')}
                className="p-2 rounded hover:bg-primary-100 dark:hover:bg-primary-800 text-primary-600 dark:text-primary-300"
                title="Medien rechts ausrichten"
              >
                <AlignRight size={16} />
              </button>
            </div>
          )}
          <div className="rich-text-editor">
            <EditorContent editor={editor} />
          </div>
        </div>
      </div>
    </div>
  );
};

export default RichTextEditor;