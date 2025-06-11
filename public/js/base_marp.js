// public/js/base_marp.js

document.addEventListener('DOMContentLoaded', function() {
    const editorTextareaMarp = document.getElementById('editor-marp');
    const previewDivMarp = document.getElementById('ppt-preview');
    const modeSelectMarp = document.getElementById('mode-select-marp-page');
    
    const baseUrl = typeof window.BASE_APP_URL !== 'undefined' ? window.BASE_APP_URL : '';
    if (baseUrl === '') {
        console.warn("ADVERTENCIA: window.BASE_APP_URL no está definida. Funcionalidades pueden fallar.");
    }

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
            
            if (typeof DOMPurify !== 'undefined') {
                const cleanHtml = DOMPurify.sanitize(htmlResult, { USE_PROFILES: { html: true } });
                previewDivMarp.innerHTML = cleanHtml;
            } else {
                console.warn("DOMPurify no está cargado. El HTML de la previsualización se inserta sin saneamiento.");
                previewDivMarp.innerHTML = htmlResult;
            }

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
            }
        });
    }
    setTimeout(updateMarpPreview, 100); 

    // --- INICIO DEL BLOQUE MODIFICADO ---
    // Manejar clics en los botones de generación (PDF, Video, etc.)
    const generateButtons = document.querySelectorAll('.generate-btn');
    generateButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const format = this.dataset.format; // pdf, mp4, etc.
            if (!marpCodeMirrorEditor) {
                alert('El editor Marp no está inicializado.');
                return;
            }
            const markdownContent = marpCodeMirrorEditor.getValue();

            if (!markdownContent.trim()) {
                alert('El editor está vacío. Escribe algo de Markdown para Marp.');
                return;
            }

            // Guardar texto original y mostrar indicador de carga
            const originalButtonHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generando...`;

            try {
                let generateEndpoint;
                const requestBody = new FormData();
                requestBody.append('markdown', markdownContent);

                if (format === 'mp4') {
                    generateEndpoint = `${baseUrl}/markdown/generate-video-from-marp`;
                } else {
                    // Para PDF y otros formatos que maneja este endpoint
                    generateEndpoint = `${baseUrl}/markdown/generate-marp-file`;
                    requestBody.append('format', format); // PDF necesita saber el formato
                }

                const response = await fetch(generateEndpoint, {
                    method: 'POST',
                    body: requestBody,
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || `Error del servidor (${response.status})`);
                }

                if (result.success) {
                    // El backend ahora devuelve una URL para redirigir, tanto para PDF como para Video.
                    // Para PDF: result.downloadPageUrl
                    // Para Video: result.redirectUrl
                    const redirectUrl = result.downloadPageUrl || result.redirectUrl;
                    
                    if (redirectUrl) {
                        // La redirección se maneja aquí.
                        window.location.href = redirectUrl;
                    } else {
                        throw new Error('Respuesta del servidor exitosa, pero no se proporcionó una URL de redirección.');
                    }
                } else {
                    throw new Error(result.error || 'Falló la generación del archivo por un motivo desconocido.');
                }

            } catch (error) {
                console.error(`Error al generar ${format.toUpperCase()}:`, error);
                alert(`Hubo un error al generar el archivo ${format.toUpperCase()}:\n${error.message}`);
                // Solo restaurar el botón si no hubo redirección
                this.disabled = false;
                this.innerHTML = originalButtonHTML;
            }
        });
    });
    // --- FIN DEL BLOQUE MODIFICADO ---
});