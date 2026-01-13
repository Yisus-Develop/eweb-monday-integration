<?php
// includes/class-monday-handler.php

class Monday_Handler {

    public static function logMsg($msg, $isError = false) {
        // Solo logueamos si es error O si el modo debug está activo en las opciones
        $debugMode = get_option('monday_debug_mode', 'yes'); // Por defecto 'yes' para seguridad inicial
        if (!$isError && $debugMode !== 'yes') return;

        $logFile = plugin_dir_path(__DIR__) . 'webhook_debug.log';
        
        // Rotación básica de log (5MB)
        if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
            rename($logFile, $logFile . '.' . date('Ymd-His') . '.old');
        }
        
        $prefix = $isError ? '[ERROR] ' : '[INFO]  ';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " $prefix $msg\n", FILE_APPEND);
    }

    public static function process($data) {
        try {
            self::logMsg("Iniciando procesamiento de lead...");

            // Sanitización preventiva
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $data[$key] = implode(', ', array_filter($val));
                }
            }

            // 1. Mapeo de Identidad y Limpieza de Nombre
            $rawName = $data['nombre'] ?? $data['your-name'] ?? $data['ea_firstname'] ?? $data['name'] ?? 'Sin Nombre';
            
            if (is_array($rawName)) {
                $rawName = implode(' ', array_filter($rawName));
            }

            $cleanName = trim(str_ireplace(['Mars Challenge', '«', '»'], '', (string)$rawName));
            if (strlen($cleanName) < 2) {
                $cleanName = !empty($data['email']) ? explode('@', $data['email'])[0] : 'Lead #' . date('His');
            }

            $scoringData = [
                'name'   => $cleanName,
                'email'  => $data['email'] ?? $data['your-email'] ?? $data['ea_email'] ?? '',
                'phone'  => strval($data['telefono'] ?? $data['your-phone'] ?? $data['phone'] ?? ''),
                'country'=> $data['pais_cf7'] ?? $data['ea_country'] ?? $data['country'] ?? '',
                'city'   => $data['ciudad_cf7'] ?? $data['ea_city'] ?? $data['city'] ?? '',
                'perfil' => $data['perfil'] ?? $data['profile'] ?? 'general',
                'organizacion' => $data['org_name'] ?? $data['institucion'] ?? $data['ea_institution'] ?? '',
                'interes' => $data['interes'] ?? '',
                'especialidad' => $data['especialidad'] ?? '',
                'asunto' => $data['asunto'] ?? '',
                'mensaje' => $data['mensaje'] ?? '',
            ];

            if (!filter_var($scoringData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido (" . $scoringData['email'] . ")");
            }

            $scoreResult = LeadScoring::calculate($scoringData);
            
            $apiToken = get_option('monday_api_token');
            $boardId = get_option('monday_board_id');

            if (empty($apiToken) || empty($boardId)) {
                throw new Exception("Credenciales de Monday no configuradas en el plugin.");
            }

            $monday = new MondayAPI($apiToken);

            // Mapeo Entity
            $entityLabel = 'Corporativo';
            $p = strtolower($scoringData['perfil']);
            if (strpos($p, 'institucion') !== false) $entityLabel = 'Universidad';
            elseif (strpos($p, 'zer') !== false) $entityLabel = 'Colegio';

            $puestoFinal = strval($scoreResult['detected_role'] ?? 'Lead');
            if (!empty($scoringData['organizacion'])) {
                $puestoFinal .= ' - ' . $scoringData['organizacion'];
            }

            $statusFinal = ($scoreResult['detected_role'] === 'Joven') ? 'Lead Zers' : StatusConstants::STATUS_LEAD;

            $columnUpdates = [
                NewColumnIds::EMAIL => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
                NewColumnIds::PHONE => ['phone' => $scoringData['phone'] ?: '0000', 'countryShortName' => ($scoringData['country'] ?: 'ES')],
                NewColumnIds::PUESTO => $puestoFinal,
                NewColumnIds::STATUS => ['label' => $statusFinal],
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

            self::logMsg("Creando nuevo lead en Monday: " . $scoringData['name']);
            $resp = $monday->createItem($boardId, $scoringData['name'], []);
            $itemId = $resp['create_item']['id'] ?? null;

            if ($itemId) {
                $monday->changeMultipleColumnValues($boardId, $itemId, json_encode($columnUpdates));
                
                $formTitle = $data['your-subject'] ?? $data['asunto'] ?? 'Nuevo Lead';
                $updateBody = "<b>📋 Datos del Formulario: $formTitle</b><br><ul>";
                foreach ($data as $key => $val) {
                    $updateBody .= "<li><b>$key:</b> " . htmlspecialchars(is_array($val) ? implode(', ', $val) : $val) . "</li>";
                }
                $updateBody .= "</ul>";
                $monday->createUpdate($itemId, $updateBody);
                
                return ['status' => 200, 'id' => $itemId];
            }

            return ['status' => 500, 'message' => 'No se pudo crear el item en Monday.'];

        } catch (Throwable $e) {
            self::logMsg("FALLO EN HANDLER: " . $e->getMessage(), true);
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
