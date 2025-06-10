---
marp: true
theme: gaia
class: lead
paginate: true
style: |
  section {
    background: #f0f0f0;
    color: #333;
  }
  h1 {
    color: #1e88e5;
  }
---

# 🚀 Presentación Ejecutiva

**Sistema `markdown2video`**  
Versión 1.0 - 2025

---

## 📋 Agenda

1. Introducción
2. Características del Sistema
3. Casos de Uso
4. Arquitectura
5. Demo Técnica
6. Conclusiones y Preguntas

---

## 🧠 Introducción

> Transformamos ideas escritas en presentaciones impactantes, exportables a **video, HTML o PDF**.

---

## ✨ Características

- ✅ Conversión de Markdown a video
- 🎨 Temas Marp personalizados
- 🧑‍💻 Código fuente en slides
- 🎙️ Voz en off (IA opcional)
- 🌐 Integración con plataformas web

---

## 🔢 Lista ordenada de funciones

1. Renderizado de Markdown
2. Generación de video
3. Reproductor integrado
4. Control de versiones
5. Exportación a múltiples formatos

---

## 📊 Tabla comparativa

| Sistema        | Exporta a video | Código fuente | IA Voz |
|----------------|-----------------|---------------|--------|
| PowerPoint     | ❌              | ❌            | ❌     |
| Google Slides  | ❌              | ❌            | ❌     |
| **markdown2video** | ✅         | ✅            | ✅     |

---

## 💻 Código de ejemplo

```php
<?php
require 'vendor/autoload.php';
use Dales\Markdown2video\Config\Database;

$db = new Database();
$result = $db->selectAll("SELECT * FROM presentaciones");
