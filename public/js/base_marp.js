// public/js/base_marp.js

document.addEventListener('DOMContentLoaded', function() {
    const editorTextareaMarp = document.getElementById('editor-marp');
    const previewDivMarp = document.getElementById('ppt-preview');
    const modeSelectMarp = document.getElementById('mode-select-marp-page');
    
    const baseUrl = typeof window.BASE_APP_URL !== 'undefined' ? window.BASE_APP_URL : '';
    if (baseUrl === '') {
        console.warn("ADVERTENCIA: window.BASE_APP_URL no está definida. Funcionalidades pueden fallar.");
    }
    // const csrfTokenMarpEditor = typeof window.CSRF_TOKEN_MARP_EDITOR !== 'undefined' ? window.CSRF_TOKEN_MARP_EDITOR : '';

    let marpDebounceTimer;

    if (!editorTextareaMarp) {
        console.error("Textarea #editor-marp no encontrado. Editor Marp no se inicializará.");
        return; 
    }

    const marpCodeMirrorEditor = CodeMirror.fromTextArea(editorTextareaMarp, {
        mode: 'markdown',
        theme: 'dracula',
        lineNumbers: true,
        lineWrapping: true,
        matchBrackets: true,
        placeholder: editorTextareaMarp.getAttribute('placeholder') || "Escribe tu presentación Marp aquí...",
        extraKeys: { "Enter": "newlineAndIndentContinueMarkdownList" }
    });

    function refreshMarpEditorLayout() {
        if (marpCodeMirrorEditor) {
            marpCodeMirrorEditor.setSize('100%', '100%');
            marpCodeMirrorEditor.refresh();
        }
    }
    setTimeout(refreshMarpEditorLayout, 50);

    async function updateMarpPreview() {
        if (!previewDivMarp || !marpCodeMirrorEditor) return;
        const markdownText = marpCodeMirrorEditor.getValue();
        previewDivMarp.innerHTML = '<p>Generando vista previa Marp...</p>';

        try {
            const renderEndpoint = baseUrl + '/markdown/render-marp-preview';
            const requestBody = `markdown=${encodeURIComponent(markdownText)}`;
            const headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
            // if (csrfTokenMarpEditor) { headers['X-CSRF-TOKEN'] = csrfTokenMarpEditor; }

            const response = await fetch(renderEndpoint, { method: 'POST', headers: headers, body: requestBody });

            if (!response.ok) {
                let errorDetail = await response.text();
                try {
                  const errorJson = JSON.parse(errorDetail);
                  errorDetail = errorJson.details || errorJson.error || errorDetail;
                } catch(e) { /* No era JSON */ }
                throw new Error(`Error del servidor: ${response.status} - ${errorDetail}`);
            }

            const htmlResult = await response.text();
            
            // --- INICIO DE LA CORRECCIÓN CON DOMPURIFY ---
            if (typeof DOMPurify !== 'undefined') {
                // Sanear el HTML antes de insertarlo
                // Configuración básica: permite HTML estándar.
                // Puedes necesitar configuraciones más específicas para Marp si elimina cosas importantes.
                const cleanHtml = DOMPurify.sanitize(htmlResult, { 
                    USE_PROFILES: { html: true },
                    // Ejemplo de configuración más permisiva si es necesario para Marp (¡USA CON PRECAUCIÓN!):
                    // ADD_TAGS: ['section', 'svg', 'foreignObject', 'style'], // Añade etiquetas que Marp usa
                    // ADD_ATTR: ['data-marpit-slide-index', 'data-line', 'viewBox'] // Añade atributos que Marp usa
                    // Investiga qué etiquetas/atributos específicos usa Marp y DOMPurify podría estar quitando.
                });
                previewDivMarp.innerHTML = cleanHtml;
            } else {
                // Fallback si DOMPurify no está cargado (menos seguro, como antes)
                console.warn("DOMPurify no está cargado. El HTML de la previsualización se inserta sin saneamiento adicional del lado del cliente.");
                previewDivMarp.innerHTML = htmlResult;
            }
            // --- FIN DE LA CORRECCIÓN CON DOMPURIFY ---

        } catch (error) {
            console.error("Error al generar vista previa Marp:", error);
            if (previewDivMarp) {
                previewDivMarp.innerHTML = ''; // Limpiar
                const errorParagraph = document.createElement('p');
                errorParagraph.style.color = 'red';
                errorParagraph.textContent = `Error al cargar la previsualización Marp: ${error.message}`;
                previewDivMarp.appendChild(errorParagraph);
            }
        }
    }

    if (marpCodeMirrorEditor) {
        marpCodeMirrorEditor.on('change', () => {
            clearTimeout(marpDebounceTimer);
            marpDebounceTimer = setTimeout(updateMarpPreview, 700);
        });
    }

    if (modeSelectMarp) {
        modeSelectMarp.addEventListener('change', function () {
            const selectedMode = this.value;
            if (selectedMode === 'markdown') {
                if (baseUrl) { window.location.href = baseUrl + '/markdown/create'; }
                else { console.error("BASE_URL no configurada (Marp)."); alert("Error de config.");}
            } else if (selectedMode === 'marp') {
                console.log("Modo Marp ya seleccionado.");
            }
        });
    }
    setTimeout(updateMarpPreview, 100); 
});