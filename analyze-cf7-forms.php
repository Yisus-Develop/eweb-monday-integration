<?php
// analyze-cf7-forms.php
// Análisis profundo de formularios Contact Form 7 para mapeo a Monday

$site_url = 'https://marschallenge.space';
$username = 'wmaster_cs4or9qs';
$app_password = str_replace(' ', '', 'THuf tLn6 MQXG bCsS usyN yBca');

echo "=== ANÁLISIS DE FORMULARIOS CONTACT FORM 7 ===\n\n";

$auth = base64_encode("$username:$app_password");
$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic $auth\r\n"
    ]
]);

// 1. Listar todos los formularios
$endpoint = "$site_url/wp-json/contact-form-7/v1/contact-forms";
$response = @file_get_contents($endpoint, false, $context);

if ($response === false) {
    die("❌ Error: No se pudo conectar a WordPress API\n");
}

$forms = json_decode($response, true);
echo "✅ Formularios encontrados: " . count($forms) . "\n\n";

$formsAnalysis = [];

foreach ($forms as $form) {
    $formId = $form['id'];
    $formTitle = $form['title'];
    
    echo "📋 FORMULARIO: $formTitle (ID: $formId)\n";
    echo str_repeat("-", 60) . "\n";
    
    // Obtener detalles del formulario
    $detailUrl = "$endpoint/$formId";
    $detailResponse = @file_get_contents($detailUrl, false, $context);
    
    if (!$detailResponse) {
        echo "   ⚠️ No se pudo obtener detalle\n\n";
        continue;
    }
    
    $detail = json_decode($detailResponse, true);
    $formContent = $detail['properties']['form'] ?? '';
    
    if (empty($formContent) || !is_string($formContent)) {
        echo "   ⚠️ Formulario vacío o inaccesible\n\n";
        continue;
    }
    
    // Extraer campos del formulario
    // Patrón: [tipo* nombre-campo "Label"]
    preg_match_all('/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)(?:\s+"([^"]*)")?/', $formContent, $matches, PREG_SET_ORDER);
    
    $fields = [];
    foreach ($matches as $match) {
        $type = str_replace('*', '', $match[1]); // Quitar asterisco de requerido
        $fieldName = $match[2];
        $label = $match[3] ?? $fieldName;
        
        // Filtrar campos de sistema
        if (in_array($fieldName, ['_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag'])) {
            continue;
        }
        
        $fields[] = [
            'name' => $fieldName,
            'type' => $type,
            'label' => $label
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

// Guardar análisis completo
file_put_contents('cf7_forms_analysis.json', json_encode($formsAnalysis, JSON_PRETTY_PRINT));
echo "✅ Análisis guardado en: cf7_forms_analysis.json\n\n";

// Generar reporte de mapeo
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
        // Sugerir mapeo basado en nombre del campo
        $mondayColumn = 'TBD';
        $mondayId = 'TBD';
        
        // Mapeo inteligente
        if (stripos($field['name'], 'email') !== false || stripos($field['name'], 'correo') !== false) {
            $mondayColumn = 'E-mail';
            $mondayId = 'lead_email';
        } elseif (stripos($field['name'], 'name') !== false || stripos($field['name'], 'nombre') !== false) {
            $mondayColumn = 'Nombre';
            $mondayId = 'name';
        } elseif (stripos($field['name'], 'phone') !== false || stripos($field['name'], 'telefono') !== false) {
            $mondayColumn = 'Teléfono';
            $mondayId = 'lead_phone';
        } elseif (stripos($field['name'], 'company') !== false || stripos($field['name'], 'empresa') !== false) {
            $mondayColumn = 'Empresa';
            $mondayId = 'lead_company';
        } elseif (stripos($field['name'], 'country') !== false || stripos($field['name'], 'pais') !== false) {
            $mondayColumn = 'País';
            $mondayId = 'text_mkyn95hk';
        } elseif (stripos($field['name'], 'message') !== false || stripos($field['name'], 'mensaje') !== false) {
            $mondayColumn = '(Ignorar o campo personalizado)';
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
echo "✅ Guía de mapeo generada: FORMS-MAPPING-GUIDE.md\n";
