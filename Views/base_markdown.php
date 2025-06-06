<?php
// --- Views/base_markdown.php ---
$base_url = $base_url ?? ''; // Pasada por MarkdownController->create()
$pageTitle = $pageTitle ?? 'Editor Markdown';
// Token CSRF para la acción de generar PDF (si lo implementas)
// En MarkdownController->create():
// if (empty($_SESSION['csrf_token_generate_pdf'])) { $_SESSION['csrf_token_generate_pdf'] = bin2hex(random_bytes(32)); }
// $csrf_token_generate_pdf = $_SESSION['csrf_token_generate_pdf'];
$csrf_token_generate_pdf = $csrf_token_generate_pdf ?? ''; // Pásalo desde el controlador

// Definir variables globales de JavaScript ANTES de incluir el script externo
echo "<script>\n";
echo "  window.BASE_APP_URL = " . json_encode($base_url) . ";\n";
// Pasar el token CSRF a JavaScript si lo vas a usar en el fetch
echo "  window.CSRF_TOKEN_PDF_GENERATE = " . json_encode($csrf_token_generate_pdf) . ";\n";
echo "</script>\n";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/codemirror.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/theme/dracula.min.css">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/header.css">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/base_markdown.css">
</head>
<body>
  <?php if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . 'header.php')) { include VIEWS_PATH . 'header.php'; } ?>

  <div class="container">
    <div class="editor-container">
      <div class="editor-header">
        <h2>Editor</h2>
        <select id="mode-select" class="mode-selector">
          <option value="markdown" selected>Markdown Estándar</option>
          <option value="marp">Marp</option>
        </select>
      </div>
      <div class="editor-body"><textarea id="editor" class="editor" placeholder="Escribe tu presentación aquí..."></textarea></div>
    </div>
    <div class="preview-container">
      <div class="preview-header"><h2>Vista Previa</h2></div>
      <div class="preview-body"><div id="ppt-preview" class="ppt-preview"><p>La vista previa se mostrará aquí...</p></div></div>
      <div class="button-container">
        <button class="generate-btn" data-action="generate-ppt">Generar PPT</button>
        <button class="generate-btn" id="generatePdfBtnHtml">Generar PDF (desde Preview)</button> <!-- ID ESPECÍFICO -->
        <button class="generate-btn" data-action="generate-mp4">Generar Video MP4</button>
        <button class="generate-btn" data-action="generate-html">Generar HTML</button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/codemirror.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/mode/markdown/markdown.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/addon/edit/continuelist.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.59.4/addon/display/placeholder.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  
  <script src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/js/base_markdown.js"></script>
</body>
</html>