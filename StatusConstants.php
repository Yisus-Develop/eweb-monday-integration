<?php
// StatusConstants.php
// Etiquetas REALES configuradas en el tablero (Case-Sensitive)

class StatusConstants {
    // Column [color_mkz3yyj8] (Clasificación)
    const CLASSIFICATION_HOT = 'HOT';
    const CLASSIFICATION_WARM = 'WARM';
    const CLASSIFICATION_COLD = 'COLD';
    
    // Column [color_mkz3176k] (Perfil Detectado)
    // Basado en el audit real de labels
    const ROLE_PARTNER = 'Partner';
    const ROLE_MISSION_PARTNER = 'Mission Partner';
    const ROLE_RECTOR_DIRECTOR = 'Rector/Director';
    const ROLE_MAYOR_GOVERNMENT = 'Alcalde/Gobierno';
    const ROLE_CORPORATE = 'Corporate';
    const ROLE_TEACHER_MENTOR = 'Maestro/Mentor';
    const ROLE_YOUNG = 'Joven';
    const ROLE_SPECIALTY = 'Especialidad';
    const ROLE_UNIVERSITY = 'Universidad';
    const ROLE_PRIORITY_COUNTRY = 'Interesado País';
    
    // Column [status] (Tipo)
    const STATUS_LEAD = 'Lead';
    
    public static function getScoreClassification($score) {
        if ($score >= 15) return self::CLASSIFICATION_HOT;
        if ($score >= 8) return self::CLASSIFICATION_WARM;
        return self::CLASSIFICATION_COLD;
    }
    
    public static function getRoleLabel($role) {
        $r = strtolower(trim($role));
        switch($r) {
            case 'pioneer':
            case 'mission partner':
                return self::ROLE_MISSION_PARTNER;
            case 'institucion':
            case 'rector':
            case 'director':
            case 'rector/director':
                return self::ROLE_RECTOR_DIRECTOR;
            case 'ciudad':
            case 'alcalde':
            case 'gobierno':
            case 'alcalde/gobierno':
                return self::ROLE_MAYOR_GOVERNMENT;
            case 'empresa':
            case 'corporate':
                return self::ROLE_CORPORATE;
            case 'mentor':
            case 'maestro':
            case 'maestro/mentor':
                return self::ROLE_TEACHER_MENTOR;
            case 'zer':
            case 'joven':
            case 'young':
                return self::ROLE_YOUNG;
            case 'pais':
            case 'interesado país':
                return self::ROLE_PRIORITY_COUNTRY;
            default:
                // IMPORTANTE: Retornar algo que EXISTA en la columna, o null
                return self::ROLE_PRIORITY_COUNTRY; // Fallback seguro
        }
    }
}
?>
