<?php

/**
 * Web Server URL Finder
 * Helps identify the correct URL for your Convertre API
 */

echo "🔍 Finding your Convertre API web server URL...\n\n";

$possibleUrls = [
    'http://localhost/convertre-api/public',
    'http://localhost:8080/convertre-api/public',
    'http://localhost:80/convertre-api/public',
    'http://127.0.0.1/convertre-api/public',
    'http://127.0.0.1:8080/convertre-api/public',
    'http://localhost:8000/convertre-api/public'
];

$workingUrls = [];

foreach ($possibleUrls as $baseUrl) {
    $healthUrl = $baseUrl . '/health';
    
    echo "Testing: {$healthUrl} ... ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $healthUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ WORKING!\n";
            $workingUrls[] = $baseUrl;
        } else {
            echo "❌ Invalid response\n";
        }
    } elseif ($httpCode > 0) {
        echo "❌ HTTP {$httpCode}\n";
    } else {
        echo "❌ Connection failed: {$error}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if (empty($workingUrls)) {
    echo "❌ No working URLs found!\n\n";
    echo "Possible solutions:\n";
    echo "1. Start your web server (Apache/Nginx)\n";
    echo "2. Check if the API is accessible via browser\n";
    echo "3. Verify the correct port and path\n";
    echo "4. Test manually: curl http://localhost/convertre-api/public/health\n";
} else {
    echo "✅ Found working URL(s):\n\n";
    foreach ($workingUrls as $url) {
        echo "   {$url}\n";
    }
    echo "\nUpdate your test files to use one of these URLs.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Additional diagnostic info
echo "🔧 System Information:\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not detected') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";

?>