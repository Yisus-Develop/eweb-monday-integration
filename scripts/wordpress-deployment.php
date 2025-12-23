<?php
// wordpress-deployment.php
// Script de despliegue para WordPress

echo "========================================\n";
echo "  SCRIPT DE DESPLIEGUE WORDPRESS        \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

// Directorio de WordPress (ajustar según tu instalación)
$wp_directory = 'C:\xampp\htdocs\wordpress'; // Cambiar a la ruta de tu WordPress

if (!file_exists($wp_directory)) {
    echo "❌ Directorio de WordPress no encontrado: $wp_directory\n";
    echo "Por favor, ajusta la ruta en este script.\n";
    exit(1);
}

echo "✅ Directorio de WordPress encontrado: $wp_directory\n\n";

// Rutas de origen (nuestros archivos de integración)
$source_dir = 'C:\Users\jesus\AI-Vault\projects\monday-automation\src\wordpress';

// Ruta de destino en WordPress
$target_dir = $wp_directory . '\wp-content\plugins\mars-challenge-integration';

echo "CREANDO ESTRUCTURA DE DIRECTORIOS...\n";

// Crear directorio de destino
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
    echo "✅ Directorio creado: $target_dir\n";
} else {
    echo "⚠️  Directorio ya existe: $target_dir\n";
}

// Crear directorio de logs
$logs_dir = $target_dir . '\logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
    echo "✅ Directorio de logs creado: $logs_dir\n";
} else {
    echo "⚠️  Directorio de logs ya existe: $logs_dir\n";
}

echo "\nCOPIANDO ARCHIVOS DE INTEGRACIÓN...\n";

// Archivos a copiar
$files_to_copy = [
    'MondayAPI.php' => 'monday-api.php',
    'LeadScoring.php' => 'lead-scoring.php',
    'NewColumnIds.php' => 'new-column-ids.php',
    'scripts\webhook-confirmation.php' => 'webhook-confirmation.php',
    'final-webhook-handler.php' => 'webhook-handler.php'
];

foreach ($files_to_copy as $source_file => $target_file) {
    $source_path = $source_dir . '\\' . $source_file;
    $target_path = $target_dir . '\\' . $target_file;
    
    if (file_exists($source_path)) {
        if (copy($source_path, $target_path)) {
            echo "✅ Copiado: $target_file\n";
        } else {
            echo "❌ Error copiando: $target_file\n";
        }
    } else {
        echo "❌ Archivo no encontrado: $source_path\n";
    }
}

echo "\nCREANDO ARCHIVO DE CONFIGURACIÓN...\n";

// Crear archivo de configuración
$config_content = '<?php
// wp-content/plugins/mars-challenge-integration/config.php
// Configuración de Mars Challenge CRM Integration

// Configuración de Monday.com
// NOTA: Reemplaza "TU_TOKEN_DE_API_AQUI" con tu token real de Monday.com
define("MONDAY_API_TOKEN", "TU_TOKEN_DE_API_AQUI");
define("MONDAY_BOARD_ID", 18392144864); // MC – Lead Master Intake

// Opciones de integración
define("ENABLE_LOGGING", true);
define("LOG_FILE_PATH", __DIR__ . "/logs/webhook.log");
define("ERROR_FILE_PATH", __DIR__ . "/logs/webhook_errors.log");

// Directorio de logs
$logDir = __DIR__ . "/logs";
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Cargar dependencias
require_once "monday-api.php";
require_once "lead-scoring.php";
require_once "new-column-ids.php";
require_once "webhook-confirmation.php";

echo "Configuración cargada exitosamente\n";
?>';

$config_path = $target_dir . '\config.php';
if (file_put_contents($config_path, $config_content)) {
    echo "✅ Archivo de configuración creado: config.php\n";
    echo "⚠️  IMPORTANTE: Edita config.php para agregar tu token de Monday.com real\n";
} else {
    echo "❌ Error creando config.php\n";
}

echo "\nCREANDO ARCHIVO DE INTEGRACIÓN CF7...\n";

// Crear archivo de integración CF7
$integration_content = '<?php
// wp-content/plugins/mars-challenge-integration/integration.php
// Integración con Contact Form 7

// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}

class MarsChallengeIntegration {
    
    public function __construct() {
        add_action("wpcf7_before_send_mail", array($this, "handle_form_submission"), 10, 2);
    }
    
    public function handle_form_submission($contact_form, $abort) {
        // Obtener datos del formulario
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Preparar datos para el webhook
        $webhook_data = array_map(function($value) {
            return is_array($value) ? implode(", ", $value) : $value;
        }, $posted_data);
        
        // Enviar datos al webhook
        $webhook_url = plugin_dir_url(__FILE__) . "webhook-handler.php";
        
        $response = wp_remote_post($webhook_url, array(
            "body" => $webhook_data,
            "timeout" => 30,
            "sslverify" => false
        ));
        
        // Registrar resultado si hay error
        if (is_wp_error($response)) {
            error_log("Error CF7-Monday integration: " . $response->get_error_message());
        }
    }
}

// Inicializar la integración
new MarsChallengeIntegration();
?>';

$integration_path = $target_dir . '\integration.php';
if (file_put_contents($integration_path, $integration_content)) {
    echo "✅ Archivo de integración CF7 creado: integration.php\n";
} else {
    echo "❌ Error creando integration.php\n";
}

echo "\nCREANDO ARCHIVO DE PLUGIN...\n";

// Crear archivo de plugin
$plugin_content = '<?php
/**
 * Plugin Name: Mars Challenge CRM Integration
 * Description: Integración completa de CF7 con Monday.com para Mars Challenge
 * Version: 1.0
 * Author: Mars Challenge Team
 */

// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}

// Cargar la integración
require_once plugin_dir_path(__FILE__) . "integration.php";

// Mensaje de activación
register_activation_hook(__FILE__, function() {
    if (!class_exists("WPCF7")) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die("Contact Form 7 es requerido para este plugin.");
    }
});

// Añadir página de configuración
add_action("admin_menu", function() {
    add_options_page(
        "Mars Challenge CRM",
        "Mars Challenge CRM",
        "manage_options",
        "mars-challenge-crm",
        function() {
            echo "<div class=\'wrap\'>";
            echo "<h1>Mars Challenge CRM Integration</h1>";
            echo "<p>Configuración: " . plugin_dir_url(__FILE__) . "config.php</p>";
            echo "<p>Webhook URL: " . plugin_dir_url(__FILE__) . "webhook-handler.php</p>";
            echo "</div>";
        }
    );
});
?>';

$plugin_path = $wp_directory . '\wp-content\plugins\mars-challenge-crm.php';
if (file_put_contents($plugin_path, $plugin_content)) {
    echo "✅ Archivo de plugin creado: mars-challenge-crm.php\n";
} else {
    echo "❌ Error creando mars-challenge-crm.php\n";
}

echo "\n========================================\n";
echo "  DESPLIEGUE COMPLETADO                \n";
echo "========================================\n";
echo "Archivos instalados en: $target_dir\n";
echo "Plugin disponible en: $wp_directory\\wp-content\\plugins\\mars-challenge-crm.php\n\n";

echo "PRÓXIMOS PASOS:\n";
echo "1. Edita config.php para agregar tu token de Monday.com real\n";
echo "2. Activa el plugin 'Mars Challenge CRM Integration' en WordPress admin\n";
echo "3. Configura tus formularios de CF7 para usar la integración\n";
echo "4. Prueba el formulario para verificar funcionamiento\n\n";

echo "URL DEL WEBHOOK: " . str_replace('\\', '/', plugin_dir_url(__FILE__) . "webhook-handler.php") . "\n";
echo "========================================\n";

?>