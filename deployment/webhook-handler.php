<?php
// webhook-handler.php
// Versión Final Re-Armonizada y Blindada (Fix Nombre y Columnas)

// 1. Cargar Configuración
if (file_exists('../../config/config.php')) {
    require_once '../../config/config.php';
} elseif (file_exists('../config.php')) {
    require_once '../config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
} else {
    if (!defined('MONDAY_API_TOKEN')) define('MONDAY_API_TOKEN', 'missing');
}

// 2. Activar Debug si es necesario
if (defined('WEBHOOK_DEBUG') && WEBHOOK_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';
require_once 'StatusConstants.php';

// --- SEGURIDAD: Validación de Token Secreto ---
$received_secret = $_SERVER['HTTP_X_MC_SECRET'] ?? '';
$expected_secret = defined('MONDAY_INTEGRATION_SECRET') ? MONDAY_INTEGRATION_SECRET : '';

if (empty($expected_secret) || $received_secret !== $expected_secret) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Acceso no autorizado. Token inválido.']);
    exit;
}
// ----------------------------------------------

// Logging inteligente
$logFile = __DIR__ . '/webhook_debug.log';
function logMsg($msg, $isError = false) {
    global $logFile;
    // Solo logueamos si es error O si el modo debug está activo
    if (!$isError && (!defined('WEBHOOK_DEBUG') || !WEBHOOK_DEBUG)) return;
    
    // Rotación básica de log (5MB)
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        rename($logFile, $logFile . '.' . date('Ymd-His') . '.old');
    }
    
    $prefix = $isError ? '[ERROR] ' : '[INFO]  ';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " $prefix $msg\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Solo POST permitido');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: $_POST;
    
    logMsg("Recibida petición. Datos: " . substr(print_r($data, true), 0, 500));

    // 1. Mapeo de Identidad y Limpieza de Nombre
    $rawName = $data['nombre'] ?? 
               $data['nombre_empresa'] ?? 
               $data['your-name'] ?? 
               $data['contact_name'] ?? 
               $data['ea_firstname'] ?? 
               $data['first-name'] ?? 
               $data['full-name'] ?? 
               $data['first'] ?? 
               $data['name'] ?? 
               '';

    // Si tenemos campos separados de nombre y apellido
    if (empty($rawName) && isset($data['first']) && isset($data['last'])) {
        $rawName = $data['first'] . ' ' . $data['last'];
    } elseif (empty($rawName) && isset($data['ea_firstname']) && isset($data['ea_lastname'])) {
        $rawName = $data['ea_firstname'] . ' ' . $data['ea_lastname'];
    }

    if (empty($rawName)) $rawName = 'Sin Nombre';
    
    // Limpieza: Solo quitamos el prefijo de Mars Challenge y los corchetes específicos
    $cleanName = trim(str_ireplace(['Mars Challenge', '«', '»'], '', $rawName));
    
    // Si el nombre es "Sin Nombre" o muy corto, intentamos usar el email
    if ($cleanName === 'Sin Nombre' || strlen($cleanName) < 2) {
        $cleanName = !empty($data['email']) ? explode('@', $data['email'])[0] : 'Lead #' . date('His');
    }

    logMsg("Identidad detectada: $cleanName (" . ($data['email'] ?? 'sin email') . ")");

    $scoringData = [
        'name'   => $cleanName,
        'email'  => $data['email'] ?? $data['your-email'] ?? $data['ea_email'] ?? '',
        'phone'  => strval($data['telefono'] ?? $data['your-phone'] ?? $data['tel-641'] ?? $data['phone'] ?? ''),
        'country'=> $data['pais_cf7'] ?? $data['pais_otro'] ?? $data['ea_country'] ?? $data['country'] ?? '',
        'city'   => $data['ciudad_cf7'] ?? $data['ea_city'] ?? $data['city'] ?? '',
        'perfil' => $data['perfil'] ?? $data['profile'] ?? 'general',
        'profile'=> $data['profile'] ?? $data['perfil'] ?? 'general',
        'tipo_institucion' => $data['tipo_institucion'] ?? '',
        'numero_estudiantes' => $data['numero_estudiantes'] ?? 0,
        'poblacion' => $data['poblacion'] ?? $data['population'] ?? 0,
        'population' => $data['population'] ?? $data['poblacion'] ?? 0,
    ];

    if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
        logMsg("Email omitido o inválido: " . ($scoringData['email'] ?: 'vacío'), true);
        throw new Exception("Email inválido (" . $scoringData['email'] . ")");
    }

    $scoreResult = LeadScoring::calculate($scoringData);
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    // Mapa de Entidad (Status Column)
    $entityLabel = 'Otro';
    $p = strtolower($scoringData['perfil']);
    if (strpos($p, 'institucion') !== false || strpos($p, 'pioneer') !== false) $entityLabel = 'Universidad';
    elseif (strpos($p, 'zer') !== false || strpos($p, 'mentor') !== false) $entityLabel = 'Escuela';
    elseif (strpos($p, 'empresa') !== false) $entityLabel = 'Empresa';
    elseif (strpos($p, 'ciudad') !== false || strpos($p, 'pais') !== false) $entityLabel = 'Ciudad';

    $puestoFinal = strval($scoreResult['detected_role'] ?? 'Lead');

    // Preparar Columnas con formatos CORRECTOS
    $columnUpdates = [
        NewColumnIds::EMAIL => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        NewColumnIds::PHONE => ['phone' => $scoringData['phone'] ?: '0000', 'countryShortName' => ($scoringData['country'] ?: 'ES')],
        NewColumnIds::PUESTO => $puestoFinal,
        NewColumnIds::STATUS => ['label' => StatusConstants::STATUS_LEAD],
        NewColumnIds::LEAD_SCORE => (int)$scoreResult['total'],
        NewColumnIds::CLASSIFICATION => ['label' => strval($scoreResult['priority_label'])],
        NewColumnIds::ROLE_DETECTED => ['label' => strval(StatusConstants::getRoleLabel($scoreResult['detected_role']))],
        NewColumnIds::COUNTRY => strval($scoringData['country'] ?: 'CO'),
        NewColumnIds::CITY => strval($scoringData['city'] ?: 'N/A'),
        NewColumnIds::ENTRY_DATE => ['date' => date('Y-m-d')],
        NewColumnIds::ENTITY_TYPE => ['label' => $entityLabel],
        NewColumnIds::IA_ANALYSIS => ['text' => substr(json_encode($scoreResult['breakdown']), 0, 1999)],
        NewColumnIds::TYPE_OF_LEAD => ['labels' => [strval($scoreResult['tipo_lead'])]],
        NewColumnIds::SOURCE_CHANNEL => ['labels' => [strval($scoreResult['canal_origen'])]],
        NewColumnIds::LANGUAGE => ['labels' => [strval($scoreResult['idioma'])]],
        NewColumnIds::FORM_SUMMARY => ['text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]
    ];

    // Manejo de Registro (Deduplicación con Historial en Sub-elementos)
    $existing = $monday->getItemsByColumnValue($boardId, NewColumnIds::EMAIL, $scoringData['email']);
    $action = 'created';
    
    if (!empty($existing)) {
        $itemId = $existing[0]['id'];
        logMsg("Lead existente detectado ($itemId). Creando nota de historial (Update)...");
        
        $formTitle = $data['your-subject'] ?? $data['asunto'] ?? 'Nuevo Formulario Enviado';
        $updateBody = "<b>📋 Historial de Formulario: $formTitle</b><br><br>";
        $updateBody .= "Se ha recibido una nueva entrada para este contacto:<br><ul>";
        foreach ($data as $key => $val) {
            if (is_array($val)) $val = implode(', ', $val);
            $updateBody .= "<li><b>$key:</b> " . htmlspecialchars($val) . "</li>";
        }
        $updateBody .= "</ul><br><i>Enviado el " . date('d/m/Y H:i:s') . " desde " . $_SERVER['HTTP_REFERER'] . "</i>";
        
        try {
            $monday->createUpdate($itemId, $updateBody);
        } catch (Exception $e) {
            logMsg("Error creando actualización (ignorado): " . $e->getMessage(), true);
        }
        
        logMsg("Actualizando lead principal: $itemId");
        $action = 'updated';
    } else {
        logMsg("Creando nuevo lead: " . $scoringData['name']);
        $resp = $monday->createItem($boardId, $scoringData['name'], []);
        $itemId = $resp['create_item']['id'] ?? null;
    }

    if ($itemId) {
        logMsg("Enviando actualización masiva para $itemId...");
        try {
            // Un solo hit a la API (RÁPIDO) elimina el error 500 por timeout
            $monday->changeMultipleColumnValues($boardId, $itemId, json_encode($columnUpdates));
            logMsg("OK: Actualización masiva completada.");
        } catch (Exception $e) {
            logMsg("Fallo en masiva: " . $e->getMessage() . ". Reintentando individual...", true);
            // Fallback individual si falla la masiva (seguridad extra)
            foreach ($columnUpdates as $colId => $val) {
                try { $monday->changeColumnValue($boardId, $itemId, $colId, $val); } catch (Exception $e2) {}
            }
        }
    }

    echo json_encode(['status' => 'success', 'action' => $action, 'monday_id' => $itemId]);

} catch (Throwable $e) {
    logMsg("FALLO: " . $e->getMessage(), true);
    header('HTTP/1.1 500');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
