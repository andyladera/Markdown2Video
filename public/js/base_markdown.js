// public/js/base_markdown.js
document.addEventListener('DOMContentLoaded', function () {
    const editorTextarea = document.getElementById('editor');
    const previewDiv = document.getElementById('ppt-preview'); // El div donde marked.js renderiza el HTML
    const modeSelect = document.getElementById('mode-select');
    const generatePdfBtnHtml = document.getElementById('generatePdfBtnHtml'); // Nuevo ID
    
    const baseUrlJs = typeof window.BASE_APP_URL !== 'undefined' ? window.BASE_APP_URL : '';
    const csrfTokenPdfGenerate = typeof window.CSRF_TOKEN_PDF_GENERATE !== 'undefined' ? window.CSRF_TOKEN_PDF_GENERATE : '';

    if (baseUrlJs === '') { console.warn("JS WARN: window.BASE_APP_URL no definida."); }

    if (!editorTextarea) { console.error("JS ERROR: Textarea #editor no encontrado."); return; }
    let editorInstance = null;
    try {
        editorInstance = CodeMirror.fromTextArea(editorTextarea, {
            lineNumbers: true, mode: "markdown", theme: "dracula", lineWrapping: true,
            matchBrackets: true, placeholder: editorTextarea.getAttribute('placeholder') || "Escribe...",
            extraKeys: { "Enter": "newlineAndIndentContinueMarkdownList" }
        });
    } catch(e) { console.error("JS ERROR: CodeMirror init falló:", e); return; }

    function refreshEditor() { if (editorInstance) { editorInstance.setSize('100%', '100%'); editorInstance.refresh(); } }
    setTimeout(refreshEditor, 100); // Dar tiempo al DOM

    function updateMarkdownPreview() {
        if (!previewDiv) return;
        if (typeof marked !== 'undefined' && editorInstance) {
            try { previewDiv.innerHTML = marked.parse(editorInstance.getValue()); }
            catch (e) { console.error("JS Error marked.js:", e); previewDiv.innerHTML = "<p style='color:red;'>Error preview.</p>"; }
        } else if (typeof marked === 'undefined') { previewDiv.innerHTML = "<p style='color:orange;'>Marked.js no cargado.</p>"; }
    }
    if (editorInstance) { editorInstance.on("change", updateMarkdownPreview); setTimeout(updateMarkdownPreview, 150); }

    if (modeSelect) {
        modeSelect.addEventListener("change", function () {
            const selectedMode = this.value;
            if (selectedMode === "marp") {
                if (baseUrlJs) { window.location.href = baseUrlJs + '/markdown/marp-editor'; }
                else { console.error("JS ERROR: BASE_URL no config para Marp."); }
            } else if (selectedMode === "markdown") { console.log("JS: Modo Markdown seleccionado."); if (editorInstance) updateMarkdownPreview(); }
        });
    }

    // --- Funcionalidad para el botón "Generar PDF (desde Preview)" ---
    if (generatePdfBtnHtml && previewDiv) { // Verificar que previewDiv exista
        console.log("JS DEBUG: Botón #generatePdfBtnHtml encontrado. Añadiendo listener.");
        generatePdfBtnHtml.addEventListener('click', async function() {
            console.log("JS DEBUG: Clic en 'Generar PDF (desde Preview)'.");
            
            const htmlContentForPdf = previewDiv.innerHTML; // <--- TOMA EL HTML DEL PREVIEW

            if (!htmlContentForPdf.trim() || htmlContentForPdf.includes("La vista previa se mostrará aquí...")) {
                alert("La vista previa está vacía. Escribe algo en el editor y espera a que se genere la previsualización.");
                return;
            }

            const originalButtonText = this.textContent;
            this.textContent = 'Generando PDF...';
            this.disabled = true;

            try {
                const endpoint = baseUrlJs + '/markdown/generate-pdf-from-html'; // NUEVO ENDPOINT
                console.log("JS DEBUG: Enviando HTML a endpoint:", endpoint);
                
                const bodyParams = new URLSearchParams();
                bodyParams.append('html_content', htmlContentForPdf); // Envía 'html_content'
                if (csrfTokenPdfGenerate) { // Añadir CSRF si está disponible
                    bodyParams.append('csrf_token_generate_pdf', csrfTokenPdfGenerate);
                } else { console.warn("JS WARN: CSRF Token para generar PDF no encontrado."); }


                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyParams.toString()
                });
                console.log("JS DEBUG: Respuesta Fetch PDF - Status:", response.status, "OK:", response.ok);

                if (!response.ok) {
                    let errorMsg = `Error del servidor: ${response.status}`;
                    const errorTextAttempt = await response.text();
                    try { const errorData = JSON.parse(errorTextAttempt); errorMsg = errorData.error || errorData.message || errorMsg; }
                    catch (e) { if(errorTextAttempt) errorMsg += ` - ${errorTextAttempt.substring(0,100)}`;}
                    throw new Error(errorMsg);
                }

                const data = await response.json();
                console.log("JS DEBUG: Datos respuesta backend PDF:", data);

                if (data.success && data.downloadPageUrl) {
                    const downloadPageFullUrl = baseUrlJs + data.downloadPageUrl;
                    console.log("JS DEBUG: Abriendo pág. descarga:", downloadPageFullUrl);
                    window.open(downloadPageFullUrl, '_blank');
                } else if (data.error) { alert(`Error al generar PDF: ${data.error}`); }
                else { alert("Respuesta inesperada del servidor (PDF)."); }

            } catch (error) {
                console.error("JS ERROR en func. generar PDF (catch):", error);
                alert(`Ocurrió un error: ${error.message}`);
            } finally {
                this.textContent = originalButtonText;
                this.disabled = false;
            }
        });
    } else {
        if (!generatePdfBtnHtml) console.warn("JS WARN: Botón #generatePdfBtnHtml NO encontrado.");
        if (!previewDiv) console.warn("JS WARN: Div #ppt-preview NO encontrado para PDF.");
    }
});