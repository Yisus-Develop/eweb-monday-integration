<?php
// email-template-integration.php
// Integración de plantillas de email multilingües con el webhook handler

require_once '../../../config/config.php';
require_once '../MondayAPI.php';
require_once 'multilingual-email-templates.php';

class EmailTemplateIntegrator {
    
    public static function sendEmailToLead($leadData, $mondayAPI) {
        // Obtener la clasificación y el idioma del lead
        $classification = $leadData['classification'] ?? 'COLD'; // Por defecto COLD
        $language = $leadData['language'] ?? 'es'; // Por defecto español
        
        // Obtener la plantilla correspondiente
        $template = MultilingualEmailTemplates::getTemplate($classification, $language);
        
        if (!$template) {
            error_log("No se encontró plantilla para clasificación $classification e idioma $language");
            return false;
        }
        
        // Reemplazar placeholders en la plantilla
        $subject = str_replace('{name}', $leadData['name'] ?? 'Contacto', $template['subject']);
        $body = str_replace('{name}', $leadData['name'] ?? 'Contacto', $template['body']);
        $body = str_replace('{website_url}', $leadData['website_url'] ?? 'https://mars-challenge.org', $body);
        $body = str_replace('{project_url}', $leadData['project_url'] ?? 'https://mars-challenge.org/info', $body);
        
        // En un entorno real, aquí se enviaría el email real
        echo "=== ENVIANDO EMAIL A: {$leadData['email']} ===\n";
        echo "Clasificación: $classification\n";
        echo "Idioma: $language\n";
        echo "Asunto: $subject\n";
        echo "Cuerpo:\n$body\n";
        echo "========================================\n\n";
        
        // Registro de actividad en Monday
        self::logEmailActivity($leadData['monday_item_id'], $mondayAPI, [
            'subject' => $subject,
            'classification' => $classification,
            'language' => $language
        ]);
        
        return true;
    }
    
    private static function logEmailActivity($itemId, $mondayAPI, $emailData) {
        try {
            // En un entorno real, crearíamos un registro en el tablero de Actividades
            // o agregaríamos una nota al lead indicando que se envió el email
            echo "Registro de actividad: Email enviado a item ID $itemId\n";
            echo "  - Clasificación: {$emailData['classification']}\n";
            echo "  - Idioma: {$emailData['language']}\n";
            echo "  - Asunto: {$emailData['subject']}\n";
        } catch (Exception $e) {
            error_log("Error al registrar actividad de email: " . $e->getMessage());
        }
    }
}

// Ejemplo de uso con diferentes tipos de leads
echo "=== INTEGRACIÓN DE PLANTILLAS DE EMAIL MULTILINGÜES ===\n\n";

$mondayAPI = new MondayAPI(MONDAY_API_TOKEN);

// Simular diferentes tipos de leads
$leads = [
    [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'classification' => 'HOT',
        'language' => 'es',
        'monday_item_id' => 123456
    ],
    [
        'name' => 'Maria Silva',
        'email' => 'maria@example.com',
        'classification' => 'WARM',
        'language' => 'pt',
        'monday_item_id' => 123457
    ],
    [
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'classification' => 'COLD',
        'language' => 'en',
        'monday_item_id' => 123458
    ],
    [
        'name' => 'Pierre Dupont',
        'email' => 'pierre@example.com',
        'classification' => 'HOT',
        'language' => 'fr',
        'monday_item_id' => 123459
    ]
];

foreach ($leads as $lead) {
    EmailTemplateIntegrator::sendEmailToLead($lead, $mondayAPI);
}

?>