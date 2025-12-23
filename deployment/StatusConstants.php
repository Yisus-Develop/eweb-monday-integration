<?php
// StatusConstants.php
// Constantes para las etiquetas de status basadas en la configuración actual

class StatusConstants {
    // Etiquetas reales para la columna 'Clasificación' (classification_status)
    const CLASSIFICATION_HOT = 'HOT';
    const CLASSIFICATION_WARM = 'WARM';
    const CLASSIFICATION_COLD = 'COLD';
    
    // Etiquetas reales para la columna 'Rol Detectado' (role_detected_new)
    const ROLE_MISSION_PARTNER = 'Mission Partner';
    const ROLE_RECTOR_DIRECTOR = 'Rector/Director';
    const ROLE_MAYOR_GOVERNMENT = 'Alcalde/Gobierno';
    const ROLE_CORPORATE = 'Corporate';
    const ROLE_TEACHER_MENTOR = 'Maestro/Mentor';
    const ROLE_YOUNG = 'Joven';
    
    // IDs de Grupos según la configuración de Monday.com
    const GROUP_HOT = 'group_mkypkk91';
    const GROUP_WARM = 'group_mkypjxfw';
    const GROUP_COLD = 'group_mkypvwd';
    const GROUP_NEW = 'topics';
    
    // Funciones para mapeo de puntuación a clasificación actual
    public static function getScoreClassification($score) {
        if ($score >= 15) return self::CLASSIFICATION_HOT;
        if ($score >= 8) return self::CLASSIFICATION_WARM;
        return self::CLASSIFICATION_COLD;
    }
    
    public static function getGroupById($label) {
        switch($label) {
            case self::CLASSIFICATION_HOT: return self::GROUP_HOT;
            case self::CLASSIFICATION_WARM: return self::GROUP_WARM;
            case self::CLASSIFICATION_COLD: return self::GROUP_COLD;
            default: return self::GROUP_NEW;
        }
    }
    
    // Funciones para mapeo de rol detectado a etiqueta actual
    public static function getRoleLabel($role) {
        switch(strtolower(trim($role))) {
            case 'mission partner':
            case 'mp':
                return self::ROLE_MISSION_PARTNER;
            case 'rector':
            case 'director':
            case 'rector/director':
                return self::ROLE_RECTOR_DIRECTOR;
            case 'alcalde':
            case 'gobierno':
            case 'alcalde/gobierno':
                return self::ROLE_MAYOR_GOVERNMENT;
            case 'corporate':
            case 'empresa':
                return self::ROLE_CORPORATE;
            case 'maestro':
            case 'mentor':
            case 'maestro/mentor':
                return self::ROLE_TEACHER_MENTOR;
            case 'joven':
            case 'young':
                return self::ROLE_YOUNG;
            default:
                return self::CLASSIFICATION_COLD; // Valor por defecto
        }
    }
}
?>
