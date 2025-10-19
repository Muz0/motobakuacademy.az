(function () {
    function slugify(value) {
        return value
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-+/g, '-');
    }

    function detectMediaType(url) {
        if (!url) {
            return 'none';
        }
        const normalized = url.split('?')[0].toLowerCase();
        if (/\.(mp4|webm|ogg|mov)$/.test(normalized)) {
            return 'video';
        }
        if (/\.(jpe?g|png|gif|webp|svg)$/.test(normalized)) {
            return 'image';
        }
        return 'file';
    }

    function renderMediaPreview(previewEl, url) {
        if (!previewEl) {
            return;
        }

        previewEl.innerHTML = '';
        previewEl.classList.toggle('is-empty', !url);

        if (!url) {
            const empty = document.createElement('span');
            empty.className = 'media-preview__empty';
            empty.textContent = 'No file selected.';
            previewEl.appendChild(empty);
            return;
        }

        const type = detectMediaType(url);
        const thumb = document.createElement('div');
        thumb.className = 'media-preview__thumb';

        if (type === 'image') {
            const img = document.createElement('img');
            img.src = url;
            img.alt = '';
            thumb.appendChild(img);
        } else if (type === 'video') {
            const video = document.createElement('video');
            video.src = url;
            video.controls = true;
            thumb.appendChild(video);
        } else {
            const span = document.createElement('span');
            span.className = 'media-preview__file-icon';
            span.textContent = 'File';
            thumb.appendChild(span);
        }

        const meta = document.createElement('div');
        meta.className = 'media-preview__meta';
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Open in new tab';
        meta.appendChild(link);

        previewEl.appendChild(thumb);
        previewEl.appendChild(meta);
    }

    function updatePreviewForInput(input) {
        if (!input) {
            return;
        }
        const previewEl = document.querySelector('[data-media-preview-for="' + input.id + '"]');
        if (!previewEl) {
            return;
        }
        renderMediaPreview(previewEl, input.value.trim());
    }

    function initMediaPreviews() {
        document.querySelectorAll('[data-media-preview-for]').forEach(function (previewEl) {
            const inputId = previewEl.getAttribute('data-media-preview-for');
            if (!inputId) {
                return;
            }
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            const update = function () {
                updatePreviewForInput(input);
            };
            update();
            if (!input.dataset.previewBound) {
                input.addEventListener('input', update);
                input.addEventListener('change', update);
                input.dataset.previewBound = 'true';
            }
        });
    }

    function initTinyMCE(attempt) {
        if (typeof attempt === 'undefined') {
            attempt = 0;
        }

        if (typeof tinymce === 'undefined') {
            if (attempt < 10) {
                setTimeout(function () {
                    initTinyMCE(attempt + 1);
                }, 200);
            }
            return;
        }

        if (tinymce.editors && tinymce.editors.length) {
            tinymce.remove();
        }

        tinymce.init({
            selector: 'textarea[data-editor="rich-text"]',
            plugins: 'lists link table code',
            toolbar: [
                'undo redo | bold italic underline | alignleft aligncenter alignright | ' +
                'bullist numlist outdent indent | link table | code'
            ].join(' '),
            menubar: false,
            branding: false,
            height: 420,
            content_style: 'body { font-family: "Segoe UI", Tahoma, sans-serif; font-size: 15px; }'
        });

        attachRichTextValidation();
    }

    function attachRichTextValidation() {
        document.querySelectorAll('textarea[data-editor="rich-text"]').forEach(function (textarea) {
            const form = textarea.closest('form');
            if (!form || form.dataset.richTextValidated) {
                return;
            }

            const errorEl = form.querySelector('[data-editor-error]');

            form.addEventListener('submit', function (event) {
                let contentText = textarea.value.trim();

                if (typeof tinymce !== 'undefined') {
                    const editorInstance = tinymce.get(textarea.id);
                    if (editorInstance) {
                        contentText = editorInstance.getContent({ format: 'text' }).trim();
                        editorInstance.save();
                    }
                }

                if (contentText.length === 0) {
                    event.preventDefault();
                    if (errorEl) {
                        errorEl.textContent = 'Content is required.';
                        errorEl.style.display = 'block';
                    } else {
                        alert('Content is required.');
                    }

                    if (typeof tinymce !== 'undefined') {
                        const editorInstance = tinymce.get(textarea.id);
                        if (editorInstance) {
                            editorInstance.focus();
                            return;
                        }
                    }

                    textarea.focus();
                } else if (errorEl) {
                    errorEl.style.display = 'none';
                    errorEl.textContent = '';
                }
            });

            form.dataset.richTextValidated = 'true';
        });
    }

    function setupCopyButtons() {
        document.querySelectorAll('[data-copy-url]').forEach(function (button) {
            if (button.dataset.copyAttached === 'true') {
                return;
            }

            button.addEventListener('click', function () {
                const url = button.getAttribute('data-copy-url');
                if (!url) {
                    return;
                }

                const originalLabel = button.textContent;

                function showCopied() {
                    button.textContent = 'Copied!';
                    button.disabled = true;
                    setTimeout(function () {
                        button.textContent = originalLabel;
                        button.disabled = false;
                    }, 1500);
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(showCopied).catch(function () {
                        fallbackCopy(url, showCopied);
                    });
                } else {
                    fallbackCopy(url, showCopied);
                }
            });

            button.dataset.copyAttached = 'true';
        });
    }

    function fallbackCopy(text, onSuccess) {
        const tempInput = document.createElement('textarea');
        tempInput.value = text;
        tempInput.setAttribute('readonly', '');
        tempInput.style.position = 'absolute';
        tempInput.style.left = '-9999px';
        document.body.appendChild(tempInput);
        tempInput.select();

        try {
            document.execCommand('copy');
            onSuccess();
        } catch (e) {
            console.error('Copy failed', e);
            alert('Copy failed. Please copy the URL manually.');
        } finally {
            document.body.removeChild(tempInput);
        }
    }

    function setupMediaPicker() {
        const modal = document.querySelector('[data-media-picker-modal]');
        if (!modal) {
            return;
        }

        const openButtons = document.querySelectorAll('[data-open-media-picker]');
        const overlay = modal.querySelector('.media-picker__overlay');
        const closeButtons = modal.querySelectorAll('[data-media-close]');
        const tabButtons = modal.querySelectorAll('[data-media-tab]');
        const panels = modal.querySelectorAll('[data-media-panel]');
        const libraryContainer = modal.querySelector('[data-media-library]');
        const uploadForm = modal.querySelector('[data-media-upload-form]');
        const uploadFeedback = modal.querySelector('[data-media-upload-feedback]');
        const apiUrl = modal.getAttribute('data-media-api');
        let activeContext = null;
        let libraryLoaded = false;
        let isLoadingLibrary = false;

        function openModal(context) {
            activeContext = context;
            modal.classList.add('is-open');
            modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
            switchTab('upload');
            loadLibrary();
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('hidden', 'hidden');
            document.body.style.overflow = '';
            activeContext = null;
        }

        function switchTab(tab) {
            tabButtons.forEach(function (button) {
                const isActive = button.getAttribute('data-media-tab') === tab;
                button.classList.toggle('is-active', isActive);
            });
            panels.forEach(function (panel) {
                const isActive = panel.getAttribute('data-media-panel') === tab;
                panel.classList.toggle('is-active', isActive);
            });
        }

        function setToken(token) {
            if (!token) {
                return;
            }
            modal.setAttribute('data-csrf', token);
            modal.querySelectorAll('input[name="_token"]').forEach(function (input) {
                input.value = token;
            });
            document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                if (!modal.contains(input)) {
                    input.value = token;
                }
            });
        }

        function loadLibrary() {
            if (isLoadingLibrary || !apiUrl) {
                return;
            }
            isLoadingLibrary = true;
            libraryContainer.innerHTML = '<p style="color:#64748b;">Loading media...</p>';
            fetch(apiUrl, {
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to load media library.');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    setToken(payload.token);
                    renderLibrary(payload.data || []);
                    libraryLoaded = true;
                })
                .catch(function (error) {
                    libraryContainer.innerHTML = '<p style="color:#dc2626;">' + error.message + '</p>';
                })
                .finally(function () {
                    isLoadingLibrary = false;
                });
        }

        function renderLibrary(items) {
            if (!items.length) {
                libraryContainer.innerHTML = '<p style="color:#64748b;">No files yet. Upload a new image or video.</p>';
                return;
            }

            const fragment = document.createDocumentFragment();
            items.forEach(function (item) {
                const card = document.createElement('div');
                card.className = 'media-picker__item';
                card.setAttribute('data-media-url', item.url);
                card.setAttribute('data-media-type', item.type);
                card.setAttribute('data-media-name', item.name);

                const thumb = document.createElement('div');
                thumb.className = 'media-picker__thumb';

                if (item.type === 'image') {
                    const img = document.createElement('img');
                    img.src = item.url;
                    img.alt = item.name;
                    thumb.appendChild(img);
                } else if (item.type === 'video') {
                    const videoLabel = document.createElement('span');
                    videoLabel.textContent = 'Video';
                    videoLabel.style.color = '#64748b';
                    thumb.appendChild(videoLabel);
                } else {
                    const fileLabel = document.createElement('span');
                    fileLabel.textContent = 'File';
                    fileLabel.style.color = '#64748b';
                    thumb.appendChild(fileLabel);
                }

                const body = document.createElement('div');
                body.className = 'media-picker__body';

                const meta = document.createElement('div');
                meta.className = 'media-picker__meta';
                meta.innerHTML = '<strong>' + item.name + '</strong>' +
                    '<span>' + item.size_label + '</span><br>' +
                    '<span>' + item.modified_label + '</span>';

                const selectButton = document.createElement('button');
                selectButton.type = 'button';
                selectButton.className = 'button media-picker__select';
                selectButton.textContent = 'Select';
                selectButton.setAttribute('data-media-select', item.url);

                body.appendChild(meta);
                body.appendChild(selectButton);

                card.appendChild(thumb);
                card.appendChild(body);
                fragment.appendChild(card);
            });

            libraryContainer.innerHTML = '';
            libraryContainer.appendChild(fragment);
        }

        function handleSelection(url, type) {
            if (!url) {
                return;
            }

            let applied = false;

            if (activeContext === 'editor') {
                if (typeof tinymce !== 'undefined') {
                    const editor = tinymce.get('content');
                    if (editor) {
                        if (type === 'video') {
                            editor.insertContent('<p><video controls style="max-width:100%;" src="' + url + '"></video></p>');
                        } else if (type === 'image') {
                            editor.insertContent('<p><img src="' + url + '" alt="" style="max-width:100%;"></p>');
                        } else {
                            editor.insertContent('<p><a href="' + url + '">' + url + '</a></p>');
                        }
                        applied = true;
                    }
                }
            } else if (activeContext) {
                const targetInput = document.querySelector(activeContext);
                if (targetInput) {
                    targetInput.value = url;
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    updatePreviewForInput(targetInput);
                    applied = true;
                }
            }

            const coverInput = document.querySelector('#cover_image');
            if (type === 'image' && coverInput && !coverInput.value) {
                coverInput.value = url;
                coverInput.dispatchEvent(new Event('input', { bubbles: true }));
                updatePreviewForInput(coverInput);
            }

            const graphicInput = document.querySelector('#graphic_content');
            if (graphicInput) {
                if (type === 'video' || !graphicInput.value) {
                    graphicInput.value = url;
                    graphicInput.dispatchEvent(new Event('input', { bubbles: true }));
                    updatePreviewForInput(graphicInput);
                }
            }

            closeModal();
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const context = button.getAttribute('data-open-media-picker');
                openModal(context || 'editor');
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const tab = button.getAttribute('data-media-tab');
                switchTab(tab);
                if (tab === 'library') {
                    loadLibrary();
                }
            });
        });

        libraryContainer.addEventListener('click', function (event) {
            const button = event.target.closest('[data-media-select]');
            if (!button) {
                return;
            }
            const card = button.closest('.media-picker__item');
            if (!card) {
                return;
            }
            const url = card.getAttribute('data-media-url');
            const type = card.getAttribute('data-media-type');
            handleSelection(url, type);
        });

        if (uploadForm) {
            uploadForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!apiUrl) {
                    return;
                }

                const fileInput = uploadForm.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || !fileInput.files.length) {
                    uploadFeedback.textContent = 'Please choose a file to upload.';
                    uploadFeedback.style.color = '#dc2626';
                    return;
                }

                const formData = new FormData();
                formData.append('media', fileInput.files[0]);
                formData.append('_token', modal.getAttribute('data-csrf') || '');

                uploadFeedback.textContent = 'Uploading...';
                uploadFeedback.style.color = '#64748b';

                fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return response.json().then(function (payload) {
                                throw payload;
                            });
                        }
                        return response.json();
                })
                    .then(function (payload) {
                        setToken(payload.token);
                        fileInput.value = '';
                        uploadFeedback.textContent = 'Upload successful.';
                        uploadFeedback.style.color = '#16a34a';
                        libraryLoaded = false;
                        const mediaItem = payload.data || {};
                        if (mediaItem.url) {
                            const targetInput = activeContext && activeContext !== 'editor'
                                ? document.querySelector(activeContext)
                                : null;
                            if (targetInput) {
                                targetInput.value = mediaItem.url;
                                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                                updatePreviewForInput(targetInput);
                            }
                            const coverInput = document.querySelector('#cover_image');
                            if (mediaItem.type === 'image' && coverInput && !coverInput.value) {
                                coverInput.value = mediaItem.url;
                                coverInput.dispatchEvent(new Event('input', { bubbles: true }));
                                updatePreviewForInput(coverInput);
                            }
                            const graphicInput = document.querySelector('#graphic_content');
                            if (graphicInput) {
                                if (mediaItem.type === 'video' || !graphicInput.value) {
                                    graphicInput.value = mediaItem.url;
                                    graphicInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    updatePreviewForInput(graphicInput);
                                }
                            }
                        }
                        if (activeContext) {
                            handleSelection(mediaItem.url, mediaItem.type || 'file');
                        }
                        loadLibrary();
                    })
                    .catch(function (payload) {
                        const message = payload && payload.error ? payload.error : 'Upload failed.';
                        const token = payload && payload.token ? payload.token : null;
                        if (token) {
                            setToken(token);
                        }
                        uploadFeedback.textContent = message;
                        uploadFeedback.style.color = '#dc2626';
                    });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-slug-target]').forEach(function (slugInput) {
            const sourceSelector = slugInput.getAttribute('data-slug-source');
            const source = sourceSelector
                ? document.querySelector(sourceSelector)
                : slugInput.closest('form')?.querySelector('[data-slug-source]');

            if (!source) {
                return;
            }

            let manualOverride = slugInput.value.trim() !== '';

            source.addEventListener('input', function () {
                if (manualOverride) {
                    return;
                }

                slugInput.value = slugify(source.value);
            });

            slugInput.addEventListener('input', function () {
                manualOverride = slugInput.value.trim() !== '';
            });
        });

        initTinyMCE();
        attachRichTextValidation();
        setupCopyButtons();
        setupMediaPicker();
        initMediaPreviews();
    });

    window.initTinyMCE = initTinyMCE;
})();
