<?php
/**
 * API Client for Epic EHR Backend
 * Handles all communication with the Python Flask backend
 */

class ApiClient {
    private $baseUrl;
    private $timeout;
    
    public function __construct($baseUrl = 'http://localhost:5000/api', $timeout = 30) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }
    
    /**
     * Make a GET request to the API
     */
    public function get($endpoint, $params = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * Make a POST request to the API
     */
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('POST', $url, $data);
    }
    
    /**
     * Make a PUT request to the API
     */
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('PUT', $url, $data);
    }
    
    /**
     * Make a DELETE request to the API
     */
    public function delete($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('DELETE', $url, $data);
    }
    
    /**
     * Execute HTTP request
     */
    private function request($method, $url, $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
        }
        
        return $decoded;
    }
}

// Service classes for specific API endpoints

class PatientService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getAll() {
        return $this->api->get('patients/');
    }
    
    public function getById($id) {
        return $this->api->get("patients/{$id}");
    }
    
    public function getByMrn($mrn) {
        return $this->api->get("patients/mrn/{$mrn}");
    }
    
    public function search($query) {
        return $this->api->get('patients/search', ['q' => $query]);
    }
    
    public function getHeader($patientId) {
        return $this->api->get("patients/{$patientId}/header");
    }
    
    public function getEncounters($patientId) {
        return $this->api->get("patients/{$patientId}/encounters");
    }
    
    public function getCurrentEncounter($patientId) {
        return $this->api->get("patients/{$patientId}/current-encounter");
    }
    
    public function getAllergies($patientId) {
        return $this->api->get("patients/{$patientId}/allergies");
    }
}

class MedicationService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getPatientMedications($patientId, $status = 'Active') {
        return $this->api->get("medications/patient/{$patientId}", ['status' => $status]);
    }
    
    public function getCategorized($patientId) {
        return $this->api->get("medications/patient/{$patientId}/categorized");
    }
    
    public function getMar($patientId, $date = null) {
        $params = $date ? ['date' => $date] : [];
        return $this->api->get("medications/patient/{$patientId}/mar", $params);
    }
    
    public function recordAdministration($data) {
        return $this->api->post('medications/administration', $data);
    }
}

class OrderService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getPatientOrders($patientId, $status = null, $type = null) {
        $params = [];
        if ($status) $params['status'] = $status;
        if ($type) $params['type'] = $type;
        return $this->api->get("orders/patient/{$patientId}", $params);
    }
    
    public function getPending($patientId) {
        return $this->api->get("orders/patient/{$patientId}/pending");
    }
    
    public function getToComplete($patientId) {
        return $this->api->get("orders/patient/{$patientId}/to-complete");
    }
    
    public function getByType($patientId) {
        return $this->api->get("orders/patient/{$patientId}/by-type");
    }
    
    public function create($data) {
        return $this->api->post('orders/', $data);
    }
    
    public function acknowledge($orderId, $acknowledgedBy) {
        return $this->api->post("orders/{$orderId}/acknowledge", ['acknowledged_by' => $acknowledgedBy]);
    }
    
    public function updateStatus($orderId, $status) {
        return $this->api->put("orders/{$orderId}/status", ['status' => $status]);
    }
}

class VitalService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getPatientVitals($patientId, $hours = 24) {
        return $this->api->get("vitals/patient/{$patientId}", ['hours' => $hours]);
    }
    
    public function getLatest($patientId) {
        return $this->api->get("vitals/patient/{$patientId}/latest");
    }
    
    public function getTrends($patientId, $hours = 72) {
        return $this->api->get("vitals/patient/{$patientId}/trends", ['hours' => $hours]);
    }
    
    public function record($data) {
        return $this->api->post('vitals/', $data);
    }
}

class FlowsheetService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getSections($department = null) {
        $params = $department ? ['department' => $department] : [];
        return $this->api->get('flowsheets/sections', $params);
    }
    
    public function getTemplates($section = null) {
        $params = $section ? ['section' => $section] : [];
        return $this->api->get('flowsheets/templates', $params);
    }
    
    public function getGroups() {
        return $this->api->get('flowsheets/groups');
    }
    
    public function getPatientFlowsheet($patientId, $group = null, $hours = 72) {
        $params = ['hours' => $hours];
        if ($group) $params['group'] = $group;
        return $this->api->get("flowsheets/patient/{$patientId}", $params);
    }
    
    public function getGrouped($patientId, $group = null) {
        $params = $group ? ['group' => $group] : [];
        return $this->api->get("flowsheets/patient/{$patientId}/grouped", $params);
    }
    
    public function getColumnView($patientId, $group = null, $hours = 24) {
        $params = ['hours' => $hours];
        if ($group) $params['group'] = $group;
        return $this->api->get("flowsheets/patient/{$patientId}/column-view", $params);
    }
    
    public function createEntry($data) {
        return $this->api->post('flowsheets/entry', $data);
    }
    
    public function updateEntry($entryId, $data) {
        return $this->api->put("flowsheets/entry/{$entryId}", $data);
    }
    
    public function deleteEntry($entryId, $deletedBy = null) {
        return $this->api->delete("flowsheets/entry/{$entryId}", ['deleted_by' => $deletedBy]);
    }
}

class LabService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getPatientLabs($patientId, $days = 30, $panel = null) {
        $params = ['days' => $days];
        if ($panel) $params['panel'] = $panel;
        return $this->api->get("labs/patient/{$patientId}", $params);
    }
    
    public function getLatest($patientId) {
        return $this->api->get("labs/patient/{$patientId}/latest");
    }
    
    public function getCritical($patientId) {
        return $this->api->get("labs/patient/{$patientId}/critical");
    }
    
    public function getByPanel($patientId, $days = 7) {
        return $this->api->get("labs/patient/{$patientId}/by-panel", ['days' => $days]);
    }
    
    public function getTrends($patientId, $testName, $days = 30) {
        return $this->api->get("labs/patient/{$patientId}/trends/{$testName}", ['days' => $days]);
    }
    
    public function acknowledgeCritical($resultId, $acknowledgedBy) {
        return $this->api->post("labs/{$resultId}/acknowledge", ['acknowledged_by' => $acknowledgedBy]);
    }
}

class NoteService {
    private $api;
    
    public function __construct(ApiClient $api) {
        $this->api = $api;
    }
    
    public function getPatientNotes($patientId, $type = null, $days = 90) {
        $params = ['days' => $days];
        if ($type) $params['type'] = $type;
        return $this->api->get("notes/patient/{$patientId}", $params);
    }
    
    public function getNote($noteId) {
        return $this->api->get("notes/{$noteId}");
    }
    
    public function create($data) {
        return $this->api->post('notes/', $data);
    }
    
    public function sign($noteId) {
        return $this->api->post("notes/{$noteId}/sign", []);
    }
    
    public function getSmartPhrases($category = null) {
        $params = $category ? ['category' => $category] : [];
        return $this->api->get('notes/smart-phrases', $params);
    }
}

// Initialize global API client and services
$api = new ApiClient();
$patientService = new PatientService($api);
$medicationService = new MedicationService($api);
$orderService = new OrderService($api);
$vitalService = new VitalService($api);
$flowsheetService = new FlowsheetService($api);
$labService = new LabService($api);
$noteService = new NoteService($api);
