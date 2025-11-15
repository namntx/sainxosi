<?php
/**
 * Configuration file for Supabase connection
 * Update these values with your Supabase credentials
 */

// Supabase Configuration
define('SUPABASE_URL', 'https://lkjvijmcjurrwopkwhyo.supabase.co'); // e.g., https://xxxxx.supabase.co
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImxranZpam1janVycndvcGt3aHlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjI5MTcyMDUsImV4cCI6MjA3ODQ5MzIwNX0.OP_0dUlzl1DiZtDLdfXefLksacdIA83I9vFWfRxiSOo'); // Your Supabase anon/public key
define('SUPABASE_SERVICE_KEY', 'YOUR_SUPABASE_SERVICE_KEY'); // Optional: service role key for admin operations

// Firebase Configuration
define('FIREBASE_PROJECT_ID', 'xsne-dcb01');
define('FIREBASE_API_KEY', 'AIzaSyD_x7X-MvWzZA0wk8FE7kNA2SrnY7Se04c');
define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/firebase-service-account.json'); // Path to service account JSON file

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

