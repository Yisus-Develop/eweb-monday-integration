<?php
// NewColumnIds.php
// Nuevos IDs de columnas para el tablero de Contactos (18392144862)

class NewColumnIds {
    const EMAIL = 'contact_email';
    const PHONE = 'contact_phone';
    const PUESTO = 'texto';
    const STATUS = 'status';
    
    // Columnas de negocio armonizadas
    const LEAD_SCORE = 'numeric_mkz3ds8m';
    const CLASSIFICATION = 'color_mkz3yyj8';
    const ROLE_DETECTED = 'color_mkz3176k'; // Ahora "Perfil Detectado"
    const COUNTRY = 'text_mkz3vvqv';
    const MISSION_PARTNER = 'text_mkz3epfe'; // DEPRECATED: Se eliminará o renombrará
    const ENTRY_DATE = 'date_mkz3bbqb';
    const NEXT_ACTION = 'date_mkz3devn'; // Ahora "Seguimiento"
    const TYPE_OF_LEAD = 'dropdown_mkz3my1q'; // Ahora "Categoría"
    const SOURCE_CHANNEL = 'dropdown_mkz3jgsb'; // Ahora "Origen"
    const LANGUAGE = 'dropdown_mkz37gb6';
    
    // Nuevas columnas solicitadas
    const AMOUNT = 'numeric_mkz36drs';
    const CITY = 'text_mkz3w9bk';
    const INST_TYPE = 'color_mkz3pvz9'; // Ahora "Entidad"
    const INTERNAL_NOTES = 'long_text_mkz360cv'; // Ahora "Análisis IA"
    const COMMENTS = 'long_text4'; // Ahora "Resumen del Formulario"
}
?>
