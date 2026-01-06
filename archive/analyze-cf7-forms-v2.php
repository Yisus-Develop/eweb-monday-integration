<?php
// analyze-cf7-forms-v2.php
// Análisis de formularios CF7 usando el endpoint correcto del CPT

$site_url = 'https://marschallenge.space';
$username = 'wmaster_cs4or9qs';
$app_password = str_replace(' ', '', 'THuf tLn6 MQXG bCsS usyN yBca');

echo "=== ANÁLISIS DE FORMULARIOS CF7 (Método CPT) ===\n\n";

$auth = base64_encode("$username:$app_password");
$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic $auth\r\n"
    ]
]);

// 1. Listar formularios usando el endpoint del CPT
$endpoint = "$site_url/wp-json/wp/v2/wpcf7_contact_form?per_page=50";
echo "Consultando: $endpoint\n";

$response = @file_get_contents($endpoint, false, $context);

if ($response === false) {
    die("❌ Error: No se pudo acceder al endpoint wpcf7_contact_form\n");
}

$forms = json_decode($response, true);

if (empty($forms)) {
    die("❌ No se encontraron formularios o no hay permisos\n");
}

echo "✅ Formularios encontrados: " . count($forms) . "\n\n";

$formsAnalysis = [];

foreach ($forms as $form) {
    $formId = $form['id'];
    $formTitle = $form['title']['rendered'] ?? 'Sin título';
    
    echo "📋 FORMULARIO: $formTitle (ID: $formId)\n";
    echo str_repeat("-", 60) . "\n";
    
    // El contenido del formulario está en 'content'
    $formContent = $form['content']['rendered'] ?? '';
    
    if (empty($formContent) || !is_string($formContent)) {
        echo "   ⚠️ Sin contenido accesible\n\n";
        continue;
    }
    
    // Limpiar HTML para obtener solo los shortcodes
    $formContent = strip_tags($formContent);
    
    // Extraer campos del formulario
    // Patrón mejorado para CF7: [tipo* nombre-campo ...]
    preg_match_all('/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)(?:\s+([^\]]*))?\]/', $formContent, $matches, PREG_SET_ORDER);
    
    $fields = [];
    foreach ($matches as $match) {
        $type = str_replace('*', '', $match[1]); // Quitar asterisco de requerido
        $fieldName = $match[2];
        $options = $match[3] ?? '';
        
        // Filtrar campos de sistema y submit
        if (in_array($fieldName, ['_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag']) 
            || in_array($type, ['submit', 'acceptance'])) {
            continue;
        }
        
        // Extraer label si existe en las opciones
        $label = $fieldName;
        if (preg_match('/"([^"]+)"/', $options, $labelMatch)) {
            $label = $labelMatch[1];
        }
        
        $fields[] = [
            'name' => $fieldName,
            'type' => $type,
            'label' => $label,
            'options' => trim($options)
        ];
    }
    
    echo "   Campos detectados: " . count($fields) . "\n";
    foreach ($fields as $field) {
        echo "   • {$field['name']} ({$field['type']}) - \"{$field['label']}\"\n";
    }
    echo "\n";
    
    $formsAnalysis[] = [
        'id' => $formId,
        'title' => $formTitle,
        'fields' => $fields
    ];
}

// Guardar análisis
file_put_contents('cf7_forms_analysis.json', json_encode($formsAnalysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Análisis guardado: cf7_forms_analysis.json\n\n";

// Generar guía de mapeo
echo "=== GENERANDO GUÍA DE MAPEO ===\n\n";

$mappingGuide = "# Guía de Mapeo: Contact Form 7 → Monday CRM\n\n";
$mappingGuide .= "**Fecha**: " . date('Y-m-d H:i:s') . "\n";
$mappingGuide .= "**Total Formularios**: " . count($formsAnalysis) . "\n\n";

foreach ($formsAnalysis as $formData) {
    $mappingGuide .= "## 📝 {$formData['title']}\n\n";
    $mappingGuide .= "**ID del Formulario**: `{$formData['id']}`\n\n";
    
    if (empty($formData['fields'])) {
        $mappingGuide .= "*Sin campos detectados*\n\n";
        continue;
    }
    
    $mappingGuide .= "| Campo WP | Tipo | Label | → Columna Monday | ID Monday |\n";
    $mappingGuide .= "|----------|------|-------|------------------|------------|\n";
    
    foreach ($formData['fields'] as $field) {
        // Mapeo inteligente
        $mondayColumn = 'TBD';
        $mondayId = 'TBD';
        
        $fieldLower = strtolower($field['name']);
        
        if (stripos($fieldLower, 'email') !== false || stripos($fieldLower, 'correo') !== false) {
            $mondayColumn = 'E-mail';
            $mondayId = 'lead_email';
        } elseif (stripos($fieldLower, 'name') !== false || stripos($fieldLower, 'nombre') !== false) {
            $mondayColumn = 'Nombre';
            $mondayId = 'name';
        } elseif (stripos($fieldLower, 'phone') !== false || stripos($fieldLower, 'telefono') !== false || stripos($fieldLower, 'tel') !== false) {
            $mondayColumn = 'Teléfono';
            $mondayId = 'lead_phone';
        } elseif (stripos($fieldLower, 'company') !== false || stripos($fieldLower, 'empresa') !== false || stripos($fieldLower, 'org') !== false || stripos($fieldLower, 'institucion') !== false) {
            $mondayColumn = 'Empresa';
            $mondayId = 'lead_company';
        } elseif (stripos($fieldLower, 'country') !== false || stripos($fieldLower, 'pais') !== false) {
            $mondayColumn = 'País';
            $mondayId = 'text_mkyn95hk';
        } elseif (stripos($fieldLower, 'cargo') !== false || stripos($fieldLower, 'puesto') !== false || stripos($fieldLower, 'role') !== false) {
            $mondayColumn = 'Puesto / Rol Detectado';
            $mondayId = 'text / color_mkyng649';
        } elseif (stripos($fieldLower, 'message') !== false || stripos($fieldLower, 'mensaje') !== false || stripos($fieldLower, 'comment') !== false) {
            $mondayColumn = '(Ignorar - muy largo)';
            $mondayId = 'N/A';
        }
        
        $mappingGuide .= "| `{$field['name']}` | {$field['type']} | {$field['label']} | {$mondayColumn} | `{$mondayId}` |\n";
    }
    
    $mappingGuide .= "\n";
}

$mappingGuide .= "---\n\n";
$mappingGuide .= "## 🎯 Columnas Monday Disponibles\n\n";
$mappingGuide .= "| Columna | ID | Tipo |\n";
$mappingGuide .= "|---------|-------|------|\n";
$mappingGuide .= "| Nombre | `name` | name |\n";
$mappingGuide .= "| E-mail | `lead_email` | email |\n";
$mappingGuide .= "| Teléfono | `lead_phone` | phone |\n";
$mappingGuide .= "| Empresa | `lead_company` | text |\n";
$mappingGuide .= "| Puesto | `text` | text |\n";
$mappingGuide .= "| Estado | `lead_status` | status |\n";
$mappingGuide .= "| **Lead Score** | `numeric_mkyn2py0` | numbers |\n";
$mappingGuide .= "| **Clasificación** | `color_mkyn199t` | status |\n";
$mappingGuide .= "| **Rol Detectado** | `color_mkyng649` | status |\n";
$mappingGuide .= "| **País** | `text_mkyn95hk` | text |\n";

file_put_contents('../../FORMS-MAPPING-GUIDE.md', $mappingGuide);
echo "✅ Guía generada: FORMS-MAPPING-GUIDE.md\n";
