<?php

class MondayAPI {
    private $token;
    private $apiUrl = 'https://api.monday.com/v2';

    public function __construct($token) {
        $this->token = $token;
    }

    public function query($query, $variables = []) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $this->token
        ];

        $data = [
            'query' => $query,
            // Force JSON object for variables, even if empty array
            'variables' => (empty($variables) ? new stdClass() : $variables)
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $payload = json_encode($data);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }
        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['errors'])) {
            file_put_contents(__DIR__ . '/last_error.json', $response);
            throw new Exception("Error Monday API detectado. Ver last_error.json");
        }

        return $json['data'];
    }

    public function createItem($boardId, $itemName, $columnValues = [], $groupId = null) {
        $query = 'mutation ($boardId: ID!, $itemName: String!, $columnValues: JSON, $groupId: String) {
            create_item (board_id: $boardId, item_name: $itemName, column_values: $columnValues, group_id: $groupId) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemName' => $itemName,
            'columnValues' => is_array($columnValues) ? json_encode($columnValues) : $columnValues, 
            'groupId' => $groupId
        ];

        return $this->query($query, $variables);
    }

    public function createColumn($boardId, $title, $type) {
        $query = 'mutation ($boardId: ID!, $title: String!, $type: ColumnType!) {
            create_column (board_id: $boardId, title: $title, column_type: $type) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'title' => $title,
            'type' => $type
        ];

        $result = $this->query($query, $variables);
        
        if (isset($result['create_column']['id'])) {
            return $result['create_column']['id'];
        } else {
            throw new Exception("Error creating column: " . json_encode($result));
        }
    }

    public function createGroup($boardId, $groupName) {
        $query = 'mutation ($boardId: ID!, $groupName: String!) {
            create_group (board_id: $boardId, group_name: $groupName) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'groupName' => $groupName
        ];

        $result = $this->query($query, $variables);

        if (isset($result['create_group']['id'])) {
            return $result['create_group']['id'];
        } else {
            throw new Exception("Error creating group: " . json_encode($result));
        }
    }

    public function createColumnWithSettings($boardId, $title, $type, $settings = []) {
        $query = 'mutation ($boardId: ID!, $title: String!, $type: ColumnType!, $defaults: JSON) {
            create_column (board_id: $boardId, title: $title, column_type: $type, defaults: $defaults) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'title' => $title,
            'type' => $type,
            'defaults' => json_encode($settings)
        ];

        $result = $this->query($query, $variables);
        
        if (isset($result['create_column']['id'])) {
            return $result['create_column']['id'];
        } else {
            throw new Exception("Error creating column '{$title}': " . json_encode($result));
        }
    }

    public function moveItemToGroup($itemId, $groupId) {
        $query = 'mutation ($itemId: ID!, $groupId: String!) {
            move_item_to_group (item_id: $itemId, group_id: $groupId) {
                id
            }
        }';

        $variables = [
            'itemId' => (int)$itemId,
            'groupId' => $groupId
        ];

        $result = $this->query($query, $variables);

        if (isset($result['move_item_to_group']['id'])) {
            return $result['move_item_to_group']['id'];
        } else {
            throw new Exception("Error moving item to group: " . json_encode($result));
        }
    }

    public function getItemsByColumnValue($boardId, $columnId, $columnValue) {
        $query = 'query ($boardId: ID!, $columnId: String!, $columnValue: String!) {
            items_page_by_column_values (board_id: $boardId, columns: [{column_id: $columnId, column_values: [$columnValue]}]) {
                items {
                    id
                    name
                }
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'columnId' => $columnId,
            'columnValue' => $columnValue,
        ];
        
        $result = $this->query($query, $variables);

        return $result['items_page_by_column_values']['items'] ?? [];
    }

    public function updateItem($boardId, $itemId, $columnValues) {
        $query = 'mutation ($boardId: ID!, $itemId: ID!, $columnValues: JSON) {
            update_item (board_id: $boardId, item_id: $itemId, column_values: $columnValues) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemId' => (int)$itemId,
            'columnValues' => is_array($columnValues) ? json_encode($columnValues) : $columnValues
        ];

        return $this->query($query, $variables);
    }

    public function deleteColumn($boardId, $columnId) {
        $query = 'mutation ($boardId: ID!, $columnId: String!) {
            delete_column (board_id: $boardId, column_id: $columnId) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'columnId' => $columnId
        ];

        $result = $this->query($query, $variables);

        if (isset($result['delete_column']['id'])) {
            return $result['delete_column']['id'];
        } else {
            throw new Exception("Error deleting column '{$columnId}': " . json_encode($result));
        }
    }

    // Método para consultas directas sin procesamiento de errores específico
    public function rawQuery($query) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $this->token
        ];

        $data = [
            'query' => $query
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        // Devuelve el resultado completo incluyendo errores
        return json_decode($response, true);
    }

    public function changeColumnValue($boardId, $itemId, $columnId, $value) {
        // En Monday API v2023-10+, la variable debe ser JSON
        // pero el contenido DEBE ser un string JSON (double encode).
        $query = 'mutation ($boardId: ID!, $itemId: ID!, $columnId: String!, $value: JSON!) {
            change_column_value (board_id: $boardId, item_id: $itemId, column_id: $columnId, value: $value) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemId' => (int)$itemId,
            'columnId' => $columnId,
            'value' => json_encode($value) 
        ];

        return $this->query($query, $variables);
    }

    public function changeSimpleColumnValue($boardId, $itemId, $columnId, $value, $createLabelsIfMissing = false) {
        // Para columnas dropdown y otras que necesitan formatos especiales
        $query = 'mutation ($boardId: ID!, $itemId: ID!, $columnId: String!, $value: String!, $createLabelsIfMissing: Boolean) {
            change_simple_column_value (
                board_id: $boardId,
                item_id: $itemId,
                column_id: $columnId,
                value: $value,
                create_labels_if_missing: $createLabelsIfMissing
            ) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemId' => (int)$itemId,
            'columnId' => $columnId,
            'value' => $value,  // Este debe ser un string
            'createLabelsIfMissing' => $createLabelsIfMissing
        ];

        return $this->query($query, $variables);
    }

    public function changeMultipleColumnValues($boardId, $itemId, $columnValues) {
        // Para actualizar múltiples columnas a la vez
        $query = 'mutation ($boardId: ID!, $itemId: ID!, $columnValues: JSON!) {
            change_multiple_column_values (
                board_id: $boardId,
                item_id: $itemId,
                column_values: $columnValues
            ) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemId' => (int)$itemId,
            'columnValues' => $columnValues
        ];

        return $this->query($query, $variables);
    }
}
