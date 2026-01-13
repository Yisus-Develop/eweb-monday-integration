# Guía de Archivos - Integración Monday.com Unified 🚀

Esta carpeta contiene el núcleo de la integración unificada entre WordPress y Monday.com. Ahora todo vive dentro de un solo plugin para facilitar actualizaciones automáticas vía GitHub.

## 🔌 El Plugin Central

### [monday-webhook-trigger.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/monday-webhook-trigger.php)

**El Todo-en-Uno.** Es el archivo principal. Gestiona:

1. **Auto-Updater:** Se conecta a GitHub para recibir mejoras automáticamente.
2. **Dashboard de Logs:** Permite ver y re-enviar leads desde WordPress.
3. **Ajustes de Seguridad:** Nueva pestaña de **Configuración** para guardar tu API Token e ID de Tablero de forma segura (sin subirlos a GitHub).
4. **Procesamiento Interno:** Ya no hace llamadas HTTP externas; procesa todo internamente para ser más rápido y fiable.

---

## 📂 Carpeta `includes/` (El Motor)

Para mantener la raíz limpia, toda la lógica avanzada está en esta carpeta:

* **`class-monday-handler.php`**: El cerebro que recibe los datos y coordina el scoring y el envío a Monday.
* **`LeadScoring.php`**: Calcula la puntuación (0-36 pts) y detecta roles/idiomas.
* **`MondayAPI.php`**: Gestiona la comunicación técnica con Monday.com.
* **`NewColumnIds.php`** y **`StatusConstants.php`**: Mapas de IDs y etiquetas fijas.
* **`class-eweb-github-updater.php`**: Motor de actualizaciones automáticas.
* **`language-config.json`**: Configuración de países e idiomas.

---

## 🔒 Notas de Seguridad Especiales

> [!IMPORTANT]
> **No más archivos `config.php`**. Las credenciales ahora se guardan directamente en la base de datos de WordPress a través de la pantalla de ajustes del plugin.
> Esto permite que el repositorio de GitHub sea público sin exponer tus tokens privados.

---

## 🛠️ Utilidades

* `cf7-forms-extractor.php`: Herramienta para auditar campos de tus formularios CF7.
* `archive/`: Respaldos, logs antiguos y scripts de diagnóstico.
