import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import ContentSEOAnalyzer from './seo-analyzer.js';

window.quillEditors = window.quillEditors || {};

const faTitles = {
    '.ql-bold': 'پررنگ (Bold)',
    '.ql-italic': 'مورب (Italic)',
    '.ql-underline': 'زیرخط (Underline)',
    '.ql-strike': 'خط‌خورده (Strikethrough)',
    '.ql-blockquote': 'نقل‌قول (Blockquote)',
    '.ql-code-block': 'بلوک کد (Code)',
    '.ql-header': 'سطح تیتر (Heading)',
    '.ql-font': 'نوع قلم (Font)',
    '.ql-size': 'اندازه قلم (Size)',
    '.ql-color': 'رنگ متن (Color)',
    '.ql-background': 'رنگ پس‌زمینه (Background)',
    '.ql-list[value="ordered"]': 'لیست شماره‌دار',
    '.ql-list[value="bullet"]': 'لیست نشانه‌دار',
    '.ql-script[value="sub"]': 'پانویس (Subscript)',
    '.ql-script[value="super"]': 'بالانویس (Superscript)',
    '.ql-indent[value="-1"]': 'کاهش تورفتگی',
    '.ql-indent[value="+1"]': 'افزایش تورفتگی',
    '.ql-direction[value="rtl"]': 'جهت متن راست‌به‌چپ',
    '.ql-align': 'تراز متن (Align)',
    '.ql-link': 'درج لینک (Link)',
    '.ql-image': 'درج تصویر (Image)',
    '.ql-video': 'درج ویدیو (Video)',
    '.ql-clean': 'حذف فرمت (Clean)'
};

function initQuillEditors() {
    try {
        let keywordInput = document.querySelector('#keyword');
        let dirx = document.querySelector('#panel-dir')?.value || 'rtl';

        document.querySelectorAll('.ckeditorx, .quill-editor').forEach(function (el) {
            if (el.dataset.quillInitialized === 'true') return;
            if (!el.parentNode) return;

            el.dataset.quillInitialized = 'true';

            const name = el.getAttribute('name') || 'editor_' + Math.random().toString(36).substring(2, 9);
            const currentDir = el.getAttribute('dir');
            let finalDir = currentDir ? currentDir : dirx;

            // Create Quill container
            const container = document.createElement('div');
            container.className = 'quill-editor-container';

            el.parentNode.insertBefore(container, el);
            el.style.display = 'none';

            // Initialize standard Quill Snow theme
            const quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            [{ 'direction': 'rtl' }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: function () {
                                const input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.setAttribute('accept', 'image/*');
                                input.click();

                                input.onchange = async () => {
                                    const file = input.files[0];
                                    if (file) {
                                        const formData = new FormData();
                                        formData.append('upload', file);

                                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                        if (csrfToken) {
                                            formData.append('_token', csrfToken);
                                        }

                                        try {
                                            const response = await fetch(window.xupload || '/admin/ckeditor/upload', {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                                                },
                                                body: formData
                                            });

                                            const data = await response.json();
                                            if (data && data.url) {
                                                const range = quill.getSelection(true) || { index: quill.getLength() };
                                                quill.insertEmbed(range.index, 'image', data.url);
                                                quill.setSelection(range.index + 1);
                                            }
                                        } catch (err) {
                                            console.error('Quill image upload failed:', err);
                                        }
                                    }
                                };
                            }
                        }
                    }
                }
            });

            // Set toolbar to LTR for clean icon/dropdown alignment, set button types and Persian tooltips
            const toolbarEl = container.previousElementSibling;
            if (toolbarEl && toolbarEl.classList.contains('ql-toolbar')) {
                toolbarEl.setAttribute('dir', 'ltr');
                toolbarEl.querySelectorAll('button').forEach(function (button) {
                    button.setAttribute('type', 'button');
                });

                if (finalDir === 'rtl') {
                    for (const [selector, title] of Object.entries(faTitles)) {
                        const target = toolbarEl.querySelector(selector);
                        if (target) {
                            target.setAttribute('title', title);
                        }
                    }
                }
            }

            // Set initial HTML content
            if (el.value && el.value.trim() !== '') {
                quill.clipboard.dangerouslyPasteHTML(el.value);
            }

            // Apply direction if RTL to the editor content area
            const editorEl = container.querySelector('.ql-editor');
            if (editorEl && finalDir === 'rtl') {
                editorEl.setAttribute('dir', 'rtl');
                quill.format('direction', 'rtl');
                quill.format('align', 'right');
            }

            // Sync with hidden textarea
            const updateTextarea = () => {
                const text = quill.getText().trim();
                const html = text.length === 0 ? '' : quill.root.innerHTML;
                el.value = html;

                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));

                if (el.classList.contains('seo-analyze')) {
                    let keyword = keywordInput?.value;
                    const analyzer = new ContentSEOAnalyzer(html, keyword);
                    const report = analyzer.generateReport();
                    analyzer.displaySEOReport(report, 'seo-hint');
                }
            };

            quill.on('text-change', updateTextarea);

            // Sync on form submit
            if (el.form) {
                el.form.addEventListener('submit', function () {
                    const text = quill.getText().trim();
                    el.value = text.length === 0 ? '' : quill.root.innerHTML;
                });
            }

            if (el.classList.contains('seo-analyze')) {
                updateTextarea();
                keywordInput?.addEventListener('input', function () {
                    updateTextarea();
                });
            }

            window.quillEditors[name] = quill;
        });
    } catch (e) {
        console.error('Quill initialization error:', e);
    }
}

export { initQuillEditors };
