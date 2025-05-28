<?php

/**
 * PHP Version and Environment Checker
 * Run this to check your PHP setup in different contexts
 */

echo "=== PHP VERSION CHECK ===\n\n";

echo "Running PHP Version Information:\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- PHP Major Version: " . PHP_MAJOR_VERSION . "\n";
echo "- PHP Minor Version: " . PHP_MINOR_VERSION . "\n";
echo "- PHP Release Version: " . PHP_RELEASE_VERSION . "\n";
echo "- PHP Binary: " . PHP_BINARY . "\n";
echo "- SAPI: " . PHP_SAPI . "\n\n";

echo "Server Environment:\n";
echo "- Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'N/A (CLI)' . "\n";
echo "- Server Software: " . $_SERVER['SERVER_SOFTWARE'] ?? 'N/A (CLI)' . "\n";
echo "- HTTP Host: " . $_SERVER['HTTP_HOST'] ?? 'N/A (CLI)' . "\n\n";

echo "System Information:\n";
echo "- Operating System: " . PHP_OS . "\n";
echo "- Architecture: " . php_uname('m') . "\n";
echo "- Node Name: " . php_uname('n') . "\n";
echo "- Current User: " . get_current_user() . "\n";
echo "- Current Working Directory: " . getcwd() . "\n\n";

echo "Important PHP Extensions:\n";
$requiredExtensions = ['curl', 'fileinfo', 'json', 'mbstring', 'xml'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "- " . $ext . ": " . ($loaded ? "✓ Loaded" : "✗ Missing") . "\n";
}

echo "\nMemory Information:\n";
echo "- Memory Limit: " . ini_get('memory_limit') . "\n";
echo "- Current Memory Usage: " . formatBytes(memory_get_usage()) . "\n";
echo "- Peak Memory Usage: " . formatBytes(memory_get_peak_usage()) . "\n";

echo "\nFile Upload Settings:\n";
echo "- Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- Post Max Size: " . ini_get('post_max_size') . "\n";
echo "- Max File Uploads: " . ini_get('max_file_uploads') . "\n";
echo "- Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";

echo "\nCommand Line PHP Check:\n";
$cliPhpOutput = null;
$cliPhpReturn = null;
exec('php --version 2>&1', $cliPhpOutput, $cliPhpReturn);

if ($cliPhpReturn === 0 && !empty($cliPhpOutput)) {
    echo "✓ CLI PHP available:\n";
    foreach ($cliPhpOutput as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ CLI PHP not available or different version\n";
}

echo "\nComposer Check:\n";
$composerOutput = null;
$composerReturn = null;
exec('composer --version 2>&1', $composerOutput, $composerReturn);

if ($composerReturn === 0 && !empty($composerOutput)) {
    echo "✓ Composer available:\n";
    foreach ($composerOutput as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ Composer not available in PATH\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

echo "\n=== PHP CHECK COMPLETE ===\n";