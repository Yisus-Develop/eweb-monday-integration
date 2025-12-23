<?php
// webhook-confirmation.php
// Sistema de intermediario para confirmar éxito en el flujo de formularios

require_once '../../../config/config.php';
require_once '../MondayAPI.php';
require_once '../LeadScoring.php';
require_once '../NewColumnIds.php';

class WebhookConfirmation {
    
    private $monday;
    private $logFile;
    private $errorLogFile;
    
    public function __construct() {
        $this->monday = new MondayAPI(MONDAY_API_TOKEN);
        $this->logFile = __DIR__ . '/logs/webhook_confirmation.log';
        $this->errorLogFile = __DIR__ . '/logs/webhook_errors.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Procesar y confirmar un formulario entrante
     */
    public function processForm($formData) {
        $processId = uniqid('proc_', true);
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = [
            'process_id' => $processId,
            'timestamp' => $timestamp,
            'form_data' => $formData,
            'steps' => []
        ];
        
        try {
            // PASO 1: Validar datos de entrada
            $validation = $this->validateInput($formData);
            $logEntry['steps'][] = [
                'step' => 'validation',
                'status' => $validation['valid'] ? 'success' : 'failed',
                'details' => $validation['details']
            ];
            
            if (!$validation['valid']) {
                throw new Exception("Validación fallida: " . $validation['details']);
            }
            
            // PASO 2: Procesar scoring
            $scoring = $this->processScoring($formData);
            $logEntry['steps'][] = [
                'step' => 'scoring',
                'status' => 'success',
                'details' => [
                    'score' => $scoring['total'],
                    'classification' => $scoring['priority_label'],
                    'role_detected' => $scoring['detected_role']
                ]
            ];
            
            // PASO 3: Preparar datos para Monday
            $mondayData = $this->prepareMondayData($formData, $scoring);
            $logEntry['steps'][] = [
                'step' => 'monday_data_preparation',
                'status' => 'success',
                'details' => array_keys($mondayData)
            ];
            
            // PASO 4: Crear/update lead en Monday
            $leadResult = $this->createOrUpdateLead($mondayData, $formData);
            $logEntry['steps'][] = [
                'step' => 'monday_creation',
                'status' => 'success',
                'details' => [
                    'action' => $leadResult['action'],
                    'item_id' => $leadResult['item_id'],
                    'item_name' => $leadResult['item_name']
                ]
            ];
            
            // PASO 5: Confirmación final exitosa
            $logEntry['status'] = 'completed';
            $logEntry['result'] = $leadResult;
            
            $this->logActivity($logEntry);
            
            return [
                'status' => 'success',
                'process_id' => $processId,
                'message' => 'Formulario procesado exitosamente',
                'lead_id' => $leadResult['item_id'],
                'score' => $scoring['total'],
                'classification' => $scoring['priority_label']
            ];
            
        } catch (Exception $e) {
            // Log de error
            $logEntry['status'] = 'failed';
            $logEntry['error'] = $e->getMessage();
            
            $this->logActivity($logEntry);
            $this->logError($e->getMessage(), $formData);
            
            return [
                'status' => 'error',
                'process_id' => $processId,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de entrada
     */
    private function validateInput($formData) {
        $required = ['nombre', 'email'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($formData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return [
                'valid' => false,
                'details' => 'Campos requeridos faltantes: ' . implode(', ', $missing)
            ];
        }
        
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'details' => 'Email inválido'
            ];
        }
        
        return [
            'valid' => true,
            'details' => 'Validación exitosa'
        ];
    }
    
    /**
     * Procesar scoring con confirmación
     */
    private function processScoring($formData) {
        $scoringData = [
            'name' => $formData['nombre'] ?? $formData['contact_name'] ?? $formData['your-name'] ?? 'Sin Nombre',
            'email' => $formData['email'] ?? $formData['ea_email'] ?? $formData['your-email'] ?? '',
            'phone' => $formData['telefono'] ?? $formData['your-phone'] ?? '',
            'company' => $formData['org_name'] ?? $formData['company'] ?? $formData['entity'] ?? $formData['institucion'] ?? '',
            'role' => $formData['tipo_institucion'] ?? $formData['sector'] ?? $formData['interes'] ?? $formData['especialidad'] ?? '',
            'country' => $formData['pais_cf7'] ?? $formData['pais_otro'] ?? $formData['ea_country'] ?? '',
            'city' => $formData['ciudad_cf7'] ?? $formData['ea_city'] ?? '',
            'perfil' => $formData['perfil'] ?? 'general',
            'tipo_institucion' => $formData['tipo_institucion'] ?? '',
            'numero_estudiantes' => (int)($formData['numero_estudiantes'] ?? 0),
            'poblacion' => (int)($formData['poblacion'] ?? 0),
            'modality' => $formData['modality'] ?? '',
            'ea_source' => $formData['ea_source'] ?? null,
            'ea_lang' => $formData['ea_lang'] ?? null,
        ];
        
        return LeadScoring::calculate($scoringData);
    }
    
    /**
     * Preparar datos para Monday
     */
    private function prepareMondayData($formData, $scoring) {
        $missionPartner = ($formData['perfil'] === 'pioneer') ? $formData['nombre'] : '';
        
        return [
            'name' => $formData['nombre'] ?? $formData['contact_name'] ?? $formData['your-name'] ?? 'Sin Nombre',
            'email' => $formData['email'],
            'company' => $formData['org_name'] ?? $formData['company'] ?? $formData['entity'] ?? $formData['institucion'] ?? '',
            'phone' => $formData['telefono'] ?? $formData['your-phone'] ?? '',
            'country' => $formData['pais_cf7'] ?? $formData['pais_otro'] ?? $formData['ea_country'] ?? '',
            'role' => $formData['tipo_institucion'] ?? $formData['sector'] ?? $formData['interes'] ?? $formData['especialidad'] ?? '',
            'score' => $scoring['total'],
            'classification' => $scoring['priority_label'],
            'role_detected' => $scoring['detected_role'],
            'tipo_lead' => $scoring['tipo_lead'],
            'canal_origen' => $scoring['canal_origen'],
            'idioma' => $scoring['idioma'],
            'mission_partner' => $missionPartner,
            'is_disposable' => $this->isDisposableEmail($formData['email'])
        ];
    }
    
    /**
     * Crear o actualizar lead en Monday
     */
    private function createOrUpdateLead($data, $formData) {
        $boardId = 18392144864; // MC – Lead Master Intake
        $emailColumnId = 'lead_email';
        
        // Preparar valores de columna
        $basicColumnValues = [
            'lead_email' => ['email' => $data['email'], 'text' => $data['email']],
            'lead_company' => $data['company'],
            'text' => $data['role'],
            'lead_phone' => ['phone' => $data['phone'], 'country_short_name' => 'ES'],
            'lead_status' => ['label' => $data['is_disposable'] ? 'No calificado' : 'Lead nuevo'],
            'numeric_mkyn2py0' => $data['score'],
            'classification_status' => ['label' => $data['classification']],
            'role_detected_new' => ['label' => $data['role_detected']],
            'text_mkyn95hk' => $data['country'],
            'text_mkypn0m' => $data['mission_partner'],
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d')],
            'long_text_mkypqppc' => json_encode($data) // Datos completos para referencia
        ];
        
        // Verificar duplicados
        $existingItems = $this->monday->getItemsByColumnValue($boardId, $emailColumnId, $data['email']);
        
        if (!empty($existingItems)) {
            // Actualizar existente
            $itemIdToUpdate = $existingItems[0]['id'];
            $updateResponse = $this->monday->updateItem($boardId, $itemIdToUpdate, $basicColumnValues);
            $itemId = $updateResponse['update_item']['id'] ?? $itemIdToUpdate;
            $action = 'updated';
        } else {
            // Crear nuevo
            $itemResponse = $this->monday->createItem($boardId, $data['name'], $basicColumnValues);
            $itemId = $itemResponse['create_item']['id'];
            $action = 'created';
        }
        
        // Actualizar columnas dropdown
        if ($itemId) {
            try {
                $this->monday->changeSimpleColumnValue($boardId, $itemId, 'type_of_lead', $data['tipo_lead']);
            } catch (Exception $e) {
                error_log("Error actualizando Tipo de Lead: " . $e->getMessage());
            }
            
            try {
                $this->monday->changeSimpleColumnValue($boardId, $itemId, 'source_channel', $data['canal_origen']);
            } catch (Exception $e) {
                error_log("Error actualizando Canal de Origen: " . $e->getMessage());
            }
            
            try {
                $this->monday->changeSimpleColumnValue($boardId, $itemId, 'language', $data['idioma']);
            } catch (Exception $e) {
                error_log("Error actualizando Idioma: " . $e->getMessage());
            }
        }
        
        return [
            'action' => $action,
            'item_id' => $itemId,
            'item_name' => $data['name']
        ];
    }
    
    /**
     * Verificar si el email es desechable
     */
    private function isDisposableEmail($email) {
        $disposableDomains = ['tempmail.com', 'guerrillamail.com', '10minutemail.com', 'mailinator.com'];
        $emailDomain = substr(strrchr($email, "@"), 1);
        return in_array($emailDomain, $disposableDomains);
    }
    
    /**
     * Log de actividad
     */
    private function logActivity($entry) {
        $logLine = json_encode($entry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log de errores
     */
    private function logError($error, $formData = null) {
        $errorEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $error,
            'form_data' => $formData
        ];
        $errorLine = json_encode($errorEntry) . "\n";
        file_put_contents($this->errorLogFile, $errorLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obtener el estado de un proceso por ID
     */
    public function getProcessStatus($processId) {
        $logContent = file_get_contents($this->logFile);
        $logLines = explode("\n", $logContent);
        
        foreach ($logLines as $line) {
            if (empty(trim($line))) continue;
            
            $logEntry = json_decode($line, true);
            if ($logEntry && $logEntry['process_id'] === $processId) {
                return $logEntry;
            }
        }
        
        return null;
    }
}

// Si se llama directamente para prueba
if (php_sapi_name() === 'cli') {
    $confirm = new WebhookConfirmation();
    
    $testData = [
        'nombre' => 'Test Confirmation System',
        'email' => 'test.confirmation@test.com',
        'pais_cf7' => 'España',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 10000,
        'telefono' => '999888777'
    ];
    
    echo "Testing Webhook Confirmation System...\n";
    $result = $confirm->processForm($testData);
    print_r($result);
}

?>