<?php
// webhook-handler.php
// Versión FINAL V4.1 - Robusta (Simple String) y Terminología Directa

require_once 'config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';
require_once 'StatusConstants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Solo POST permitido');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

try {
    // 1. Mapeo y Limpieza
    $scoringData = [
        'name' => $data['nombre'] ?? $data['your-name'] ?? 'Sin Nombre',
        'email' => $data['email'] ?? $data['your-email'] ?? '',
        'phone' => $data['telefono'] ?? $data['your-phone'] ?? $data['phone'] ?? '',
        'country' => $data['pais_cf7'] ?? $data['country'] ?? '',
        'perfil' => $data['perfil'] ?? 'general',
    ];

    if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    $scoreResult = LeadScoring::calculate($scoringData);
    $rawBackup = json_encode($data, JSON_UNESCAPED_UNICODE);

    // 2. Clasificación
    $label = StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = StatusConstants::getGroupById($label);
    
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    // 3. Crear Item Básico (Solo lo que es garantizado que funciona en el primer paso)
    $columnValues = [
        NewColumnIds::LEAD_EMAIL => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        NewColumnIds::NUMERIC_SCORE => (string)$scoreResult['total'],
        NewColumnIds::CLASSIFICATION_STATUS => ['label' => $label],
        NewColumnIds::COUNTRY_TEXT => $scoringData['country'],
        NewColumnIds::DATE_CREATED => ['date' => date('Y-m-d')],
        NewColumnIds::RAW_DATA_JSON => $rawBackup
    ];

    $itemResponse = $monday->createItem($boardId, $scoringData['name'], $columnValues, $groupId);
    $itemId = $itemResponse['create_item']['id'] ?? null;

    if ($itemId) {
        // 4. Actualizar Columnas Delicadas usando changeSimpleColumnValue (Mucho más robusto)
        
        // TELÉFONO: Como string simple (+52...) Monday lo detecta mejor
        if (!empty($scoreResult['clean_phone'])) {
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LEAD_PHONE, $scoreResult['clean_phone']);
        }

        // TERMINOLOGÍA DIRECTA: Zer, Pioneer, etc.
        $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, $scoreResult['tipo_lead'], true);
        
        // OTROS DROPDOWNS
        $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, 'Website', true);
        $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, $scoreResult['idioma'], true);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'id' => $itemId]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
