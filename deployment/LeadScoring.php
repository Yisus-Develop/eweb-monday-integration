<?php
// LeadScoring.php - Versión Production-Ready
// Responsable de TODO el procesamiento inteligente de leads

class LeadScoring {
    
    /**
     * Obtiene países prioritarios desde configuración
     */
    private static function getPriorityCountries() {
        $configPath = __DIR__ . '/language-config.json';
        if (!file_exists($configPath)) {
            // Fallback hardcoded
            return ['México', 'Colombia', 'España', 'Argentina', 'Chile', 'Perú', 'Brasil', 'Portugal', 'Ecuador', 'Uruguay'];
        }
        
        $config = json_decode(file_get_contents($configPath), true);
        return $config['priority_countries'] ?? [];
    }
    
    /**
     * Calcula Lead Score y detecta todos los atributos
     * 
     * @param array $data Datos del formulario
     * @return array [total, priority_label, breakdown, detected_role, tipo_lead, canal_origen, idioma]
     */
    public static function calculate($data) {
        $score = 0;
        $breakdown = [];
        
        // 1. PERFIL (Máximo: 10 puntos) - CRÍTICO
        $perfilScore = self::scoreByPerfil($data['perfil'] ?? 'general');
        $score += $perfilScore;
        $breakdown['perfil'] = $perfilScore;
        
        // 2. TIPO DE INSTITUCIÓN (Máximo: 5 puntos)
        if (isset($data['tipo_institucion'])) {
            $tipoScore = self::scoreByTipoInstitucion($data['tipo_institucion']);
            $score += $tipoScore;
            $breakdown['tipo_institucion'] = $tipoScore;
        }
        
        // 3. PAÍS PRIORITARIO (5 puntos)
        $country = $data['country'] ?? '';
        $priorityCountries = self::getPriorityCountries();
        if (in_array($country, $priorityCountries)) {
            $score += 5;
            $breakdown['pais_prioritario'] = 5;
        }
        
        // 4. TAMAÑO DE INSTITUCIÓN (Máximo: 3 puntos)
        if (isset($data['numero_estudiantes']) && $data['numero_estudiantes'] > 1000) {
            $score += 3;
            $breakdown['institucion_grande'] = 3;
        }
        
        // 5. POBLACIÓN (para ciudades) (Máximo: 3 puntos)
        if (isset($data['poblacion']) && $data['poblacion'] > 100000) {
            $score += 3;
            $breakdown['ciudad_grande'] = 3;
        }
        
        // 6. FORMULARIO COMPLETO (3 puntos)
        $cleanPhone = self::sanitizePhone($data['phone'] ?? '');
        if (!empty($cleanPhone)) {
            $score += 3;
            $breakdown['formulario_completo'] = 3;
        }
        
        // 7. MODALIDAD (para empresas) (3 puntos)
        if (isset($data['modality']) && $data['modality'] === 'Donación') {
            $score += 3;
            $breakdown['donacion'] = 3;
        }
        
        // Clasificación automática
        $classification = self::classify($score);
        $country = $data['country'] ?? '';
        
        return [
            'total' => $score,
            'priority_label' => $classification,
            'breakdown' => $breakdown,
            'detected_role' => self::detectRole($data),
            'tipo_lead' => self::mapPerfilToTipoLead($data['perfil'] ?? 'general'),
            'canal_origen' => self::detectChannel($data),
            'idioma' => self::detectLanguage($data),
            'country_iso' => self::getCountryISO($country),
            'clean_phone' => self::sanitizePhone($data['phone'] ?? '')
        ];
    }
    
    private static function scoreByPerfil($perfil) {
        $scores = [
            'pioneer' => 10,      // Mission Partner - VIP
            'institucion' => 10,  // Rector/Director - VIP
            'ciudad' => 10,       // Alcalde - VIP
            'empresa' => 5,       // Corporate
            'pais' => 5,          // Interesado País
            'mentor' => 3,        // Maestro
            'zer' => 3,           // Joven
            'general' => 0
        ];
        
        return $scores[$perfil] ?? 0;
    }
    
    private static function scoreByTipoInstitucion($tipo) {
        if (stripos($tipo, 'Universidad') !== false) return 5;
        if (stripos($tipo, 'Escuela') !== false) return 3;
        return 0;
    }
    
    private static function classify($score) {
        if ($score > 20) return 'HOT';
        if ($score >= 10) return 'WARM';
        return 'COLD';
    }
    
    private static function detectRole($data) {
        $perfil = $data['perfil'] ?? 'general';

        // Si no viene perfil, intentar inferir por otros campos
        if ($perfil === 'general') {
            if (isset($data['numero_estudiantes']) || isset($data['tipo_institucion'])) $perfil = 'institucion';
            elseif (isset($data['ea_institution']) && stripos($data['ea_institution'], 'Universidad') !== false) $perfil = 'institucion';
            elseif (isset($data['org_name']) || isset($data['modality'])) $perfil = 'empresa';
        }

        $roleMap = [
            'pioneer' => 'Mission Partner',
            'institucion' => 'Rector/Director',
            'ciudad' => 'Alcalde/Gobierno',
            'empresa' => 'Corporate',
            'mentor' => 'Maestro/Mentor',
            'pais' => 'Interesado País',
            'zer' => 'Joven',
            'general' => 'Maestro/Mentor'
        ];

        return $roleMap[$perfil] ?? 'Maestro/Mentor';
    }
    
    private static function mapPerfilToTipoLead($perfil) {
        $map = [
            'institucion' => 'Institución',
            'ciudad' => 'Ciudad',
            'empresa' => 'Empresa',
            'pioneer' => 'Pioneer',
            'mentor' => 'Mentor',
            'pais' => 'País',
            'zer' => 'Zer',
            'general' => 'General'
        ];
        
        return $map[$perfil] ?? 'Otro';
    }
    
    private static function detectChannel($data) {
        // Si tiene ea_source (del formulario de alertas)
        if (isset($data['ea_source'])) {
            $sourceMap = [
                'popup' => 'Otro', // Newsletter no existe en Monday
                'form' => 'Website',
            ];
            return $sourceMap[$data['ea_source']] ?? 'Website';
        }
        
        // Si es Mission Partner
        if (($data['perfil'] ?? '') === 'pioneer') {
            return 'Mission Partner';
        }
        
        // Default
        return 'Website';
    }
    
    
    /**
     * Detecta el idioma basado en configuración dinámica
     * Soporta múltiples idiomas sin modificar código
     */
    private static function detectLanguage($data) {
        // Si viene explícitamente del formulario
        if (isset($data['ea_lang']) && !empty($data['ea_lang'])) {
            return $data['ea_lang'];
        }
        
        // Cargar configuración de idiomas
        $configPath = __DIR__ . '/language-config.json';
        if (!file_exists($configPath)) {
            return 'Español'; // Fallback si no existe config
        }
        
        $config = json_decode(file_get_contents($configPath), true);
        $country = $data['country'] ?? '';
        
        if (empty($country)) {
            return $config['default_language'] === 'es' ? 'Español' : $config['languages'][$config['default_language']]['name'];
        }
        
        // Buscar país en configuración (case-insensitive)
        foreach ($config['languages'] as $langCode => $langData) {
            foreach ($langData['countries'] as $configCountry) {
                if (strcasecmp($country, $configCountry) === 0) {
                    return $langData['name'];
                }
            }
        }
        
        // Default si no se encuentra
        return $config['languages'][$config['default_language']]['name'];
    }

    /**
     * Obtiene el código ISO del país para formato de teléfono
     */
    public static function getCountryISO($country) {
        $configPath = __DIR__ . '/language-config.json';
        if (!file_exists($configPath)) return 'ES';

        $config = json_decode(file_get_contents($configPath), true);
        return $config['iso_mapping'][$country] ?? 'ES';
    }

    /**
     * Limpia el teléfono de espacios, guiones y puntos
     */
    public static function sanitizePhone($phone) {
        if (empty($phone)) return '';
        // Eliminar todo lo que no sea dígito o el signo +
        return preg_replace('/[^\d+]/', '', $phone);
    }
}
