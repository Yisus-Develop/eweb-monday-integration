<?php
// webhook-handler.php
// Manejador Universal para TODOS los formularios Contact Form 7 → Monday CRM

require_once 'config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Solo POST permitido');
}

// Obtener datos (JSON o Form-Data)
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

// Logging
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Datos: " . print_r($data, true) . "\n", FILE_APPEND);

try {
    // ===== MAPEO DINÁMICO DE CAMPOS =====
    // Detecta automáticamente los campos sin importar el formulario
    
    $scoringData = [
        // Nombre (múltiples variantes)
        'name' => $data['nombre'] ?? $data['contact_name'] ?? $data['your-name'] ?? $data['ea_firstname'] . ' ' . ($data['ea_lastname'] ?? '') ?? 'Sin Nombre',
        
        // Email (múltiples variantes)
        'email' => $data['email'] ?? $data['ea_email'] ?? $data['your-email'] ?? '',
        
        // Teléfono
        'phone' => $data['telefono'] ?? $data['your-phone'] ?? '',
        
        // Empresa/Organización (múltiples variantes)
        'company' => $data['org_name'] ?? $data['company'] ?? $data['entity'] ?? $data['institucion'] ?? $data['ea_institution'] ?? '',
        
        // Rol/Puesto
        'role' => $data['tipo_institucion'] ?? $data['sector'] ?? $data['interes'] ?? $data['especialidad'] ?? $data['ea_role'] ?? '',
        
        // País (hidden o text)
        'country' => $data['pais_cf7'] ?? $data['pais_otro'] ?? $data['ea_country'] ?? '',
        
        // Ciudad
        'city' => $data['ciudad_cf7'] ?? $data['ea_city'] ?? '',
        
        // PERFIL (CRÍTICO para scoring)
        'perfil' => $data['perfil'] ?? 'general',
        
        // Campos adicionales para scoring
        'tipo_institucion' => $data['tipo_institucion'] ?? '',
        'numero_estudiantes' => (int)($data['numero_estudiantes'] ?? 0),
        'poblacion' => (int)($data['poblacion'] ?? 0),
        'modality' => $data['modality'] ?? '',
    ];
    
    // Validación mínima
    if (empty($scoringData['email'])) {
        throw new Exception('Email es requerido');
    }
    
    // ===== CALCULAR LEAD SCORE =====
    $scoreResult = LeadScoring::calculate($scoringData);
    
    // ===== DETERMINAR ROL DETECTADO =====
    $detectedRole = 'General';
    switch ($scoringData['perfil']) {
        case 'pioneer':
            $detectedRole = 'Mission Partner';
            break;
        case 'institucion':
            $detectedRole = 'Rector/Director';
            break;
        case 'ciudad':
            $detectedRole = 'Alcalde/Gobierno';
            break;
        case 'empresa':
            $detectedRole = 'Corporate';
            break;
        case 'mentor':
            $detectedRole = 'Maestro/Mentor';
            break;
        case 'pais':
            $detectedRole = 'Interesado País';
            break;
        case 'zer':
            $detectedRole = 'Joven';
            break;
    }
    
    // ===== PREPARAR DATOS PARA MONDAY =====
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;
    
    // Mapeo a columnas Monday (IDs REALES del tablero "Leads")
    $columnValues = [
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'], // Puesto
        'lead_phone' => $scoringData['phone'],
        'lead_status' => ['label' => 'Nuevo Lead'],
        
        // Columnas de negocio (creadas por optimize-infrastructure.php)
        'numeric_mkyn2py0' => $scoreResult['total'], // Lead Score
        'color_mkyn199t' => ['label' => $scoreResult['priority_label']], // Clasificación (Hot/Warm/Cold)
        'color_mkyng649' => ['label' => $detectedRole], // Rol Detectado
        'text_mkyn95hk' => $scoringData['country'] // País
    ];
    
    // ===== ENVIAR A MONDAY =====
    $itemName = $scoringData['name'];
    $response = $monday->createItem($boardId, $itemName, $columnValues);
    
    // ===== RESPUESTA EXITOSA =====
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Lead procesado correctamente',
        'monday_id' => $response['create_item']['id'] ?? null,
        'score' => $scoreResult,
        'detected_role' => $detectedRole
    ]);
    
} catch (Exception $e) {
    // Manejo de errores
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    
    $errorMsg = $e->getMessage();
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMsg
    ]);
}