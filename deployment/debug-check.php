<?php
/**
 * debug-check.php
 * Script de diagnóstico para corregir problemas de logs y conexión en producción.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Integración Monday.com</h1>";

// 1. Verificar Carpeta Escritura
echo "<h3>1. Permisos de Archivo</h3>";
$dir = __DIR__;
if (is_writable($dir)) {
    echo "✅ La carpeta es escribible.<br>";
} else {
    echo "❌ <b>ERROR:</b> La carpeta no tiene permisos de escritura. Los logs no se podrán crear. Intenta CHMOD 755 o 777 en la carpeta.<br>";
}

// 2. Verificar Config
echo "<h3>2. Configuración</h3>";
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ Archivo config.php encontrado.<br>";
    
    if (defined('MONDAY_API_TOKEN') && MONDAY_API_TOKEN !== 'missing') {
        echo "✅ Token de API configurado.<br>";
    } else {
        echo "❌ <b>ERROR:</b> MONDAY_API_TOKEN no está definido o es inválido en config.php.<br>";
    }
    
    if (defined('WEBHOOK_DEBUG')) {
        echo "ℹ️ WEBHOOK_DEBUG está: " . (WEBHOOK_DEBUG ? "<b>ACTIVADO</b>" : "<b>DESACTIVADO</b>") . "<br>";
    } else {
        echo "⚠️ WEBHOOK_DEBUG no está definido. Por defecto solo se loguearán errores.<br>";
    }
} else {
    echo "❌ <b>ERROR:</b> No se encontró config.php en esta carpeta.<br>";
}

// 3. Prueba de Escritura Log
echo "<h3>3. Prueba de Log</h3>";
$testLog = 'test_write.log';
if (@file_put_contents($testLog, date('Y-m-d H:i:s') . " - Prueba de diagnóstico\n", FILE_APPEND)) {
    echo "✅ Prueba de escritura exitosa. El archivo '$testLog' se ha creado.<br>";
    @unlink($testLog);
} else {
    echo "❌ <b>ERROR:</b> No se pudo escribir el archivo de prueba. Revisa permisos del servidor.<br>";
}

// 4. Conexión con Monday
echo "<h3>4. Conexión Monday.com</h3>";
if (defined('MONDAY_API_TOKEN')) {
    require_once 'MondayAPI.php';
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    try {
        $query = '{ boards(ids: [' . (defined('MONDAY_BOARD_ID') ? MONDAY_BOARD_ID : '0') . ']) { name } }';
        $mdata = $monday->query($query);
        if ($mdata) {
            echo "✅ Conexión exitosa con Monday.com. Tablero: " . ($mdata['boards'][0]['name'] ?? 'No encontrado') . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ <b>ERROR API:</b> " . $e->getMessage() . "<br>";
    }
}

echo "<br><hr>";
echo "Si los leads no llegan y todo lo anterior sale en verde, revisa el plugin de WordPress para asegurar que está apuntando a la URL correcta.";
