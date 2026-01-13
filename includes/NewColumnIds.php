<?php
// NewColumnIds.php
// IDs REALES detectados en el tablero "MC – Contactos" (18392144862)

class NewColumnIds {
    const EMAIL = 'contact_email';
    const PHONE = 'contact_phone';
    const PUESTO = 'texto';
    const STATUS = 'status'; // "Tipo" (Lead, Cliente, etc.)
    
    // Columnas de negocio armonizadas
    const LEAD_SCORE = 'numeric_mkz3ds8m';
    const CLASSIFICATION = 'color_mkz3yyj8'; // Clasificación (HOT, WARM, COLD)
    const ROLE_DETECTED = 'color_mkz3176k';  // Perfil Detectado
    const COUNTRY = 'text_mkz3vvqv';
    const CITY = 'text_mkz3w9bk';
    
    const ENTRY_DATE = 'date_mkz3bbqb';
    const NEXT_ACTION = 'date_mkz3devn'; // Seguimiento
    
    const TYPE_OF_LEAD = 'dropdown_mkz3my1q'; // Categoría
    const SOURCE_CHANNEL = 'dropdown_mkz3jgsb'; // Origen
    const LANGUAGE = 'dropdown_mkz37gb6';
    
    const AMOUNT = 'numeric_mkz36drs';
    const ENTITY_TYPE = 'color_mkz3pvz9'; // Entidad (Universidad, Colegio, etc.)
    const IA_ANALYSIS = 'long_text_mkz360cv'; // Análisis IA
    const FORM_SUMMARY = 'long_text4'; // Resumen del Formulario
    const PARTNER_REF = 'text_mkz3epfe'; // Interés/Especialidad (antes "Partner Ref")
}
?>
