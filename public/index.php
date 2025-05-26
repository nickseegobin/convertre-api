<?php

/**
 * Convertre API - Main Router - FIXED for Batch Conversion
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple includes - using __DIR__ for absolute paths
$basePath = __DIR__ . '/../src/';

require_once $basePath . 'Utils/ConfigLoader.php';
require_once $basePath . 'Utils/ResponseFormatter.php';
require_once $basePath . 'Utils/Logger.php';
require_once $basePath . 'Utils/FileHandler.php';
require_once $basePath . 'Services/AuthenticationService.php';
require_once $basePath . 'Services/FileValidationService.php';
require_once $basePath . 'Services/RateLimitService.php';
require_once $basePath . 'Services/RequestValidator.php';
require_once $basePath . 'Services/ConversionResult.php';
require_once $basePath . 'Services/AbstractConversionModule.php';
require_once $basePath . 'Services/ModuleFactory.php';
require_once $basePath . 'Controllers/AuthController.php';
require_once $basePath . 'Exceptions/ConversionException.php';
require_once $basePath . 'Exceptions/ValidationException.php';
require_once $basePath . 'Exceptions/AuthenticationException.php';

// Only include middleware if it exists
if (file_exists($basePath . 'Middleware/ValidationMiddleware.php')) {
    require_once $basePath . 'Middleware/ValidationMiddleware.php';
}

// Only include conversion modules if they exist
if (file_exists($basePath . 'Services/Modules/HeicToJpgModule.php')) {
    require_once $basePath . 'Services/Modules/HeicToJpgModule.php';
}
if (file_exists($basePath . 'Services/Modules/DocxToPdfModule.php')) {
    require_once $basePath . 'Services/Modules/DocxToPdfModule.php';
}

// Only include ConversionController if it exists
if (file_exists($basePath . 'Controllers/ConversionController.php')) {
    require_once $basePath . 'Controllers/ConversionController.php';
}

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;
use Convertre\Utils\ResponseFormatter;
use Convertre\Services\AuthenticationService;
use Convertre\Controllers\AuthController;

// Initialize systems
try {
    ConfigLoader::init(__DIR__ . '/../config');
    Logger::init(__DIR__ . '/../storage/logs');
    FileHandler::init(__DIR__ . '/../storage/uploads', __DIR__ . '/../storage/converted');
    AuthenticationService::init(__DIR__ . '/../storage');
} catch (Exception $e) {
    ResponseFormatter::sendJson(
        ResponseFormatter::internalError('System initialization failed: ' . $e->getMessage())
    );
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request path - ENHANCED VERSION
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Try multiple path extraction methods
$path = '';

// Method 1: Check PATH_INFO (when using index.php/route)
if (isset($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
}
// Method 2: Parse from REQUEST_URI
else {
    $fullPath = parse_url($requestUri, PHP_URL_PATH);
    
    // Remove base paths
    $fullPath = str_replace('/convertre-api/public', '', $fullPath);
    $fullPath = str_replace('/index.php', '', $fullPath);
    
    $path = $fullPath ?: '/';
}

// Clean up path
$path = '/' . trim($path, '/');
if ($path === '/') $path = '/info';

// Debug - remove in production
if (isset($_GET['debug'])) {
    ResponseFormatter::sendJson([
        'debug' => true,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
        'extracted_path' => $path,
        'method' => $requestMethod,
        'files_count' => count($_FILES),
        'files_debug' => array_keys($_FILES)
    ]);
}

// Simple routing
try {
    switch ($path) {
        case '/':
        case '/info':
            if ($requestMethod === 'GET') {
                $info = [
                    'name' => 'Convertre API',
                    'version' => '1.0.0-MVP',
                    'status' => 'running',
                    'endpoints' => [
                        'GET /info' => 'API information',
                        'GET /health' => 'Health check',
                        'POST /generate-key' => 'Generate API key',
                        'POST /convert' => 'Single file conversion',
                        'POST /convert-batch' => 'Batch file conversion'
                    ]
                ];
                ResponseFormatter::sendJson(ResponseFormatter::success($info));
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        case '/health':
            if ($requestMethod === 'GET') {
                $status = [
                    'status' => 'ok',
                    'timestamp' => gmdate('c'),
                    'version' => '1.0.0-MVP'
                ];
                ResponseFormatter::sendJson(ResponseFormatter::success($status));
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        case '/generate-key':
            if ($requestMethod === 'POST' || $requestMethod === 'GET') {
                AuthController::generateKey();
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        case '/validate-key':
            if ($requestMethod === 'POST') {
                AuthController::validateKey();
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        case '/convert':
            if ($requestMethod === 'POST') {
                // Check if ConversionController exists
                if (class_exists('Convertre\\Controllers\\ConversionController')) {
                    \Convertre\Controllers\ConversionController::convert();
                } else {
                    ResponseFormatter::sendJson(
                        ResponseFormatter::error('Conversion module not available', 'MODULE_UNAVAILABLE', 503)
                    );
                }
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        case '/convert-batch':
            if ($requestMethod === 'POST') {
                // Check if ConversionController exists
                if (class_exists('Convertre\\Controllers\\ConversionController')) {
                    \Convertre\Controllers\ConversionController::convertBatch();
                } else {
                    ResponseFormatter::sendJson(
                        ResponseFormatter::error('Batch conversion not available', 'MODULE_UNAVAILABLE', 503)
                    );
                }
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                );
            }
            break;

        // Handle download route - FIXED pattern matching
        default:
            if (preg_match('/^\/download\/(.+)$/', $path, $matches)) {
                if ($requestMethod === 'GET') {
                    if (class_exists('Convertre\\Controllers\\ConversionController')) {
                        \Convertre\Controllers\ConversionController::download($matches[1]);
                    } else {
                        ResponseFormatter::sendJson(
                            ResponseFormatter::notFound('Download not available')
                        );
                    }
                } else {
                    ResponseFormatter::sendJson(
                        ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
                    );
                }
            } else {
                ResponseFormatter::sendJson(
                    ResponseFormatter::notFound('Endpoint not found: ' . $path)
                );
            }
            break;
    }

} catch (Exception $e) {
    ResponseFormatter::sendJson(
        ResponseFormatter::internalError('Request failed: ' . $e->getMessage())
    );
}