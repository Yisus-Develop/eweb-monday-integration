<?php

class LeadScoring {
    
    // Reglas de puntuación (Blueprint)
    private static $rules = [
        'recommendation' => 10, // Mission Partner
        'role_vip' => 10,       // Rector, CEO, Alcalde
        'priority_country' => 5,
        'event_contact' => 5,
        'website_organic' => 5,
        'full_form' => 3,
        'lead_ads' => 3
    ];

    /**
     * Calcula el Score basado en los datos del formulario
     */
    public static function calculate($data) {
        $score = 0;
        $breakdown = [];

        // 1. Rol VIP (Rector, CEO, Alcalde)
        // Se busca en el campo 'cargo' o 'mensaje'
        $vipKeywords = ['rector', 'alcalde', 'ceo', 'director general', 'presidente'];
        if (self::containsKeywords($data['role'] ?? '', $vipKeywords)) {
            $score += self::$rules['role_vip'];
            $breakdown[] = 'Rol VIP (+10)';
        }

        // 2. Mission Partner (Recomendación)
        // Si el campo 'mission_partner' no está vacío
        if (!empty($data['mission_partner'])) {
            $score += self::$rules['recommendation'];
            $breakdown[] = 'Mission Partner (+10)';
        }

        // 3. País Prioritario
        // Lista de ejemplo - debería venir de config o DB idealmente
        $priorityCountries = ['España', 'México', 'Colombia', 'Argentina', 'Chile']; 
        if (in_array($data['country'] ?? '', $priorityCountries)) {
            $score += self::$rules['priority_country'];
            $breakdown[] = 'País Prioritario (+5)';
        }

        // 4. Formulario Completo (Ejemplo: si tiene teléfono)
        if (!empty($data['phone'])) {
            $score += self::$rules['full_form'];
            $breakdown[] = 'Formulario Completo (+3)';
        }

        return [
            'total' => $score,
            'breakdown' => implode(', ', $breakdown),
            'priority_label' => self::getPriorityLabel($score)
        ];
    }

    private static function containsKeywords($text, $keywords) {
        $text = mb_strtolower($text);
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function getPriorityLabel($score) {
        if ($score > 20) return 'HOT';
        if ($score >= 10) return 'WARM';
        return 'COLD';
    }
}
