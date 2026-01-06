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
        $perfil = $data['perfil'] ?? $data['profile'] ?? 'general';
        $perfilScore = self::scoreByPerfil($perfil);
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
        $numEstudiantes = $data['numero_estudiantes'] ?? 0;
        if ($numEstudiantes > 1000) {
            $score += 3;
            $breakdown['institucion_grande'] = 3;
        }
        
        // 5. POBLACIÓN (para ciudades) (Máximo: 3 puntos)
        $poblacion = $data['poblacion'] ?? $data['population'] ?? 0;
        if ($poblacion > 100000) {
            $score += 3;
            $breakdown['ciudad_grande'] = 3;
        }
        
        // 6. FORMULARIO COMPLETO (3 puntos)
        if (!empty($data['phone'])) {
            $score += 3;
            $breakdown['formulario_completo'] = 3;
        }
        
        // 7. MODALIDAD (para empresas) (3 puntos)
        if (isset($data['modality']) && $data['modality'] === 'Donación') {
            $score += 3;
            $breakdown['donacion'] = 3;
        }
        
        // 8. ORGANIZACIÓN IDENTIFICADA (2 puntos) - Contexto comercial
        if (!empty($data['organizacion'])) {
            $score += 2;
            $breakdown['organizacion_identificada'] = 2;
        }
        
        // 9. INTERÉS ESPECÍFICO (2 puntos) - Lead cualificado
        if (!empty($data['interes']) || !empty($data['especialidad'])) {
            $score += 2;
            $breakdown['interes_especifico'] = 2;
        }
        
        // 10. MENSAJE DETALLADO (1 punto) - Engagement
        if (!empty($data['mensaje']) && strlen($data['mensaje']) > 50) {
            $score += 1;
            $breakdown['mensaje_detallado'] = 1;
        }
        
        // Clasificación automática
        $classification = self::classify($score);
        
        return [
            'total' => $score,
            'priority_label' => $classification,
            'breakdown' => $breakdown,
            'detected_role' => self::detectRole($data),
            'tipo_lead' => self::mapPerfilToTipoLead($data['perfil'] ?? 'general'),
            'canal_origen' => self::detectChannel($data),
            'idioma' => self::detectLanguage($data)
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
        $tipoStr = is_array($tipo) ? implode(' ', $tipo) : strval($tipo);
        if (stripos($tipoStr, 'Universidad') !== false || stripos($tipoStr, 'University') !== false) return 5;
        if (stripos($tipoStr, 'Escuela') !== false || stripos($tipoStr, 'School') !== false) return 3;
        return 0;
    }
    
    private static function classify($score) {
        if ($score > 20) return 'HOT';
        if ($score >= 10) return 'WARM';
        return 'COLD';
    }
    
    private static function detectRole($data) {
        $perfil = $data['perfil'] ?? 'general';

        $roleMap = [
            'pioneer' => 'Mission Partner',
            'institucion' => 'Rector/Director',
            'ciudad' => 'Alcalde/Gobierno',
            'empresa' => 'Corporate',
            'mentor' => 'Maestro/Mentor',
            'pais' => 'Interesado País',
            'zer' => 'Joven',
            'general' => 'Maestro/Mentor'  // Cambiado a un valor válido
        ];

        return $roleMap[$perfil] ?? 'Maestro/Mentor';  // Valor por defecto válido
    }
    
    private static function mapPerfilToTipoLead($perfil) {
        $map = [
            'institucion' => 'Universidad',
            'ciudad' => 'Ciudad',
            'empresa' => 'Empresa',
            'pioneer' => 'Universidad',
            'mentor' => 'Escuela',
            'pais' => 'Otro',
            'zer' => 'Otro',
            'general' => 'Otro'
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
}
