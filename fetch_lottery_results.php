<?php
/**
 * Fetch lottery results from az24.vn and insert into Supabase
 * Based on Az24DailyProvider.php
 * 
 * Usage:
 *   php fetch_lottery_results.php [region] [date]
 *   php fetch_lottery_results.php mn 2025-11-12
 *   php fetch_lottery_results.php mb (uses today's date)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/az24_parser.php';

// Get parameters
$region = $argv[1] ?? 'mb'; // mb, mt, mn
$dateStr = $argv[2] ?? date('Y-m-d'); // YYYY-MM-DD format

// Validate region
$validRegions = ['mb', 'mt', 'mn', 'bac', 'trung', 'nam'];
$region = strtolower($region);
if (!in_array($region, $validRegions)) {
    die("❌ Invalid region: $region. Use: mb, mt, mn\n");
}

// Normalize region
if ($region === 'bac') $region = 'mb';
if ($region === 'trung') $region = 'mt';
if ($region === 'nam') $region = 'mn';

// Parse date
try {
    $date = new DateTime($dateStr);
} catch (Exception $e) {
    die("❌ Invalid date format: $dateStr. Use YYYY-MM-DD\n");
}

echo "🎲 Fetching lottery results for $region on " . $date->format('d-m-Y') . "\n";

// Fetch from az24.vn
$parser = new Az24Parser();
$results = $parser->fetchDaily($region, $date);

if (empty($results)) {
    die("❌ No results found for $region on " . $date->format('d-m-Y') . "\n");
}

echo "✅ Found " . count($results) . " result(s)\n";

// Deduplicate results by date + province (keep last one)
$uniqueResults = [];
foreach ($results as $result) {
    $key = ($result['draw_date'] ?? $date->format('Y-m-d')) . '|' . ($result['station_code'] ?? 'mb');
    $uniqueResults[$key] = $result;
}
$results = array_values($uniqueResults);
echo "📊 After deduplication: " . count($results) . " unique result(s)\n";

// Show unique stations
$stations = array_unique(array_map(function($r) {
    return $r['station_code'] ?? 'mb';
}, $results));
echo "📊 Unique stations: " . implode(', ', $stations) . "\n";

// Insert into Supabase
$supabase = new SupabaseClient();
$inserted = 0;
$updated = 0;
$errors = 0;

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
            echo "⚠️ Skipping $stationCode - no prizes found\n";
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
            echo "✅ Updated: $stationCode on $drawDate\n";
            $updated++;
        } else {
            // Insert new
            $supabase->insert('lottery_results', $data);
            echo "✅ Inserted: $stationCode on $drawDate\n";
            $inserted++;
        }
        
    } catch (Exception $e) {
        echo "❌ Error processing {$result['station_code']}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n📊 Summary:\n";
echo "   Inserted: $inserted\n";
echo "   Updated: $updated\n";
echo "   Errors: $errors\n";
echo "✅ Done!\n";

