<?php
// view-confirmation-logs.php
// Script para ver los logs de confirmación

require_once '../../../config/config.php';

$logFile = __DIR__ . '/logs/webhook_confirmation.log';
$errorLogFile = __DIR__ . '/logs/webhook_errors.log';

echo "========================================\n";
echo "  SISTEMA DE CONFIRMACIÓN DE FORMULARIOS \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

// Verificar que existen los archivos
if (!file_exists($logFile)) {
    echo "❌ Archivo de logs no encontrado: $logFile\n";
    echo "El sistema de confirmación aún no ha procesado formularios.\n\n";
} else {
    echo "✅ Archivo de logs encontrado: $logFile\n";
    
    // Mostrar últimos 10 logs
    $logContent = file_get_contents($logFile);
    $logLines = array_filter(explode("\n", $logContent));
    
    if (empty($logLines)) {
        echo "No hay logs registrados aún.\n\n";
    } else {
        $recentLogs = array_slice($logLines, -10); // Últimos 10 logs
        
        echo "ÚLTIMOS 10 PROCESOS DE FORMULARIOS:\n\n";
        
        foreach ($recentLogs as $line) {
            if (empty(trim($line))) continue;
            
            $logEntry = json_decode($line, true);
            if (!$logEntry) continue;
            
            echo "ID Proceso: {$logEntry['process_id']}\n";
            echo "Fecha: {$logEntry['timestamp']}\n";
            echo "Estado: {$logEntry['status']}\n";
            
            // Mostrar pasos
            foreach ($logEntry['steps'] as $step) {
                $status = $step['status'] === 'success' ? '✅' : '❌';
                echo "  {$status} {$step['step']}: {$step['status']}\n";
            }
            
            if ($logEntry['status'] === 'completed' && isset($logEntry['result'])) {
                $result = $logEntry['result'];
                echo "  🎯 Lead creado/actualizado: {$result['item_name']} (ID: {$result['item_id']})\n";
            }
            
            echo "\n" . str_repeat("-", 50) . "\n\n";
        }
    }
}

if (!file_exists($errorLogFile)) {
    echo "✅ No hay errores registrados: $errorLogFile\n\n";
} else {
    echo "⚠️  Archivo de errores encontrado: $errorLogFile\n";
    
    $errorContent = file_get_contents($errorLogFile);
    $errorLines = array_filter(explode("\n", $errorContent));
    
    if (empty($errorLines)) {
        echo "No hay errores registrados.\n\n";
    } else {
        $recentErrors = array_slice($errorLines, -5); // Últimos 5 errores
        
        echo "ÚLTIMOS 5 ERRORES:\n\n";
        
        foreach ($recentErrors as $line) {
            if (empty(trim($line))) continue;
            
            $errorEntry = json_decode($line, true);
            if (!$errorEntry) continue;
            
            echo "Fecha: {$errorEntry['timestamp']}\n";
            echo "Error: {$errorEntry['error']}\n";
            
            if (isset($errorEntry['form_data'])) {
                echo "Formulario: " . json_encode($errorEntry['form_data']) . "\n";
            }
            
            echo "\n" . str_repeat("-", 50) . "\n\n";
        }
    }
}

echo "========================================\n";
echo "  INFORMACIÓN DEL SISTEMA DE CONFIRMACIÓN\n";
echo "========================================\n";
echo "1. Cada formulario CF7 se registra con un ID único\n";
echo "2. Todos los pasos se registran (validación, scoring, creación)\n";
echo "3. Se puede rastrear cada formulario enviado\n";
echo "4. Los errores se registran por separado para diagnóstico\n";
echo "========================================\n\n";

echo "PARA MONITOREAR EL SISTEMA:\n";
echo "- Verificar el archivo: $logFile\n";
echo "- Verificar errores en: $errorLogFile\n";
echo "- Cada proceso tiene un process_id único para seguimiento\n\n";

?>