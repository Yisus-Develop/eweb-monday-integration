<?php
// cleanup-plan.php
// Plan detallado para la limpieza del tablero

echo "========================================\n";
echo "  PLAN DETALLADO DE LIMPIEZA            \n";
echo "  Tablero: MC – Lead Master Intake     \n";
echo "========================================\n\n";

echo "1. COLUMNAS A ELIMINAR (duplicadas):\n";
echo "   - dropdown_mkypgz6f: 'Tipo de Lead' (duplicado)\n";
echo "   - dropdown_mkypbsmj: 'Canal de Origen' (duplicado)\n"; 
echo "   - dropdown_mkypzbbh: 'Idioma' (duplicado)\n";
echo "   - date_mkypsy6q: 'Fecha de Entrada' (duplicado)\n";
echo "   - date_mkypeap2: 'Próxima Acción' (duplicado)\n";
echo "   - text_mkypbqgg: 'Mission Partner' (duplicado)\n\n";

echo "2. COLUMNAS A MANTENER (funcionales):\n";
echo "   - classification_status: 'Clasificación' (HOT/WARM/COLD)\n";
echo "   - role_detected_new: 'Rol Detectado' (roles específicos)\n";
echo "   - type_of_lead: 'Tipo de Lead' (opciones correctas)\n";
echo "   - source_channel: 'Canal de Origen' (opciones correctas)\n";
echo "   - language: 'Idioma' (opciones correctas)\n";
echo "   - custom_mkt2ktmt: 'Cronograma de actividades' (funcionalidad avanzada)\n\n";

echo "3. ACCIONES MANUALES REQUERIDAS:\n";
echo "   - Eliminar las columnas duplicadas mencionadas arriba\n";
echo "   - Verificar y eliminar grupos vacíos\n";
echo "   - Mantener la columna 'Cronograma de actividades'\n";
echo "   - No eliminar columnas originales como 'lead_status', 'lead_email', etc.\n\n";

echo "========================================\n";
echo "  INSTRUCCIONES DE LIMPIEZA MANUAL      \n";
echo "========================================\n";
echo "1. En la interfaz de Monday.com:\n";
echo "   - Ir al tablero 'MC – Lead Master Intake'\n";
echo "   - Para cada columna duplicada:\n";
echo "     a) Hacer clic en la columna\n";
echo "     b) Seleccionar 'Eliminar columna'\n";
echo "     c) Confirmar la eliminación\n\n";
echo "2. Para grupos:\n";
echo "   - Identificar grupos vacíos o de prueba\n";
echo "   - Hacer clic en los tres puntos del grupo\n";
echo "   - Seleccionar 'Eliminar grupo'\n\n";

echo "3. VERIFICACIÓN POSTERIOR:\n";
echo "   - Ejecutar webhook para crear un lead de prueba\n";
echo "   - Confirmar que todas las columnas nuevas funcionan\n";
echo "   - Verificar que no hay duplicados\n\n";

echo "========================================\n";
echo "  ¡LISTO PARA LIMPIEZA MANUAL!          \n";
echo "========================================\n";

?>
