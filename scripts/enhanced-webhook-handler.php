<?php
// enhanced-webhook-handler.php
// Webhook handler con sistema de confirmación y logging

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';
require_once 'scripts/webhook-confirmation.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Solo POST permitido');
}

// Obtener datos (JSON o Form-Data)
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

// Logging de entrada
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Entrada: " . print_r($data, true) . "\n", FILE_APPEND);

try {
    // Usar el sistema de confirmación
    $confirmation = new WebhookConfirmation();
    $result = $confirmation->processForm($data);
    
    // Verificar el resultado
    if ($result['status'] === 'success') {
        // Respuesta exitosa
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'process_id' => $result['process_id'],
            'lead_id' => $result['lead_id'],
            'score' => $result['score'],
            'classification' => $result['classification']
        ]);
    } else {
        // Error en el procesamiento
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
            'process_id' => $result['process_id']
        ]);
    }
    
} catch (Exception $e) {
    // Error general
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    
    $errorMsg = $e->getMessage();
    error_log("Webhook Error: " . $errorMsg);
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor: ' . $errorMsg
    ]);
}
?>