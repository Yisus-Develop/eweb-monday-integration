<?php
// final-webhook-handler.php
// Webhook handler final con formato correcto para todas las columnas

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

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

    // Crear item con solo las columnas que se pueden establecer directamente
    $basicColumnValues = [
        // Columnas existentes
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'], // Puesto
        'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
        'lead_status' => ['label' => $isDisposable ? 'No calificado' : 'Lead nuevo'],

        // Columnas de negocio con valores básicos
        'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score
        NewColumnIds::CLASSIFICATION => ['label' => $scoreResult['priority_label']],      // Clasificación
        NewColumnIds::ROLE_DETECTED => ['label' => $scoreResult['detected_role']],        // Rol Detectado
        'text_mkyn95hk' => $scoringData['country'],                                     // País (Original ID)

        // Columnas de texto
        'text_mkypn0m' => $missionPartner,                                              // Mission Partner
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada
        'date_mkypeap2' => ['date' => date('Y-m-d')],                                   // Próxima Acción
        'long_text_mkypqppc' => ['text' => $scoreResult['breakdown'] ? json_encode($scoreResult['breakdown']) : ''] // Notas Internas
    ];

    $itemName = $scoringData['name'];

    // ===== REGLA 2: MANEJO DE DUPLICADOS =====
    $existingItems = $monday->getItemsByColumnValue($boardId, $emailColumnId, $scoringData['email']);

    $action = 'created';
    $monday_id = null;

    if (!empty($existingItems)) {
        // Duplicado encontrado, actualizar el primer item
        $itemIdToUpdate = $existingItems[0]['id'];
        
        // Actualizar valores principales
        $updateResponse = $monday->updateItem($boardId, $itemIdToUpdate, $basicColumnValues);
        $action = 'updated';
        $monday_id = $updateResponse['update_item']['id'] ?? null;
        $itemId = $itemIdToUpdate;
        $message = 'Lead duplicado actualizado correctamente';
    } else {
        // No es duplicado, crear nuevo item
        $itemResponse = $monday->createItem($boardId, $itemName, $basicColumnValues);
        $action = 'created';
        $monday_id = $itemResponse['create_item']['id'] ?? null;
        $itemId = $monday_id;
        $message = 'Lead procesado correctamente';
    }

    // Si se creó o actualizó exitosamente, ahora actualizar las columnas dropdown que no pudimos crear directamente
    if ($itemId) {
        // Actualizar columnas dropdown individualmente
        try {
            // Actualizar Tipo de Lead
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
        } catch (Exception $e) {
            error_log("Error actualizando Tipo de Lead: " . $e->getMessage());
        }

        try {
            // Actualizar Canal de Origen
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => [$scoreResult['canal_origen']]]);
        } catch (Exception $e) {
            error_log("Error actualizando Canal de Origen: " . $e->getMessage());
        }

        try {
            // Actualizar Idioma
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['labels' => [$scoreResult['idioma']]]);
        } catch (Exception $e) {
            error_log("Error actualizando Idioma: " . $e->getMessage());
        }
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
?>
