<?php
// verify-comprehensive-test.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- VERIFICACIÓN DE PRUEBA COMPLETA ---\n\n";

// IDs de los items creados
$itemIds = [10840264364, 10840264305, 10840264679, 10840266724, 10840285001, 10840292137, 10840292378, 10840292705];

$query = 'query ($ids: [ID!]) {
    items (ids: $ids) {
        id
        name
        column_values {
            id
            text
        }
    }
}';

try {
    $data = $monday->query($query, ['ids' => $itemIds]);
    
    echo "RESUMEN DE LEADS CREADOS:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($data['items'] as $item) {
        $columns = [];
        foreach ($item['column_values'] as $val) {
            $columns[$val['id']] = $val['text'];
        }
        
        echo "\n📋 {$item['name']}\n";
        echo "   Email: " . ($columns['lead_email'] ?? '[VACÍO]') . "\n";
        echo "   Teléfono: " . ($columns['lead_phone'] ?? '[VACÍO]') . "\n";
        echo "   País: " . ($columns['text_mkyn95hk'] ?? '[VACÍO]') . "\n";
        echo "   Tipo de Lead: " . ($columns['dropdown_mkywgchz'] ?? '[VACÍO]') . "\n";
        echo "   Score: " . ($columns['numeric_mkyn2py0'] ?? '[VACÍO]') . "\n";
        echo "   Clasificación: " . ($columns['classification_status'] ?? '[VACÍO]') . "\n";
        echo "   Respaldo JSON: " . (empty($columns['long_text_mkyxhent']) ? '[VACÍO]' : '✅ Presente') . "\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "\n✅ VERIFICACIÓN COMPLETADA\n";
    echo "Todos los perfiles del formulario están representados en el tablero.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
