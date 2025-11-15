<?php
/**
 * Simple Supabase client for PHP
 * Uses cURL to interact with Supabase REST API
 */

class SupabaseClient {
    private $url;
    private $key;
    private $serviceKey;
    
    public function __construct($useServiceKey = true) {
        $this->url = rtrim(SUPABASE_URL, '/');
        $this->key = SUPABASE_KEY;
        $this->serviceKey = SUPABASE_SERVICE_KEY ?? SUPABASE_KEY;
        
        // For backend operations (insert/update), use service key to bypass RLS
        if ($useServiceKey && defined('SUPABASE_SERVICE_KEY') && SUPABASE_SERVICE_KEY !== 'YOUR_SUPABASE_SERVICE_KEY') {
            $this->key = $this->serviceKey;
        }
    }
    
    /**
     * Make HTTP request to Supabase
     */
    private function request($method, $table, $data = null, $filters = null, $id = null) {
        $url = $this->url . '/rest/v1/' . $table;
        
        // Add filters to URL
        if ($filters) {
            $queryParams = [];
            foreach ($filters as $key => $value) {
                $queryParams[] = $key . '=eq.' . urlencode($value);
            }
            if (!empty($queryParams)) {
                $url .= '?' . implode('&', $queryParams);
            }
        }
        
        // Add ID to URL for update/delete
        if ($id !== null) {
            $url .= ($filters ? '&' : '?') . 'id=eq.' . $id;
        }
        
        $ch = curl_init($url);
        
        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification for local/dev
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
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: $response");
        }
        
        return json_decode($response, true) ?: [];
    }
    
    /**
     * Select records
     */
    public function select($table, $filters = null) {
        return $this->request('GET', $table, null, $filters);
    }
    
    /**
     * Insert record
     */
    public function insert($table, $data) {
        return $this->request('POST', $table, $data);
    }
    
    /**
     * Update record(s) by filters
     */
    public function update($table, $data, $filters = null) {
        return $this->request('PATCH', $table, $data, $filters);
    }
    
    /**
     * Delete record
     */
    public function delete($table, $id) {
        return $this->request('DELETE', $table, null, null, $id);
    }
}

