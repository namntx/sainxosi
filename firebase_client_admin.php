<?php
/**
 * Firebase Admin SDK Client for PHP (Alternative implementation)
 * 
 * To use this instead of firebase_client.php:
 * 1. Install: composer require kreait/firebase-php
 * 2. Download service account JSON from Firebase Console
 * 3. Save as firebase-service-account.json in api/ directory
 * 4. Update firebase_client.php to use this implementation
 */

require_once __DIR__ . '/config.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Firestore;

class FirebaseClientAdmin {
    private $firestore;
    
    public function __construct() {
        $serviceAccountPath = defined('FIREBASE_SERVICE_ACCOUNT_PATH') 
            ? FIREBASE_SERVICE_ACCOUNT_PATH 
            : __DIR__ . '/firebase-service-account.json';
        
        if (!file_exists($serviceAccountPath)) {
            throw new Exception("Firebase service account file not found: $serviceAccountPath");
        }
        
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->firestore = $factory->createFirestore();
    }
    
    /**
     * Get all users with expiration dates
     */
    public function getUsers() {
        try {
            $usersRef = $this->firestore->database()->collection('users');
            $documents = $usersRef->documents();
            
            $users = [];
            foreach ($documents as $document) {
                $data = $document->data();
                $userId = $document->id();
                
                $expirationDate = null;
                if (isset($data['expirationDate'])) {
                    $timestamp = $data['expirationDate'];
                    if ($timestamp instanceof \Google\Cloud\Core\Timestamp) {
                        $expirationDate = $timestamp->get();
                    } elseif (is_string($timestamp)) {
                        $expirationDate = new DateTime($timestamp);
                    }
                }
                
                $users[] = [
                    'userId' => $userId,
                    'expirationDate' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
                    'expirationTimestamp' => $expirationDate ? $expirationDate->getTimestamp() : null,
                ];
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
            $userRef = $this->firestore->database()->collection('users')->document($userId);
            $document = $userRef->snapshot();
            
            if (!$document->exists()) {
                return null;
            }
            
            $data = $document->data();
            $expirationDate = null;
            
            if (isset($data['expirationDate'])) {
                $timestamp = $data['expirationDate'];
                if ($timestamp instanceof \Google\Cloud\Core\Timestamp) {
                    $expirationDate = $timestamp->get();
                } elseif (is_string($timestamp)) {
                    $expirationDate = new DateTime($timestamp);
                }
            }
            
            return [
                'userId' => $userId,
                'expirationDate' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
                'expirationTimestamp' => $expirationDate ? $expirationDate->getTimestamp() : null,
            ];
        } catch (Exception $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }
    
    /**
     * Create or update user expiration date
     */
    public function setUserExpiration($userId, DateTime $expirationDate) {
        try {
            $userRef = $this->firestore->database()->collection('users')->document($userId);
            $existing = $userRef->snapshot()->exists();
            
            $userRef->set([
                'expirationDate' => $expirationDate,
            ], ['merge' => true]);
            
            return [
                'action' => $existing ? 'updated' : 'created',
                'userId' => $userId,
            ];
        } catch (Exception $e) {
            throw new Exception("Error setting user expiration: " . $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            $userRef = $this->firestore->database()->collection('users')->document($userId);
            
            if (!$userRef->snapshot()->exists()) {
                throw new Exception("User not found: $userId");
            }
            
            $userRef->delete();
            
            return ['success' => true, 'userId' => $userId];
        } catch (Exception $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }
}

