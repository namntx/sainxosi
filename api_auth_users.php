<?php
/**
 * API endpoint for Firebase Authentication users
 *
 * GET /api/api_auth_users.php - Get all Firebase Auth users
 * GET /api/api_auth_users.php?uid=xxx - Get specific user by UID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

// Check if Admin SDK is available
$useAdminSDK = file_exists(__DIR__ . '/vendor/autoload.php') &&
               file_exists(defined('FIREBASE_SERVICE_ACCOUNT_PATH') ? FIREBASE_SERVICE_ACCOUNT_PATH : __DIR__ . '/firebase-service-account.json');

if (!$useAdminSDK) {
    echo json_encode([
        'success' => false,
        'error' => 'Firebase Admin SDK is required for this endpoint. Please install: composer require kreait/firebase-php'
    ]);
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

try {
    // Initialize Firebase Admin SDK
    $serviceAccountPath = defined('FIREBASE_SERVICE_ACCOUNT_PATH')
        ? FIREBASE_SERVICE_ACCOUNT_PATH
        : __DIR__ . '/firebase-service-account.json';

    if (!file_exists($serviceAccountPath)) {
        throw new Exception('Firebase service account file not found: ' . $serviceAccountPath);
    }

    $factory = (new Factory)->withServiceAccount($serviceAccountPath);
    $auth = $factory->createAuth();

    // Get specific user by UID
    if (isset($_GET['uid'])) {
        $uid = $_GET['uid'];

        try {
            $user = $auth->getUser($uid);

            echo json_encode([
                'success' => true,
                'user' => [
                    'uid' => $user->uid,
                    'email' => $user->email,
                    'displayName' => $user->displayName,
                    'photoUrl' => $user->photoUrl,
                    'emailVerified' => $user->emailVerified,
                    'disabled' => $user->disabled,
                    'metadata' => [
                        'createdAt' => $user->metadata->createdAt ? $user->metadata->createdAt->format('Y-m-d H:i:s') : null,
                        'lastLoginAt' => $user->metadata->lastLoginAt ? $user->metadata->lastLoginAt->format('Y-m-d H:i:s') : null,
                    ]
                ]
            ]);
        } catch (FirebaseException $e) {
            echo json_encode([
                'success' => false,
                'error' => 'User not found: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // List all users
    $users = [];
    $maxResults = 1000; // Maximum allowed by Firebase

    try {
        foreach ($auth->listUsers($maxResults) as $user) {
            $users[] = [
                'uid' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName,
                'photoUrl' => $user->photoUrl,
                'emailVerified' => $user->emailVerified,
                'disabled' => $user->disabled,
                'metadata' => [
                    'createdAt' => $user->metadata->createdAt ? $user->metadata->createdAt->format('Y-m-d H:i:s') : null,
                    'lastLoginAt' => $user->metadata->lastLoginAt ? $user->metadata->lastLoginAt->format('Y-m-d H:i:s') : null,
                ]
            ];
        }

        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    } catch (FirebaseException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to list users: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
