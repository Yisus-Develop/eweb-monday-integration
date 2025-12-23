<?php
// webhook-handler.php
// Manejador Universal para TODOS los formularios Contact Form 7 → Monday CRM

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'StatusConstants.php';

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
    $scoringData = [
        'name' => $data['nombre'] ?? $data['contact_name'] ?? $data['your-name'] ?? ($data['ea_firstname'] ?? '') . ' ' . ($data['ea_lastname'] ?? '') ?: 'Sin Nombre',
        'email' => $data['email'] ?? $data['ea_email'] ?? $data['your-email'] ?? '',
        'phone' => $data['telefono'] ?? $data['your-phone'] ?? '',
        'company' => $data['org_name'] ?? $data['company'] ?? $data['entity'] ?? $data['institucion'] ?? $data['ea_institution'] ?? '',
        'role' => $data['tipo_institucion'] ?? $data['sector'] ?? $data['interes'] ?? $data['especialidad'] ?? $data['ea_role'] ?? '',
        'country' => $data['pais_cf7'] ?? $data['pais_otro'] ?? $data['ea_country'] ?? '',
        'city' => $data['ciudad_cf7'] ?? $data['ea_city'] ?? '',
        'perfil' => $data['perfil'] ?? 'general',
        'tipo_institucion' => $data['tipo_institucion'] ?? '',
        'numero_estudiantes' => (int)($data['numero_estudiantes'] ?? 0),
        'poblacion' => (int)($data['poblacion'] ?? 0),
        'modality' => $data['modality'] ?? '',
        'ea_source' => $data['ea_source'] ?? null,
        'ea_lang' => $data['ea_lang'] ?? null,
    ];

    // ===== REGLA 1: VALIDACIÓN DE EMAIL =====
    if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido o no proporcionado');
    }

    $isDisposable = false;
    $disposableDomains = ['tempmail.com', 'guerrillamail.com', '10minutemail.com', 'mailinator.com'];
    $emailDomain = substr(strrchr($scoringData['email'], "@"), 1);
    if (in_array($emailDomain, $disposableDomains)) {
        $isDisposable = true;
    }

    // ===== CALCULAR LEAD SCORE Y ATRIBUTOS =====
    $scoreResult = LeadScoring::calculate($scoringData);
    $missionPartner = ($scoringData['perfil'] === 'pioneer') ? $scoringData['name'] : '';

    // ===== PREPARAR DATOS PARA MONDAY =====
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;
    $emailColumnId = 'lead_email'; // ID de la columna de email

    $columnValues = [
        // Columnas existentes
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'],
        'lead_phone' => $scoringData['phone'],
        'lead_status' => $isDisposable ? ['label' => 'No calificado'] : ['label' => 'Lead nuevo'],

        // Columnas de negocio (ya creadas y sus IDs actuales)
        'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score (Original ID)
        'color_mkypv3rg' => ['label' => StatusConstants::getScoreClassification($scoreResult['total'])], // Clasificación (HOT/WARM/COLD) (NEW ID)
        'color_mkyng649' => ['label' => StatusConstants::getRoleLabel($scoreResult['detected_role'])],  // Rol Detectado (Original ID)
        'text_mkyn95hk' => $scoringData['country'],                                     // País (Original ID)

        // Nuevas columnas críticas (IDs de Fase 2.1)
        'dropdown_mkyp8q98' => ['label' => $scoreResult['tipo_lead']],                  // Tipo de Lead (NEW ID)
        'dropdown_mkypf16c' => ['label' => $scoreResult['canal_origen']],               // Canal de Origen (NEW ID)
        'text_mkypn0m' => $missionPartner,                                              // Mission Partner (NEW ID)
        'dropdown_mkyps472' => ['label' => $scoreResult['idioma']],                     // Idioma (NEW ID)
        
        // Nuevas columnas secundarias (IDs de Fase 2.2)
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada (Original ID)
        'date_mkypeap2' => ['date' => date('Y-m-d')],                                   // Próxima Acción (Original ID)
        'long_text_mkypqppc' => $scoreResult['breakdown'] ? json_encode($scoreResult['breakdown']) : '' // Notas Internas (Original ID, using breakdown)
    ];

    // ===== REGLA 2: MANEJO DE DUPLICADOS =====
    $existingItems = $monday->getItemsByColumnValue($boardId, $emailColumnId, $scoringData['email']);
    
    $itemName = $scoringData['name'];
    $action = 'created';
    $monday_id = null;

    if (!empty($existingItems)) {
        // Duplicado encontrado, actualizar el primer item
        $itemIdToUpdate = $existingItems[0]['id'];
        $response = $monday->updateItem($boardId, $itemIdToUpdate, $columnValues);
        $action = 'updated';
        $monday_id = $response['update_item']['id'] ?? null;
        $message = 'Lead duplicado actualizado correctamente';
    } else {
        // No es duplicado, crear nuevo item
        $response = $monday->createItem($boardId, $itemName, $columnValues);
        $action = 'created';
        $monday_id = $response['create_item']['id'] ?? null;
        $message = 'Lead procesado correctamente';
    }

    // ===== RESPUESTA EXITOSA =====
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'action' => $action,
        'message' => $message,
        'monday_id' => $monday_id,
        'score_details' => $scoreResult
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
