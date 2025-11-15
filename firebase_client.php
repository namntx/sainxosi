<?php
/**
 * Firebase REST API Client for PHP
 * Used to manage user expiration dates in Firestore
 * 
 * NOTE: This uses REST API which is subject to Firestore security rules.
 * For production, consider using Firebase Admin SDK instead.
 */

class FirebaseClient {
    private $projectId;
    private $apiKey;
    private $baseUrl;
    private $useAdminSDK;
    
    public function __construct() {
        $this->projectId = defined('FIREBASE_PROJECT_ID') ? FIREBASE_PROJECT_ID : 'xsne-dcb01';
        $this->apiKey = defined('FIREBASE_API_KEY') ? FIREBASE_API_KEY : 'AIzaSyD_x7X-MvWzZA0wk8FE7kNA2SrnY7Se04c';
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        
        // Check if Admin SDK is available
        $this->useAdminSDK = file_exists(__DIR__ . '/vendor/autoload.php') && 
                            file_exists(defined('FIREBASE_SERVICE_ACCOUNT_PATH') ? FIREBASE_SERVICE_ACCOUNT_PATH : __DIR__ . '/firebase-service-account.json');
        
        if ($this->useAdminSDK) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
    }
    
    /**
     * Make HTTP request to Firestore REST API
     */
    private function request($method, $path, $data = null) {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
        ];
        
        // Add API key to URL for GET requests, or as header for POST/PATCH
        if ($method === 'GET') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'key=' . $this->apiKey;
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'key=' . $this->apiKey;
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? $response;
            throw new Exception("HTTP Error $httpCode: $errorMsg");
        }
        
        return $decoded;
    }
    
    /**
     * Convert Firestore timestamp to PHP DateTime
     * Handles multiple Firestore timestamp formats
     */
    private function timestampToDateTime($timestampValue) {
        if (!$timestampValue) {
            return null;
        }
        
        // Format 1: Direct timestamp object {"seconds": "1234567890", "nanos": 0}
        if (is_array($timestampValue)) {
            if (isset($timestampValue['seconds'])) {
                $seconds = is_string($timestampValue['seconds']) ? (int)$timestampValue['seconds'] : $timestampValue['seconds'];
                return new DateTime('@' . $seconds);
            }
        }
        
        // Format 2: String timestamp (ISO 8601)
        if (is_string($timestampValue)) {
            try {
                return new DateTime($timestampValue);
            } catch (Exception $e) {
                // Try as Unix timestamp
                if (is_numeric($timestampValue)) {
                    return new DateTime('@' . (int)$timestampValue);
                }
            }
        }
        
        // Format 3: Numeric Unix timestamp
        if (is_numeric($timestampValue)) {
            return new DateTime('@' . (int)$timestampValue);
        }
        
        return null;
    }
    
    /**
     * Convert PHP DateTime to Firestore timestamp
     */
    private function dateTimeToTimestamp(DateTime $dateTime) {
        // Firestore expects: {"seconds": "1234567890", "nanos": 0}
        return [
            'seconds' => (string)$dateTime->getTimestamp(),
            'nanos' => 0,
        ];
    }
    
    /**
     * Get all users with expiration dates
     */
    public function getUsers() {
        try {
            $response = $this->request('GET', '/users');
            
            $users = [];
            if (isset($response['documents']) && is_array($response['documents'])) {
                foreach ($response['documents'] as $doc) {
                    // Extract user ID from document name: projects/xxx/databases/(default)/documents/users/UID
                    $nameParts = explode('/', $doc['name']);
                    $userId = end($nameParts);
                    $fields = $doc['fields'] ?? [];
                    
                    $expirationDate = null;
                    // Firestore REST API returns fields in format: {"fieldName": {"valueType": value}}
                    // Check multiple possible formats
                    if (isset($fields['expirationDate'])) {
                        $expirationField = $fields['expirationDate'];
                        
                        // Format 1: {"timestampValue": "2024-12-31T23:59:59Z"}
                        if (isset($expirationField['timestampValue'])) {
                            $expirationDate = $this->timestampToDateTime($expirationField['timestampValue']);
                        }
                        // Format 2: {"timestampValue": {"seconds": "1234567890", "nanos": 0}}
                        elseif (is_array($expirationField) && isset($expirationField['seconds'])) {
                            $expirationDate = $this->timestampToDateTime($expirationField);
                        }
                        // Format 3: Direct timestamp value
                        else {
                            $expirationDate = $this->timestampToDateTime($expirationField);
                        }
                    }
                    
                    $users[] = [
                        'userId' => $userId,
                        'expirationDate' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
                        'expirationTimestamp' => $expirationDate ? $expirationDate->getTimestamp() : null,
                    ];
                }
            }
            
            return $users;
        } catch (Exception $e) {
            throw new Exception("Error fetching users: " . $e->getMessage());
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUser($userId) {
        try {
            $response = $this->request('GET', "/users/$userId");
            
            $fields = $response['fields'] ?? [];
            $expirationDate = null;
            
            if (isset($fields['expirationDate'])) {
                $expirationField = $fields['expirationDate'];
                
                // Format 1: {"timestampValue": "2024-12-31T23:59:59Z"}
                if (isset($expirationField['timestampValue'])) {
                    $expirationDate = $this->timestampToDateTime($expirationField['timestampValue']);
                }
                // Format 2: {"timestampValue": {"seconds": "1234567890", "nanos": 0}}
                elseif (is_array($expirationField) && isset($expirationField['seconds'])) {
                    $expirationDate = $this->timestampToDateTime($expirationField);
                }
                // Format 3: Direct timestamp value
                else {
                    $expirationDate = $this->timestampToDateTime($expirationField);
                }
            }
            
            return [
                'userId' => $userId,
                'expirationDate' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
                'expirationTimestamp' => $expirationDate ? $expirationDate->getTimestamp() : null,
            ];
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                return null;
            }
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }
    
    /**
     * Create or update user expiration date
     */
    public function setUserExpiration($userId, DateTime $expirationDate) {
        try {
            $timestampStr = $this->dateTimeToTimestamp($expirationDate);
            $data = [
                'fields' => [
                    'expirationDate' => [
                        'timestampValue' => $timestampStr,
                    ],
                ],
            ];
            
            // Try to get existing user first
            $existing = $this->getUser($userId);
            
            if ($existing) {
                // Update existing - use PATCH with updateMask
                $url = "/users/$userId?updateMask.fieldPaths=expirationDate";
                $this->request('PATCH', $url, $data);
                return ['action' => 'updated', 'userId' => $userId];
            } else {
                // Create new - Firestore requires document ID in URL
                $this->request('PATCH', "/users/$userId", $data);
                return ['action' => 'created', 'userId' => $userId];
            }
        } catch (Exception $e) {
            throw new Exception("Error setting user expiration: " . $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            $this->request('DELETE', "/users/$userId");
            return ['success' => true, 'userId' => $userId];
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                throw new Exception("User not found: $userId");
            }
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }
}

