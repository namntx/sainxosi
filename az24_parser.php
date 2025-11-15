<?php
/**
 * Parser for az24.vn lottery results
 * Converted from Az24DailyProvider.php
 */

class Az24Parser {
    private $baseUrl = 'https://az24.vn';
    private $verbose = true; // Set to false to disable debug output
    
    /**
     * Set verbose mode (for API calls, set to false)
     */
    public function setVerbose($verbose) {
        $this->verbose = $verbose;
    }
    
    /**
     * Log message (only if verbose is enabled)
     */
    private function log($message) {
        if ($this->verbose) {
            echo $message;
        }
    }
    
    /**
     * Fetch daily results for a region and date
     */
    public function fetchDaily($region, DateTime $date) {
        $slug = $this->getSlug($region);
        $dateStr = $date->format('d-m-Y');
        $url = "{$this->baseUrl}/{$slug}-{$dateStr}.html";
        
        $this->log("📡 Fetching: $url\n");
        
        // Fetch HTML
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; KQXSBot/1.0)',
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error $httpCode");
        }
        
        if (empty($html) || strlen($html) < 100) {
            throw new Exception("Empty or too short response");
        }
        
        // Parse HTML
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $payloads = [];
        
        if ($region === 'mb') {
            // MB: 1 đài duy nhất / ngày
            // Try multiple selectors
            $tables = $xp->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' kqmb ')]");
            if ($tables->length === 0) {
                // Try alternative: table with class containing kqmb
                $tables = $xp->query("//table[contains(@class, 'kqmb')]");
            }
            if ($tables->length === 0) {
                // Try: any table
                $tables = $xp->query("//table");
                $this->log("⚠️ Using fallback: found {$tables->length} table(s), checking all...\n");
            }
            
            $this->log("📊 Found {$tables->length} table(s) for MB\n");
            foreach ($tables as $idx => $table) {
                $classes = $table->getAttribute('class');
                $this->log("   Table {$idx}: class='{$classes}'\n");
                $p = $this->parseBacTable($xp, $table, $date);
                if ($p) {
                    $this->log("   ✅ Parsed successfully!\n");
                    $payloads[] = $p;
                } else {
                    $this->log("   ⚠️ Parse returned null (no DB prize?)\n");
                }
            }
        } else {
            // MN/MT: có thể 2/3/4 đài
            // Find all tables with colgiai class (avoid duplicates)
            $allTables = $xp->query("//table[contains(@class, 'colgiai')]");
            $tables = [];
            $seenTableIds = [];
            
            $this->log("📊 Found {$allTables->length} table(s) with 'colgiai'\n");
            foreach ($allTables as $tbl) {
                $classes = $tbl->getAttribute('class');
                // Use table's XPath as unique identifier to avoid duplicates
                $tableId = $tbl->getNodePath();
                
                if (!isset($seenTableIds[$tableId])) {
                    $seenTableIds[$tableId] = true;
                    $tables[] = $tbl;
                    $this->log("   Table class: '{$classes}'\n");
                } else {
                    $this->log("   ⚠️ Skipping duplicate table: '{$classes}'\n");
                }
            }
            
            if (empty($tables)) {
                // Last resort: try all tables
                $allTables = $xp->query("//table");
                $this->log("⚠️ Last resort: found {$allTables->length} table(s), checking all...\n");
                foreach ($allTables as $tbl) {
                    $classes = $tbl->getAttribute('class');
                    $tableId = $tbl->getNodePath();
                    if (!isset($seenTableIds[$tableId])) {
                        $seenTableIds[$tableId] = true;
                        $tables[] = $tbl;
                        $this->log("   Table class: '{$classes}'\n");
                    }
                }
            }
            
            foreach ($tables as $tbl) {
                $results = $this->parseMultiStationTable($xp, $tbl, $date, $region);
                $this->log("📊 Parsed " . count($results) . " station(s) from table\n");
                $payloads = array_merge($payloads, $results);
            }
        }
        
        $this->log("📊 Total payloads before deduplication: " . count($payloads) . "\n");
        
        // Deduplicate by date + province (keep last one)
        $uniquePayloads = [];
        foreach ($payloads as $payload) {
            $key = $payload['draw_date'] . '|' . $payload['station_code'];
            if (isset($uniquePayloads[$key])) {
                $this->log("   ⚠️ Duplicate found for key: $key (overwriting)\n");
            }
            $uniquePayloads[$key] = $payload;
        }
        
        $this->log("📊 Total payloads after deduplication: " . count($uniquePayloads) . "\n");
        $stations = array_unique(array_map(function($p) {
            return $p['station_code'];
        }, $uniquePayloads));
        $this->log("📊 Unique stations after deduplication: " . implode(', ', $stations) . "\n");
        
        return array_values($uniquePayloads);
    }
    
    /**
     * Get slug from region
     */
    private function getSlug($region) {
        switch (strtolower($region)) {
            case 'nam':
            case 'mn':
                return 'xsmn';
            case 'trung':
            case 'mt':
                return 'xsmt';
            case 'bac':
            case 'mb':
                return 'xsmb';
            default:
                return 'xsmn';
        }
    }
    
    /**
     * Parse Miền Bắc table
     */
    private function parseBacTable(DOMXPath $xp, DOMElement $table, DateTime $date) {
        $prizes = [
            'g8' => [], 'g7' => [], 'g6' => [], 'g5' => [],
            'g4' => [], 'g3' => [], 'g2' => [], 'g1' => [], 'db' => [],
        ];
        
        $this->log("   🔍 Parsing MB table...\n");
        
        // Hàng ĐB
        $dbRows = $xp->query(".//tr[contains(@class, 'db')]", $table);
        if ($dbRows->length === 0) {
            // Try alternative: row containing "ĐB" or "DB" text
            $allRows = $xp->query(".//tr", $table);
            foreach ($allRows as $tr) {
                $text = strtolower($tr->textContent);
                if (strpos($text, 'đb') !== false || strpos($text, 'db') !== false) {
                    $td = $xp->query(".//td[last()]", $tr)->item(0);
                    if ($td) {
                        $nums = $this->extractNumbersFromCell($td);
                        if (!empty($nums)) {
                            $prizes['db'] = array_merge($prizes['db'], $nums);
                            $this->log("     ✅ Found DB: " . count($nums) . " number(s)\n");
                        }
                    }
                }
            }
        } else {
            $this->log("     📊 Found {$dbRows->length} DB row(s)\n");
            foreach ($dbRows as $tr) {
                $td = $xp->query(".//td[last()]", $tr)->item(0);
                if ($td) {
                    $nums = $this->extractNumbersFromCell($td);
                    $prizes['db'] = array_merge($prizes['db'], $nums);
                    $this->log("     ✅ Found DB: " . count($nums) . " number(s)\n");
                }
            }
        }
        
        // Các hàng G1..G7,G4,G3,G2...
        $rows = $xp->query(".//tr[not(contains(@class, 'db'))]", $table);
        if ($rows->length === 0) {
            $rows = $xp->query(".//tr", $table);
        }
        $this->log("     📊 Found {$rows->length} prize row(s)\n");
        
        $prizeCount = 0;
        foreach ($rows as $tr) {
            $labelTd = $xp->query(".//td[1]", $tr)->item(0);
            $numsTd = $xp->query(".//td[last()]", $tr)->item(0);
            if (!$labelTd || !$numsTd) continue;
            
            $labelText = $this->cleanText($labelTd->textContent);
            $label = $this->labelKey($labelText);
            if (!$label) {
                // Skip if not a valid prize label
                continue;
            }
            
            $nums = $this->extractNumbersFromCell($numsTd);
            if (!empty($nums)) {
                $prizes[$label] = array_merge($prizes[$label], $nums);
                $this->log("     ✅ Found {$label}: " . count($nums) . " number(s)\n");
                $prizeCount++;
            }
        }
        
        $this->log("     📊 Processed {$prizeCount} prize row(s)\n");
        
        // Nếu không có DB => bỏ
        if (empty($prizes['db'])) {
            $this->log("   ⚠️ No DB prize found, returning null\n");
            return null;
        }
        
        return [
            'station_code' => 'mb',
            'station' => 'mien bac',
            'region' => 'mb',
            'draw_date' => $date->format('Y-m-d'),
            'prizes' => $prizes,
        ];
    }
    
    /**
     * Parse multi-station table (MN/MT)
     */
    private function parseMultiStationTable(DOMXPath $xp, DOMElement $table, DateTime $date, $region) {
        $result = [];
        
        // Header row: danh sách đài (th) sau cột đầu tiên
        $headerTr = $xp->query(".//tr[contains(@class, 'gr-yellow')]", $table)->item(0);
        if (!$headerTr) {
            // Try alternative: first row with th elements
            $headerTr = $xp->query(".//tr[th]", $table)->item(0);
            if (!$headerTr) {
                $this->log("   ⚠️ No header row found in table\n");
                return $result;
            }
        }
        
        $ths = $xp->query(".//th[not(contains(@class, 'first'))]", $headerTr);
        if ($ths->length === 0) {
            // Try: all th elements
            $ths = $xp->query(".//th", $headerTr);
            if ($ths->length > 0) {
                // Skip first th (label column)
                $ths = $xp->query(".//th[position() > 1]", $headerTr);
            }
        }
        if ($ths->length === 0) {
            $this->log("   ⚠️ No station columns found in header\n");
            return $result;
        }
        
        $this->log("   📊 Found {$ths->length} station(s) in header\n");
        
        $stations = [];
        
        foreach ($ths as $i => $th) {
            $a = $xp->query(".//a", $th)->item(0);
            $name = $a ? $this->cleanText($a->textContent) : $this->cleanText($th->textContent);
            $href = $a ? $a->getAttribute('href') : '';
            $code = $this->codeFromHref($href) ?: $this->codeFromName($name);
            
            $this->log("     Station {$i}: code='{$code}', name='{$name}'\n");
            
            $stations[$i] = [
                'code' => $code,
                'name' => $this->normalizeStationName($name),
                'prizes' => [
                    'g8' => [], 'g7' => [], 'g6' => [], 'g5' => [],
                    'g4' => [], 'g3' => [], 'g2' => [], 'g1' => [], 'db' => [],
                ],
            ];
        }
        
        // Duyệt từng hàng giải
        $rows = $xp->query(".//tr[not(contains(@class, 'gr-yellow'))]", $table);
        if ($rows->length === 0) {
            // Try: all rows except header
            $rows = $xp->query(".//tr[position() > 1]", $table);
        }
        
        $this->log("   📊 Found {$rows->length} prize row(s)\n");
        $prizeCount = 0;
        
        foreach ($rows as $tr) {
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length < (1 + count($stations))) {
                continue;
            }
            
            $labelTd = $tds->item(0);
            $rawLabelText = $labelTd->textContent;
            $labelText = $this->cleanText($rawLabelText);
            $label = $this->labelKey($labelText);
            
            // Debug: show raw text for troubleshooting
            if (!$label) {
                $rawHex = bin2hex($rawLabelText);
                $this->log("     ⚠️ Skipping row with label: '{$labelText}' (raw: '{$rawLabelText}', hex: {$rawHex})\n");
                continue;
            }
            
            $this->log("     📝 Processing prize: {$label} (from '{$labelText}')\n");
            $prizeCount++;
            
            // đi theo từng đài
            foreach ($stations as $i => &$st) {
                if ($i + 1 >= $tds->length) {
                    $this->log("       ⚠️ Not enough columns for station index {$i}\n");
                    continue;
                }
                $td = $tds->item($i + 1); // do cột 0 là nhãn
                $nums = $this->extractNumbersFromCell($td);
                if (empty($nums)) {
                    $this->log("       Station {$st['code']}: no numbers found\n");
                    continue;
                }
                
                $this->log("       Station {$st['code']}: " . count($nums) . " number(s)\n");
                
                // append theo label
                $st['prizes'][$label] = array_merge($st['prizes'][$label], $nums);
            }
            unset($st); // Important: unset reference after loop
        }
        
        $this->log("   📊 Processed {$prizeCount} prize row(s)\n");
        
        // Trả payload theo từng đài
        $this->log("   📊 Checking " . count($stations) . " station(s) before adding to result\n");
        foreach ($stations as $idx => $st) {
            // Debug: show station info
            $dbCount = isset($st['prizes']['db']) ? count($st['prizes']['db']) : 0;
            $this->log("   🔍 Station {$idx}: code='{$st['code']}', name='{$st['name']}', DB count={$dbCount}\n");
            
            // Nếu không có DB, coi như invalid (bỏ)
            if (empty($st['prizes']['db'])) {
                $this->log("   ⚠️ Skipping station {$st['code']} - no DB prize\n");
                continue;
            }
            
            $this->log("   ✅ Adding station: {$st['code']} ({$st['name']})\n");
            $prizeSummary = [];
            foreach ($st['prizes'] as $key => $nums) {
                if (!empty($nums)) {
                    $prizeSummary[] = "$key:" . count($nums);
                }
            }
            $this->log("      Prizes: " . implode(', ', $prizeSummary) . "\n");
            
            $result[] = [
                'station_code' => $st['code'],
                'station' => $st['name'],
                'region' => $region,
                'draw_date' => $date->format('Y-m-d'),
                'prizes' => $st['prizes'],
            ];
        }
        
        $this->log("   📊 Total stations added to result: " . count($result) . "\n");
        
        return $result;
    }
    
    /**
     * Extract numbers from cell (2-6 digits, keep leading zeros)
     */
    private function extractNumbersFromCell(DOMElement $td) {
        $texts = [];
        $walker = function(DOMNode $n) use (&$texts, &$walker) {
            if ($n->nodeType === XML_TEXT_NODE) {
                $texts[] = $n->nodeValue;
            }
            if ($n->hasChildNodes()) {
                foreach ($n->childNodes as $c) $walker($c);
            }
        };
        $walker($td);
        
        $nums = [];
        foreach ($texts as $t) {
            $t = $this->cleanText($t);
            if ($t === '') continue;
            // Lấy chuỗi số 2..6 chữ số (giữ leading zero)
            if (preg_match_all('/\d{2,6}/u', $t, $m)) {
                foreach ($m[0] as $v) $nums[] = $v;
            }
        }
        return $nums;
    }
    
    /**
     * Clean text
     */
    private function cleanText($text) {
        if ($text === null) return '';
        // Remove zero-width joiner
        $text = preg_replace('/\x{200D}/u', '', $text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalize Unicode (NFD to NFC) if intl extension is available
        if (function_exists('normalizer_normalize')) {
            $text = normalizer_normalize($text, Normalizer::FORM_C);
        }
        // Remove extra whitespace
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        return $text;
    }
    
    /**
     * Get label key from text (db, g1, g2, etc.)
     */
    private function labelKey($label) {
        // Normalize Vietnamese characters: đ -> d, Đ -> d
        $l = mb_strtolower($label, 'UTF-8');
        
        // Remove all spaces and special characters for matching
        $lNoSpace = preg_replace('/\s+/u', '', $l);
        // Replace đ/Đ with d
        $lNoSpace = str_replace(['đ', 'Đ'], 'd', $lNoSpace);
        
        // Check for DB/ĐB first (most important) - many variations
        $dbPatterns = [
            'db', 'gdb', 'ddb', 'dacbiet', 'dacbiet', 'dac biet',
            'giảiđặcbiệt', 'giaidacbiet', 'giải đặc biệt',
            'đặcbiệt', 'dacbiet', 'đb', 'db'
        ];
        
        foreach ($dbPatterns as $pattern) {
            $patternNormalized = str_replace(['đ', 'Đ', ' '], ['d', 'd', ''], mb_strtolower($pattern, 'UTF-8'));
            if ($lNoSpace === $patternNormalized || strpos($lNoSpace, $patternNormalized) !== false) {
                return 'db';
            }
        }
        
        // Also check if contains "db" or "dacbiet" anywhere
        if (strpos($lNoSpace, 'db') !== false || strpos($lNoSpace, 'dacbiet') !== false) {
            return 'db';
        }
        
        // Check for G1-G8
        if ($lNoSpace === 'g8' || $lNoSpace === 'giai8' || $lNoSpace === 'giải8') return 'g8';
        if ($lNoSpace === 'g7' || $lNoSpace === 'giai7' || $lNoSpace === 'giải7') return 'g7';
        if ($lNoSpace === 'g6' || $lNoSpace === 'giai6' || $lNoSpace === 'giải6') return 'g6';
        if ($lNoSpace === 'g5' || $lNoSpace === 'giai5' || $lNoSpace === 'giải5') return 'g5';
        if ($lNoSpace === 'g4' || $lNoSpace === 'giai4' || $lNoSpace === 'giải4') return 'g4';
        if ($lNoSpace === 'g3' || $lNoSpace === 'giai3' || $lNoSpace === 'giải3') return 'g3';
        if ($lNoSpace === 'g2' || $lNoSpace === 'giai2' || $lNoSpace === 'giải2') return 'g2';
        if ($lNoSpace === 'g1' || $lNoSpace === 'giai1' || $lNoSpace === 'giải1') return 'g1';
        
        // Try matching patterns like "giải 1", "giải 2", etc.
        if (preg_match('/giai(\d+)/u', $lNoSpace, $m)) {
            $num = (int)$m[1];
            if ($num >= 1 && $num <= 8) {
                return 'g' . $num;
            }
        }
        
        return null;
    }
    
    /**
     * Extract code from href
     */
    private function codeFromHref($href) {
        if (preg_match('#/xs([a-z]{2,4})\b#i', $href, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }
    
    /**
     * Extract code from name (fallback)
     */
    private function codeFromName($name) {
        $n = strtolower($name);
        if (strpos($n, 'tiền giang') !== false || strpos($n, 'tien giang') !== false) return 'tg';
        if (strpos($n, 'kiên giang') !== false || strpos($n, 'kien giang') !== false) return 'kg';
        if (strpos($n, 'đà lạt') !== false || strpos($n, 'da lat') !== false) return 'dl';
        if (strpos($n, 'tp hcm') !== false) return 'tp';
        if (strpos($n, 'long an') !== false) return 'la';
        if (strpos($n, 'bình phước') !== false || strpos($n, 'binh phuoc') !== false) return 'bp';
        if (strpos($n, 'hậu giang') !== false || strpos($n, 'hau giang') !== false) return 'hg';
        if (strpos($n, 'huế') !== false || strpos($n, 'hue') !== false) return 'tth';
        if (strpos($n, 'phú yên') !== false || strpos($n, 'phu yen') !== false) return 'py';
        if (strpos($n, 'hà nội') !== false || strpos($n, 'ha noi') !== false) return 'hn';
        if (strpos($n, 'đồng tháp') !== false || strpos($n, 'dong thap') !== false) return 'dt';
        if (strpos($n, 'vĩnh long') !== false || strpos($n, 'vinh long') !== false) return 'vl';
        if (strpos($n, 'trà vinh') !== false || strpos($n, 'tra vinh') !== false) return 'tv';
        if (strpos($n, 'ninh thuận') !== false || strpos($n, 'ninh thuan') !== false) return 'nt';
        if (strpos($n, 'bến tre') !== false || strpos($n, 'ben tre') !== false) return 'bt';
        if (strpos($n, 'bình thuận') !== false || strpos($n, 'binh thuan') !== false) return 'bt';
        if (strpos($n, 'bình định') !== false || strpos($n, 'binh dinh') !== false) return 'bd';
        if (strpos($n, 'bình dương') !== false || strpos($n, 'binh duong') !== false) return 'bd';
        if (strpos($n, 'bạc liêu') !== false || strpos($n, 'bac lieu') !== false) return 'bl';
        if (strpos($n, 'cà mau') !== false || strpos($n, 'ca mau') !== false) return 'cm';
        if (strpos($n, 'cần thơ') !== false || strpos($n, 'can tho') !== false) return 'ct';
        if (strpos($n, 'đà nẵng') !== false || strpos($n, 'da nang') !== false) return 'dna';
        if (strpos($n, 'đắc lắc') !== false || strpos($n, 'dac lac') !== false) return 'dl';
        if (strpos($n, 'đồng nai') !== false || strpos($n, 'dong nai') !== false) return 'dn';
        if (strpos($n, 'hậu giang') !== false || strpos($n, 'hau giang') !== false) return 'hg';
        if (strpos($n, 'khánh hòa') !== false || strpos($n, 'khanh hoa') !== false) return 'kh';
        if (strpos($n, 'kon tum') !== false) return 'kt';
        if (strpos($n, 'lai châu') !== false || strpos($n, 'lai chau') !== false) return 'lc';

        return preg_replace('/[^a-z0-9]/', '', $n);
    }
    
    /**
     * Normalize station name
     */
    private function normalizeStationName($name) {
        $n = strtolower($this->cleanText($name));
        if ($n === 'tp hcm' || $n === 'tp hcm ' || strpos($n, 'tp hồ chí minh') !== false || strpos($n, 'tp ho chi minh') !== false) {
            return 'tp.hcm';
        }
        return $n;
    }
}

