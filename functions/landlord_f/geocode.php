<?php
// landlord_f/geocode.php - Geocoding Service (Standalone)
// This file should NOT include config.php or start sessions

// CRITICAL: Only execute if this file is directly accessed
// This prevents it from running when config.php or other files include it
if (basename($_SERVER['PHP_SELF']) !== 'geocode.php') {
    return; // Exit silently if included by another file
}

// Set log file
$logFile = __DIR__ . '/geocode_debug.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog('=== GEOCODE REQUEST START ===');
writeLog('REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set'));
writeLog('QUERY_STRING: ' . ($_SERVER['QUERY_STRING'] ?? 'not set'));

// Check for proxy environment variables
writeLog('HTTP_PROXY env: ' . (getenv('HTTP_PROXY') ?: 'not set'));
writeLog('HTTPS_PROXY env: ' . (getenv('HTTPS_PROXY') ?: 'not set'));

// Unset any proxy environment variables to prevent issues
putenv('HTTP_PROXY=');
putenv('HTTPS_PROXY=');
putenv('http_proxy=');
putenv('https_proxy=');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Get search query from URL parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

writeLog('Query from $_GET: [' . $query . ']');

if (empty($query)) {
    writeLog('ERROR: Empty query');
    echo json_encode([
        'success' => false,
        'message' => 'Search query is required'
    ]);
    exit;
}

// Enhance query with Philippines context
$hasPhilippines = stripos($query, 'philippines') !== false || 
                  stripos($query, 'manila') !== false;

$searchQuery = $hasPhilippines ? $query : $query . ', Philippines';
writeLog('Search query: [' . $searchQuery . ']');

// Build Nominatim URL - try without strict country restriction first
$params = [
    'q' => $searchQuery,
    'format' => 'json',
    'addressdetails' => '1',
    'limit' => '5'
];

$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);
writeLog('URL: ' . $url);

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'RentConnect/1.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_PROXY, ''); // Explicitly disable proxy
curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false); // Disable proxy tunnel

// Execute request
writeLog('Executing cURL...');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

writeLog('HTTP Code: ' . $httpCode);
writeLog('cURL Error: ' . ($curlError ?: 'none'));

if ($curlError) {
    writeLog('CURL ERROR: ' . $curlError);
    curl_close($ch);
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $curlError
    ]);
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    writeLog('Bad HTTP code: ' . $httpCode);
    echo json_encode([
        'success' => false,
        'message' => 'Service returned error: ' . $httpCode
    ]);
    exit;
}

// Parse response
$data = json_decode($response, true);

if ($data === null) {
    writeLog('JSON parse failed: ' . json_last_error_msg());
    echo json_encode([
        'success' => false,
        'message' => 'Invalid response format'
    ]);
    exit;
}

writeLog('Results found: ' . count($data));

if (empty($data)) {
    // Try more specific search for common Philippine locations
    $alternateQueries = [
        $query . ', Metro Manila, Philippines',
        $query . ', Philippines'
    ];
    
    foreach ($alternateQueries as $altQuery) {
        writeLog('Trying alternate: ' . $altQuery);
        
        $altUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $altQuery,
            'format' => 'json',
            'limit' => '3'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $altUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RentConnect/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_PROXY, ''); // Disable proxy
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        
        $altResponse = curl_exec($ch);
        $altHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($altHttpCode === 200) {
            $altData = json_decode($altResponse, true);
            if (!empty($altData)) {
                writeLog('Alternate found: ' . count($altData) . ' results');
                $data = $altData;
                break;
            }
        }
    }
}

// Return results
if (empty($data)) {
    writeLog('FINAL: No results');
    echo json_encode([
        'success' => false,
        'message' => 'Location not found. Try: "Makati City" or "Bonifacio Global City, Taguig"'
    ]);
} else {
    writeLog('SUCCESS: ' . count($data) . ' results');
    if (isset($data[0]['display_name'])) {
        writeLog('Top result: ' . $data[0]['display_name']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Found ' . count($data) . ' location(s)',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

writeLog('=== GEOCODE REQUEST END ===');
?>