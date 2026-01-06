# Guía de Archivos - Integración Monday.com

Esta carpeta contiene el núcleo de la integración entre WordPress (Contact Form 7) y Monday.com. Aquí tienes la explicación de cada componente:

## 🔌 Componentes de WordPress

### [monday-webhook-trigger.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/monday-webhook-trigger.php)

**El Disparador.** Es el plugin que debes instalar en WordPress. Detecta cuando se envía un formulario de CF7, guarda una copia en la base de datos local (como respaldo) y envía el lead al procesador. Incluye el Dashboard administrativo para reenviar leads si fallara internet.

### [cf7-forms-extractor.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/cf7-forms-extractor.php)

**Herramienta de Mapeo.** Un plugin de utilidad que te ayuda a listar todos tus formularios de Contact Form 7 y extraer sus campos. Es fundamental para saber qué "tags" usa cada formulario.

---

## ⚙️ Núcleo de Procesamiento (Handler)

### [webhook-handler.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/webhook-handler.php)

**El Cerebro.** Este archivo recibe los datos de WordPress. Se encarga de la limpieza de nombres, detección de duplicados (y creación de actualizaciones/notas), y coordina con la API de Monday.

### [LeadScoring.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/LeadScoring.php)

**La Inteligencia.** Calcula la puntuación del lead (0-36 pts) basándose en el perfil, país, tamaño de institución y contexto comercial (organización/interés). Clasifica el lead como HOT, WARM o COLD.

### [MondayAPI.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php)

**El Cartero.** Gestiona toda la comunicación técnica con la API de Monday.com (GraphQL). Maneja la creación de items, sub-items y actualizaciones de columnas.

---

## 🛠️ Configuración y Constantes

### [config.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/config.php)

**Credenciales.** Aquí se guarda el API Token de Monday, el ID del tablero y la configuración de Debug. **Es el archivo más importante para la conexión.**

### [NewColumnIds.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php)

**Mapa de Columnas.** Contiene los IDs internos de las columnas de Monday. Si cambias o creas una columna nueva en Monday, debes actualizar su ID aquí.

### [StatusConstants.php](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/StatusConstants.php)

**Diccionario.** Define las etiquetas fijas (como "Lead", "Universidad", etc.) para asegurar que el código siempre use los textos exactos que Monday espera.

### [language-config.json](file:///C:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/language-config.json)

**Preferencias.** Lista de países prioritarios y configuraciones de idioma para el scoring.

---

## 📂 Otros

- **archive/**: Carpeta que contiene versiones antiguas, scripts de diagnóstico y logs de pruebas pasadas para mantener la raíz limpia.
