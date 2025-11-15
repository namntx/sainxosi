<?php
/**
 * API endpoint để Flutter app gọi để fetch và insert kết quả xổ số
 * 
 * Usage:
 *   GET /api/api_fetch.php?region=mn&date=2025-11-12
 *   POST /api/api_fetch.php với JSON body: {"region": "mn", "date": "2025-11-12"}
 * 
 * Response:
 *   {"success": true, "inserted": 3, "updated": 0, "errors": 0}
 *   {"success": false, "error": "Error message"}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/az24_parser.php';

try {
    // Get parameters from GET or POST
    $region = $_GET['region'] ?? $_POST['region'] ?? null;
    $dateStr = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
    
    // If POST with JSON body
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $region = $input['region'] ?? $region;
            $dateStr = $input['date'] ?? $dateStr;
        }
    }
    
    if (!$region) {
        throw new Exception('Missing required parameter: region');
    }
    
    // Validate region
    $validRegions = ['mb', 'mt', 'mn', 'bac', 'trung', 'nam'];
    $region = strtolower($region);
    if (!in_array($region, $validRegions)) {
        throw new Exception("Invalid region: $region. Use: mb, mt, mn");
    }
    
    // Normalize region
    if ($region === 'bac') $region = 'mb';
    if ($region === 'trung') $region = 'mt';
    if ($region === 'nam') $region = 'mn';
    
    // Parse date
    try {
        $date = new DateTime($dateStr);
    } catch (Exception $e) {
        throw new Exception("Invalid date format: $dateStr. Use YYYY-MM-DD");
    }
    
    // Fetch from az24.vn (disable verbose output for API)
    $parser = new Az24Parser();
    $parser->setVerbose(false); // Disable debug output for clean JSON response
    $results = $parser->fetchDaily($region, $date);
    
    if (empty($results)) {
        throw new Exception("No results found for $region on " . $date->format('d-m-Y'));
    }
    
    // Insert into Supabase
    $supabase = new SupabaseClient();
    $inserted = 0;
    $updated = 0;
    $errors = 0;
    $errorMessages = [];
    
    foreach ($results as $result) {
        try {
            $stationCode = $result['station_code'] ?? 'mb';
            $drawDate = $result['draw_date'] ?? $date->format('Y-m-d');
            $prizes = $result['prizes'] ?? [];
            
            // Convert prizes format from PHP (db, g1-g8) to Flutter (ĐB, G1-G8)
            $flutterPrizes = [];
            if (isset($prizes['db']) && !empty($prizes['db'])) {
                $flutterPrizes['ĐB'] = $prizes['db'];
            }
            for ($i = 1; $i <= 8; $i++) {
                $key = "g$i";
                if (isset($prizes[$key]) && !empty($prizes[$key])) {
                    $flutterPrizes["G$i"] = $prizes[$key];
                }
            }
            
            if (empty($flutterPrizes)) {
                continue;
            }
            
            // Prepare data for Supabase
            $data = [
                'date' => $drawDate,
                'region' => $region,
                'province' => $stationCode,
                'prizes' => json_encode($flutterPrizes, JSON_UNESCAPED_UNICODE),
            ];
            
            // Upsert (insert or update if exists)
            $existing = $supabase->select('lottery_results', [
                'date' => $drawDate,
                'province' => $stationCode,
            ]);
            
            if (!empty($existing) && is_array($existing) && isset($existing[0])) {
                // Update existing - use filters to update by date and province
                $supabase->update('lottery_results', $data, [
                    'date' => $drawDate,
                    'province' => $stationCode,
                ]);
                $updated++;
            } else {
                // Insert new
                $supabase->insert('lottery_results', $data);
                $inserted++;
            }
            
        } catch (Exception $e) {
            $errors++;
            $errorMessages[] = "Error processing {$result['station_code']}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'region' => $region,
        'date' => $date->format('Y-m-d'),
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => $errors,
        'error_messages' => $errorMessages,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

