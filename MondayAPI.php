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

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if (isset($json['errors'])) {
            throw new Exception('Error Monday API: ' . json_encode($json['errors']));
        }

        return $json['data'];
    }

    public function createItem($boardId, $itemName, $columnValues = []) {
        $query = 'mutation ($boardId: ID!, $itemName: String!, $columnValues: JSON!) {
            create_item (board_id: $boardId, item_name: $itemName, column_values: $columnValues) {
                id
            }
        }';

        $variables = [
            'boardId' => (int)$boardId,
            'itemName' => $itemName,
            'columnValues' => json_encode($columnValues)
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
}
