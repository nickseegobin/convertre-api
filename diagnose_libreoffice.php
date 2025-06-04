<?php

/**
 * LibreOffice Diagnostic Script
 * Debug why LibreOffice detection is failing
 */

echo "=== LIBREOFFICE DIAGNOSTIC ===\n\n";

// Test different possible paths
$possiblePaths = [
    'libreoffice',
    'soffice',
    '/usr/bin/libreoffice',
    '/usr/local/bin/libreoffice',
    '/Applications/LibreOffice.app/Contents/MacOS/soffice',
    '/opt/libreoffice/program/soffice'
];

echo "1. Testing LibreOffice command paths...\n";

foreach ($possiblePaths as $path) {
    echo "Testing: $path\n";
    
    $command = $path . ' --version 2>&1';
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    echo "  Return code: $returnCode\n";
    echo "  Output: " . implode(' ', $output) . "\n";
    
    if ($returnCode === 0) {
        echo "  ✓ WORKING PATH FOUND!\n";
        
        // Test headless mode
        $headlessCommand = $path . ' --headless --help 2>&1';
        $headlessOutput = [];
        $headlessReturn = 0;
        
        exec($headlessCommand, $headlessOutput, $headlessReturn);
        echo "  Headless test return code: $headlessReturn\n";
        echo "  Headless output: " . implode(' ', array_slice($headlessOutput, 0, 3)) . "\n";
        
        break;
    }
    echo "  ❌ Not working\n";
    echo "\n";
}

echo "\n2. Testing 'which' command...\n";
$whichOutput = [];
exec('which libreoffice 2>&1', $whichOutput, $whichReturn);
echo "which libreoffice: " . implode(' ', $whichOutput) . " (return: $whichReturn)\n";

$whichSoffice = [];
exec('which soffice 2>&1', $whichSoffice, $sofficeReturn);
echo "which soffice: " . implode(' ', $whichSoffice) . " (return: $sofficeReturn)\n";

echo "\n3. Testing PATH environment...\n";
echo "PATH: " . (getenv('PATH') ?: 'Not set') . "\n";

echo "\n4. Testing proc_open vs exec...\n";

function testWithProcOpen($command) {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    $process = proc_open($command, $descriptors, $pipes);
    
    if (!is_resource($process)) {
        return ['success' => false, 'error' => 'Failed to start process'];
    }
    
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $exitCode = proc_close($process);
    
    return [
        'success' => $exitCode === 0,
        'output' => $output,
        'error' => $error,
        'exit_code' => $exitCode
    ];
}

$procResult = testWithProcOpen('libreoffice --version');
echo "proc_open test:\n";
echo "  Success: " . ($procResult['success'] ? 'Yes' : 'No') . "\n";
echo "  Exit code: " . ($procResult['exit_code'] ?? 'Unknown') . "\n";
echo "  Output: " . substr($procResult['output'] ?? '', 0, 100) . "\n";
echo "  Error: " . substr($procResult['error'] ?? '', 0, 100) . "\n";

echo "\n5. macOS specific checks...\n";
if (PHP_OS === 'Darwin') {
    echo "Detected macOS system\n";
    
    // Check for LibreOffice.app
    if (is_dir('/Applications/LibreOffice.app')) {
        echo "✓ LibreOffice.app found in Applications\n";
        
        $macPath = '/Applications/LibreOffice.app/Contents/MacOS/soffice';
        if (file_exists($macPath)) {
            echo "✓ soffice binary found at: $macPath\n";
            
            $macTest = testWithProcOpen($macPath . ' --version');
            echo "macOS binary test:\n";
            echo "  Success: " . ($macTest['success'] ? 'Yes' : 'No') . "\n";
            echo "  Output: " . substr($macTest['output'] ?? '', 0, 100) . "\n";
        }
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "Look for the ✓ WORKING PATH FOUND! message above\n";
echo "If found, update your config/tools.php with the correct path\n";