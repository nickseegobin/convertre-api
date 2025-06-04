<?php

/**
 * Quick Test Script for DOCX to PDF Conversion
 * Run this to test LibreOffice integration
 */

require_once __DIR__ . '/src/Utils/ConfigLoader.php';
require_once __DIR__ . '/src/Utils/Logger.php';
require_once __DIR__ . '/src/Exceptions/ConversionException.php';
require_once __DIR__ . '/src/Services/ConversionResult.php';
require_once __DIR__ . '/src/Services/AbstractConversionModule.php';
require_once __DIR__ . '/src/Services/Modules/DocxToPdfModule.php';

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Services\Modules\DocxToPdfModule;

// Initialize
ConfigLoader::init(__DIR__ . '/config');
Logger::init(__DIR__ . '/storage/logs');

echo "=== DOCX to PDF Conversion Test ===\n\n";

// Test LibreOffice availability
echo "1. Testing LibreOffice availability...\n";
$module = new DocxToPdfModule();

// Create a simple test DOCX file
echo "2. Creating test DOCX file...\n";
$testInput = __DIR__ . '/storage/test_document.docx';
$testOutput = __DIR__ . '/storage/test_document.pdf';

// Create minimal DOCX content (base64 encoded)
$docxData = createMinimalDocx();
file_put_contents($testInput, $docxData);
echo "   Test DOCX created: " . basename($testInput) . "\n";

// Test conversion
echo "3. Testing conversion...\n";
$result = $module->convert($testInput, $testOutput);

if ($result->isSuccess()) {
    echo "   ✅ CONVERSION SUCCESS!\n";
    echo "   📄 Output file: " . basename($testOutput) . "\n";
    echo "   ⏱️  Processing time: " . $result->getProcessingTime() . "s\n";
    echo "   📏 Output size: " . (file_exists($testOutput) ? filesize($testOutput) . " bytes" : "File not found") . "\n";
} else {
    echo "   ❌ CONVERSION FAILED!\n";
    echo "   Error: " . $result->getErrorMessage() . "\n";
}

// Cleanup
echo "4. Cleaning up...\n";
if (file_exists($testInput)) unlink($testInput);
if (file_exists($testOutput)) unlink($testOutput);

echo "\n=== Test Complete ===\n";

/**
 * Create minimal DOCX file for testing
 */
function createMinimalDocx(): string
{
    // Base64 encoded minimal DOCX file (contains "Test Document")
    return base64_decode('UEsDBBQABgAIAAAAIQC2gziS8gAAAOEBAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbJSRQU7DMBBF70jcwcpLqtqEOKHdRQWCxBILWNr1JJ5Ox3J8bSdN79+5a2vLNHlP7/8ff2f2rZE3iQKXWJbS34wUmaOHZoUu7xGbvhWeFrOx6kVLkdY3OgTgZ7VRB5HhMRPiDdxVYBc8G1D8KrJ5KsUl9xgWprmlLbz2vdCiZk3tZQRPn7dD/9IQ5LIK1OJBxF9Jc2DdDLe3qtKfA/2Qh58/X8z2zH9MDSJ/W3wgx+CwLhm+1gYfWJLHqSJFPGOzXOOAVn/gOUvj5JuJVBfOJEsYCEJBZkJ7kZBJ6Nn6b1RBYAV0GrBhWOAGJZVr4wgAAAAAA');
}