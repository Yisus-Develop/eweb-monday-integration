<?php
// webhook-handler.php
// Production version of the Monday.com integration handler

require_once 'config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';
require_once 'StatusConstants.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Solo POST permitido');
}

// Obtener datos (JSON o Form-Data)
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

// Logging para debugging (opcional en producción)
if (defined('WEBHOOK_DEBUG') && WEBHOOK_DEBUG) {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Datos recibidos: " . print_r($data, true) . "\n", FILE_APPEND);
}

try {
    // ===== MAPEO DINÁMICO DE CAMPOS =====
    $scoringData = [
        'name' => $data['nombre'] ?? $data['contact_name'] ?? $data['your-name'] ?? ($data['ea_firstname'] ?? '') . ' ' . ($data['ea_lastname'] ?? '') ?: 'Sin Nombre',
        'email' => $data['email'] ?? $data['ea_email'] ?? $data['your-email'] ?? '',
        'phone' => $data['telefono'] ?? $data['your-phone'] ?? '',
        'company' => $data['org_name'] ?? $data['company'] ?? $data['entity'] ?? $data['institucion'] ?? $data['ea_institution'] ?? '',
        'role' => $data['ea_role'] ?? $data['tipo_institucion'] ?? $data['sector'] ?? $data['interes'] ?? $data['especialidad'] ?? '',
        'country' => $data['pais_cf7'] ?? $data['pais_otro'] ?? $data['ea_country'] ?? '',
        'city' => $data['ciudad_cf7'] ?? $data['ea_city'] ?? '',
        'perfil' => $data['perfil'] ?? 'general',
        'numero_estudiantes' => (int)($data['numero_estudiantes'] ?? 0),
        'poblacion' => (int)($data['poblacion'] ?? 0),
        'modality' => $data['modality'] ?? '',
        'ea_source' => $data['ea_source'] ?? null,
    ];

    // VALIDACIÓN BÁSICA
    if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido o no proporcionado');
    }

    // Clasificar como Spam si es temporal
    $isDisposable = false;
    $disposableDomains = ['tempmail.com', 'guerrillamail.com', '10minutemail.com', 'mailinator.com'];
    $emailDomain = substr(strrchr($scoringData['email'], "@"), 1);
    if (in_array($emailDomain, $disposableDomains)) $isDisposable = true;

    // CALCULAR LEAD SCORE Y ATRIBUTOS
    $scoreResult = LeadScoring::calculate($scoringData);

    // DETERMINAR ETIQUETA Y GRUPO
    $label = $isDisposable ? 'No calificado' : StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = $isDisposable ? StatusConstants::GROUP_SPAM ?? 'topics' : StatusConstants::getGroupById($label);

    // PREPARAR DATOS PARA MONDAY
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    $columnValues = [
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'numeric_mkyn2py0' => $scoreResult['total'],
        'classification_status' => ['label' => $isDisposable ? 'COLD' : $label],
        'text_mkyn95hk' => $scoringData['country'],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'],
        'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
        'lead_status' => ['label' => $isDisposable ? 'No calificado' : 'Lead nuevo'],
        'role_detected_new' => ['label' => $scoreResult['detected_role']],
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'long_text_mkypqppc' => ['text' => "Lead automático: " . json_encode($scoreResult['breakdown'])]
    ];

    // CREAR ITEM
    $itemResponse = $monday->createItem($boardId, $scoringData['name'], $columnValues, $groupId);
    $itemId = $itemResponse['create_item']['id'] ?? null;

    if ($itemId) {
        // Actualizar Dropdowns (Tipo de Lead, Origen e Idioma)
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => ['Contact Form']]);
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['labels' => [$scoreResult['idioma']]]);
    }

    // RESPUESTA EXITOSA
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'monday_id' => $itemId]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
