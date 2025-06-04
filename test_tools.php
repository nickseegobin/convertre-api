<?php

/**
 * Tools Diagnostic Script
 * Run this script to debug tool detection and configuration
 * 
 * Usage: php tools-diagnostic.php
 */

// Include the tools configuration
$toolsConfig = include __DIR__ . '/config/tools.php';

echo "=== CONVERTRE API - TOOLS DIAGNOSTIC ===\n\n";

// Display platform information
echo "Platform Information:\n";
echo "- PHP OS: " . PHP_OS . "\n";
echo "- Is Windows: " . (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Yes' : 'No') . "\n";
echo "- Current User: " . get_current_user() . "\n";
echo "- PHP Version: " . PHP_VERSION . "\n\n";

// Display detection status
if (isset($toolsConfig['detection_status'])) {
    echo "Detection Status:\n";
    foreach ($toolsConfig['detection_status'] as $key => $value) {
        echo "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . 
             (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "\n";
    }
    echo "\n";
}

// Test ImageMagick
echo "=== IMAGEMAGICK TESTING ===\n";
$imageMagickConfig = $toolsConfig['imagemagick'];

echo "Configuration:\n";
echo "- Binary Path: " . $imageMagickConfig['binary_path'] . "\n";
echo "- Version Check Command: " . $imageMagickConfig['version_check'] . "\n\n";

// Test if ImageMagick file exists
if (file_exists($imageMagickConfig['binary_path'])) {
    echo "✓ ImageMagick binary file exists\n";
} else {
    echo "✗ ImageMagick binary file NOT found\n";
}

// Test if ImageMagick is executable
if (is_executable($imageMagickConfig['binary_path'])) {
    echo "✓ ImageMagick binary is executable\n";
} else {
    echo "✗ ImageMagick binary is NOT executable\n";
}

// Test ImageMagick version
echo "\nTesting ImageMagick version...\n";
$versionOutput = null;
$versionReturn = null;
$versionCommand = $imageMagickConfig['version_check'];
exec($versionCommand . ' 2>&1', $versionOutput, $versionReturn);

if ($versionReturn === 0 && !empty($versionOutput)) {
    echo "✓ ImageMagick version check successful:\n";
    foreach ($versionOutput as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ ImageMagick version check failed\n";
    echo "Return code: " . $versionReturn . "\n";
    if (!empty($versionOutput)) {
        echo "Output:\n";
        foreach ($versionOutput as $line) {
            echo "  " . $line . "\n";
        }
    }
}

// Test simple ImageMagick conversion
echo "\nTesting ImageMagick functionality...\n";
$tempDir = sys_get_temp_dir();
$testCommand = $imageMagickConfig['binary_path'] . ' -size 100x100 xc:red "' . $tempDir . DIRECTORY_SEPARATOR . 'test_image.jpg"';
$testOutput = null;
$testReturn = null;
exec($testCommand . ' 2>&1', $testOutput, $testReturn);

if ($testReturn === 0 && file_exists($tempDir . DIRECTORY_SEPARATOR . 'test_image.jpg')) {
    echo "✓ ImageMagick basic functionality test passed\n";
    unlink($tempDir . DIRECTORY_SEPARATOR . 'test_image.jpg'); // Clean up
} else {
    echo "✗ ImageMagick basic functionality test failed\n";
    echo "Command: " . $testCommand . "\n";
    echo "Return code: " . $testReturn . "\n";
    if (!empty($testOutput)) {
        echo "Output:\n";
        foreach ($testOutput as $line) {
            echo "  " . $line . "\n";
        }
    }
}

echo "\n=== LIBREOFFICE TESTING ===\n";
$libreOfficeConfig = $toolsConfig['libreoffice'];

echo "Configuration:\n";
echo "- Binary Path: " . $libreOfficeConfig['binary_path'] . "\n";
echo "- Version Check Command: " . $libreOfficeConfig['version_check'] . "\n\n";

// Test if LibreOffice file exists
if (file_exists($libreOfficeConfig['binary_path'])) {
    echo "✓ LibreOffice binary file exists\n";
} else {
    echo "✗ LibreOffice binary file NOT found\n";
}

// Test if LibreOffice is executable
if (is_executable($libreOfficeConfig['binary_path'])) {
    echo "✓ LibreOffice binary is executable\n";
} else {
    echo "✗ LibreOffice binary is NOT executable\n";
}

// Test LibreOffice version
echo "\nTesting LibreOffice version...\n";
$loVersionOutput = null;
$loVersionReturn = null;
exec($libreOfficeConfig['version_check'] . ' 2>&1', $loVersionOutput, $loVersionReturn);

if ($loVersionReturn === 0 && !empty($loVersionOutput)) {
    echo "✓ LibreOffice version check successful:\n";
    foreach ($loVersionOutput as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ LibreOffice version check failed\n";
    echo "Return code: " . $loVersionReturn . "\n";
    if (!empty($loVersionOutput)) {
        echo "Output:\n";
        foreach ($loVersionOutput as $line) {
            echo "  " . $line . "\n";
        }
    }
}

// Test LibreOffice headless mode
echo "\nTesting LibreOffice headless mode...\n";
$headlessCommand = $libreOfficeConfig['binary_path'] . ' --headless --help';
$headlessOutput = null;
$headlessReturn = null;
exec($headlessCommand . ' 2>&1', $headlessOutput, $headlessReturn);

if ($headlessReturn === 0) {
    echo "✓ LibreOffice headless mode accessible\n";
} else {
    echo "✗ LibreOffice headless mode test failed\n";
    echo "Command: " . $headlessCommand . "\n";
    echo "Return code: " . $headlessReturn . "\n";
}

echo "\n=== ENVIRONMENT VARIABLES ===\n";
echo "PATH: " . getenv('PATH') . "\n\n";

// Check if tools are in PATH
echo "=== PATH AVAILABILITY ===\n";

// Check magick in PATH
$pathMagickOutput = null;
$pathMagickReturn = null;
exec('magick -version 2>&1', $pathMagickOutput, $pathMagickReturn);

if ($pathMagickReturn === 0) {
    echo "✓ 'magick' command available in PATH\n";
} else {
    echo "✗ 'magick' command NOT available in PATH\n";
}

// Check convert in PATH
$pathConvertOutput = null;
$pathConvertReturn = null;
exec('convert -version 2>&1', $pathConvertOutput, $pathConvertReturn);

if ($pathConvertReturn === 0) {
    echo "✓ 'convert' command available in PATH\n";
} else {
    echo "✗ 'convert' command NOT available in PATH\n";
}

// Check soffice in PATH
$pathSofficeOutput = null;
$pathSofficeReturn = null;
exec('soffice --version 2>&1', $pathSofficeOutput, $pathSofficeReturn);

if ($pathSofficeReturn === 0) {
    echo "✓ 'soffice' command available in PATH\n";
} else {
    echo "✗ 'soffice' command NOT available in PATH\n";
}

echo "\n=== RECOMMENDATIONS ===\n";

$recommendations = [];

// ImageMagick recommendations
if (!file_exists($imageMagickConfig['binary_path'])) {
    $recommendations[] = "ImageMagick not found. Install from: https://imagemagick.org/script/download.php#windows";
    $recommendations[] = "Or install via Chocolatey: choco install imagemagick";
}

// LibreOffice recommendations
if (!file_exists($libreOfficeConfig['binary_path'])) {
    $recommendations[] = "LibreOffice not found. Install from: https://www.libreoffice.org/download/download/";
    $recommendations[] = "Or install via Chocolatey: choco install libreoffice";
}

// PATH recommendations
if ($pathMagickReturn !== 0 && $pathConvertReturn !== 0) {
    $recommendations[] = "Add ImageMagick to your system PATH for easier access";
}

if ($pathSofficeReturn !== 0) {
    $recommendations[] = "Add LibreOffice to your system PATH for easier access";
}

if (empty($recommendations)) {
    echo "✓ All tools appear to be configured correctly!\n";
} else {
    foreach ($recommendations as $rec) {
        echo "- " . $rec . "\n";
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";