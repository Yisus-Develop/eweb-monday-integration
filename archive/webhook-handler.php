<?php
// webhook-handler.php
// Versión Armonizada y Corregida (Fix Error 500)

// Intentar cargar config desde múltiples ubicaciones posibles
if (file_exists('../../config/config.php')) {
    require_once '../../config/config.php';
} elseif (file_exists('../config.php')) {
    require_once '../config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
} else {
    // Fallback para desarrollo si no hay config
    if (!defined('MONDAY_API_TOKEN')) define('MONDAY_API_TOKEN', 'missing');
}

require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';
require_once 'StatusConstants.php';

// Logging inteligente (Auto-rotación a 5MB)
$logFile = __DIR__ . '/webhook_debug.log';
function logMsg($msg, $isError = false) {
    global $logFile;
    
    // Si no es un error y el DEBUG está apagado, no logueamos nada
    if (!$isError && (!defined('WEBHOOK_DEBUG') || !WEBHOOK_DEBUG)) {
        return;
    }

    // Rotación: Si el archivo mide más de 5MB, lo renombramos a .old (sobrescribiendo el anterior)
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        rename($logFile, $logFile . '.old');
    }

    $prefix = $isError ? '[ERROR] ' : '[INFO] ';
    $formattedMsg = date('Y-m-d H:i:s') . " $prefix $msg\n";
    
    @file_put_contents($logFile, $formattedMsg, FILE_APPEND);
    
    if ($isError) {
        error_log("Monday Integration: $msg");
    }
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Solo POST permitido');
}

try {
    // Obtener datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: $_POST;
    
    logMsg("Recibida petición. Datos: " . substr(print_r($data, true), 0, 500));

    // ===== MAPEO DE CAMPOS CF7 =====
    $scoringData = [
        'name' => $data['nombre'] ?? $data['contact_name'] ?? $data['your-name'] ?? ($data['ea_firstname'] ?? '') . ' ' . ($data['ea_lastname'] ?? '') ?: 'Sin Nombre',
        'email' => $data['email'] ?? $data['ea_email'] ?? $data['your-email'] ?? '',
        'phone' => $data['telefono'] ?? $data['your-phone'] ?? $data['tel-641'] ?? '', // Variantes comunes
        'company' => $data['org_name'] ?? $data['company'] ?? $data['entity'] ?? $data['institucion'] ?? $data['ea_institution'] ?? '',
        'role' => $data['tipo_institucion'] ?? $data['sector'] ?? $data['interes'] ?? $data['especialidad'] ?? $data['ea_role'] ?? '',
        'country' => $data['pais_cf7'] ?? $data['pais_otro'] ?? $data['ea_country'] ?? '',
        'city' => $data['ciudad_cf7'] ?? $data['ea_city'] ?? '',
        'perfil' => $data['perfil'] ?? 'general',
        'tipo_institucion' => $data['tipo_institucion'] ?? '',
        'numero_estudiantes' => (int)($data['numero_estudiantes'] ?? 0),
        'poblacion' => (int)($data['poblacion'] ?? 0),
        'modality' => $data['modality'] ?? '',
    ];

    if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido (" . $scoringData['email'] . ")");
    }

    // ===== CALCULAR LEAD SCORE =====
    $scoreResult = LeadScoring::calculate($scoringData);

    // ===== PREPARAR PARA MONDAY =====
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    $columnValues = [
        NewColumnIds::EMAIL => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        NewColumnIds::PHONE => ['phone' => $scoringData['phone'], 'countryShortName' => 'ES'],
        NewColumnIds::PUESTO => $scoringData['role'],
        NewColumnIds::STATUS => ['label' => StatusConstants::STATUS_LEAD],
        
        NewColumnIds::LEAD_SCORE => (int)$scoreResult['total'],
        NewColumnIds::CLASSIFICATION => ['label' => $scoreResult['priority_label']],
        NewColumnIds::ROLE_DETECTED => ['label' => StatusConstants::getRoleLabel($scoreResult['detected_role'])],
        NewColumnIds::COUNTRY => $scoringData['country'],
        NewColumnIds::CITY => $scoringData['city'],
        NewColumnIds::ENTRY_DATE => ['date' => date('Y-m-d')],
        NewColumnIds::NEXT_ACTION => ['date' => date('Y-m-d')],
        
        NewColumnIds::ENTITY_TYPE => ['label' => $scoringData['tipo_institucion'] ?: 'En curso'],
        NewColumnIds::IA_ANALYSIS => ['text' => json_encode($scoreResult['breakdown'])]
    ];

    // ===== MANEJO DE DUPLICADOS =====
    $existingItems = $monday->getItemsByColumnValue($boardId, NewColumnIds::EMAIL, $scoringData['email']);
    
    if (!empty($existingItems)) {
        $itemId = $existingItems[0]['id'];
        logMsg("Actualizando duplicado: $itemId");
        $monday->updateItem($boardId, $itemId, $columnValues);
        $action = 'updated';
    } else {
        logMsg("Creando nuevo item: " . $scoringData['name']);
        $resp = $monday->createItem($boardId, $scoringData['name'], $columnValues);
        $itemId = $resp['create_item']['id'] ?? null;
        $action = 'created';
    }

    // Actualizar Dropdowns (Individualmente para mayor robustez)
    if ($itemId) {
        try {
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['label' => $scoreResult['tipo_lead']]);
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['label' => $scoreResult['canal_origen']]);
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['label' => $scoreResult['idioma']]);
        } catch (Exception $e) {
            logMsg("Error en dropdowns: " . $e->getMessage(), true);
        }
    }

    $response = ['status' => 'success', 'action' => $action, 'monday_id' => $itemId];
    logMsg("Respuesta: " . json_encode($response));
    
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    logMsg("ERROR CRÍTICO: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString(), true);
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
