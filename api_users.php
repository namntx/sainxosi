<?php
/**
 * API endpoint for user management (Firebase)
 * 
 * GET /api/api_users.php - Get all users
 * POST /api/api_users.php - Create/Update user expiration
 * DELETE /api/api_users.php?userId=xxx - Delete user
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

// Try to use Admin SDK if available, otherwise use REST API
$useAdminSDK = file_exists(__DIR__ . '/vendor/autoload.php') && 
               file_exists(defined('FIREBASE_SERVICE_ACCOUNT_PATH') ? FIREBASE_SERVICE_ACCOUNT_PATH : __DIR__ . '/firebase-service-account.json');

if ($useAdminSDK) {
    require_once __DIR__ . '/firebase_client_admin.php';
    $firebaseClass = 'FirebaseClientAdmin';
} else {
    require_once __DIR__ . '/firebase_client.php';
    $firebaseClass = 'FirebaseClient';
}

try {
    $firebase = new $firebaseClass();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all users
        $users = $firebase->getUsers();
        
        // Debug: Check if any user has expirationDate
        $usersWithExpiration = 0;
        foreach ($users as $user) {
            if ($user['expirationDate'] !== null) {
                $usersWithExpiration++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users),
            'debug' => [
                'users_with_expiration' => $usersWithExpiration,
                'note' => 'If expirationDate is null, the field may not exist in Firestore. Use the dashboard to set expiration dates.',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create or update user
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $userId = $input['userId'] ?? null;
        $expirationDateStr = $input['expirationDate'] ?? null;
        
        if (!$userId) {
            throw new Exception('Missing required parameter: userId');
        }
        
        if (!$expirationDateStr) {
            throw new Exception('Missing required parameter: expirationDate');
        }
        
        // Parse date (accept both date and datetime)
        try {
            $expirationDate = new DateTime($expirationDateStr);
        } catch (Exception $e) {
            throw new Exception("Invalid date format: $expirationDateStr");
        }
        
        $result = $firebase->setUserExpiration($userId, $expirationDate);
        
        echo json_encode([
            'success' => true,
            'message' => "User {$result['action']} successfully",
            'data' => $result,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete user
        $userId = $_GET['userId'] ?? null;
        
        if (!$userId) {
            throw new Exception('Missing required parameter: userId');
        }
        
        $result = $firebase->deleteUser($userId);
        
        echo json_encode([
            'success' => true,
            'message' => "User deleted successfully",
            'data' => $result,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

