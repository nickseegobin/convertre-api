<?php

/**
 * Convertre API - Complete Test Runner
 * Orchestrates all testing phases for comprehensive validation
 * 
 * Usage: php test_runner.php [test_type]
 * 
 * Test types:
 * - unit: Run unit tests only
 * - integration: Run integration tests only
 * - load: Run load tests only
 * - all: Run all tests (default)
 */

require_once __DIR__ . '/run_tests.php';
require_once __DIR__ . '/load_test.php';

class CompleteTestRunner
{
    private array $testResults = [];
    private bool $verbose;
    
    public function __construct(bool $verbose = true)
    {
        $this->verbose = $verbose;
    }
    
    public function runTests(string $testType = 'all'): void
    {
        $this->printHeader();
        
        switch ($testType) {
            case 'unit':
                $this->runUnitTests();
                break;
            case 'integration':
                $this->runIntegrationTests();
                break;
            case 'load':
                $this->runLoadTests();
                break;
            case 'all':
            default:
                $this->runAllTests();
                break;
        }
        
        $this->printFinalSummary();
    }
    
    private function printHeader(): void
    {
        echo str_repeat("=", 80) . "\n";
        echo "🧪 CONVERTRE API - COMPREHENSIVE TEST SUITE\n";
        echo str_repeat("=", 80) . "\n";
        echo "Phase 7: Testing & Validation\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";
    }
    
    private function runAllTests(): void
    {
        $this->log("🚀 Running complete test suite...\n");
        
        // Phase 1: Environment Check
        $this->runEnvironmentCheck();
        
        // Phase 2: Unit Tests
        $this->runUnitTests();
        
        // Phase 3: Integration Tests
        $this->runIntegrationTests();
        
        // Phase 4: Load Tests
        $this->runLoadTests();
        
        // Phase 5: Manual Test Verification
        $this->runManualTestGuide();
    }
    
    private function runEnvironmentCheck(): void
    {
        $this->log("🔍 Phase 1: Environment Check");
        $this->log(str_repeat("-", 40));
        
        $checks = [
            'PHP Version' => $this->checkPhpVersion(),
            'Required Extensions' => $this->checkPhpExtensions(),
            'Directory Permissions' => $this->checkDirectoryPermissions(),
            'ImageMagick' => $this->checkImageMagick(),
            'LibreOffice' => $this->checkLibreOffice(),
            'Web Server' => $this->checkWebServer()
        ];
        
        $this->printCheckResults($checks);
        $this->testResults['environment'] = $checks;
    }
    
    private function runUnitTests(): void
    {
        $this->log("\n🧪 Phase 2: Unit Tests");
        $this->log(str_repeat("-", 40));
        
        try {
            $testSuite = new ConvertrTestSuite();
            ob_start();
            $testSuite->runAllTests();
            $output = ob_get_clean();
            
            $this->log($output);
            $this->testResults['unit_tests'] = ['status' => 'completed', 'output' => $output];
        } catch (Exception $e) {
            $this->log("❌ Unit tests failed: " . $e->getMessage());
            $this->testResults['unit_tests'] = ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
    
    private function runIntegrationTests(): void
    {
        $this->log("\n🔗 Phase 3: Integration Tests");
        $this->log(str_repeat("-", 40));
        
        $integrationTests = [
            'API Health Endpoint' => $this->testApiHealth(),
            'API Info Endpoint' => $this->testApiInfo(),
            'Key Generation' => $this->testKeyGeneration(),
            'Authentication Flow' => $this->testAuthenticationFlow(),
            'Error Handling' => $this->testErrorHandling()
        ];
        
        $this->printCheckResults($integrationTests);
        $this->testResults['integration_tests'] = $integrationTests;
    }
    
    private function runLoadTests(): void
    {
        $this->log("\n⚡ Phase 4: Load Tests");
        $this->log(str_repeat("-", 40));
        
        try {
            $loadTester = new LoadTester();
            ob_start();
            $loadTester->runLoadTests();
            $output = ob_get_clean();
            
            $this->log($output);
            $this->testResults['load_tests'] = ['status' => 'completed', 'output' => $output];
        } catch (Exception $e) {
            $this->log("❌ Load tests failed: " . $e->getMessage());
            $this->testResults['load_tests'] = ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
    
    private function runManualTestGuide(): void
    {
        $this->log("\n📋 Phase 5: Manual Test Verification Guide");
        $this->log(str_repeat("-", 40));
        
        $this->log("Please verify the following manually using Postman or curl:");
        $this->log("");
        
        $manualTests = [
            "1. HEIC to JPG Conversion" => [
                "Upload a sample HEIC file",
                "Verify JPG output quality",
                "Check download link works",
                "Confirm file expires after 3 hours"
            ],
            "2. DOCX to PDF Conversion" => [
                "Upload a sample DOCX file",
                "Verify PDF formatting preserved",
                "Check processing time < 30 seconds",
                "Confirm file accessibility"
            ],
            "3. Batch Processing" => [
                "Upload multiple HEIC files",
                "Verify all files processed",
                "Check batch response format",
                "Test with mixed file types"
            ],
            "4. File Cleanup" => [
                "Run cleanup endpoint",
                "Verify old files removed",
                "Check storage statistics",
                "Test automatic cleanup (wait 3+ hours)"
            ],
            "5. Error Scenarios" => [
                "Test with invalid file types",
                "Test with oversized files",
                "Test with corrupted files",
                "Test rate limiting (60+ requests/minute)"
            ]
        ];
        
        foreach ($manualTests as $category => $tests) {
            $this->log($category . ":");
            foreach ($tests as $test) {
                $this->log("   ☐ " . $test);
            }
            $this->log("");
        }
        
        $this->testResults['manual_tests'] = $manualTests;
    }
    
    // Environment Check Methods
    private function checkPhpVersion(): bool
    {
        $version = PHP_VERSION;
        $required = '8.2.0';
        $result = version_compare($version, $required, '>=');
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            $this->log("  {$status} PHP Version: {$version} (Required: {$required}+)");
        }
        
        return $result;
    }
    
    private function checkPhpExtensions(): bool
    {
        $required = ['curl', 'fileinfo', 'json', 'mbstring', 'xml'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        $result = empty($missing);
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            if ($result) {
                $this->log("  {$status} All required PHP extensions loaded");
            } else {
                $this->log("  {$status} Missing extensions: " . implode(', ', $missing));
            }
        }
        
        return $result;
    }
    
    private function checkDirectoryPermissions(): bool
    {
        $directories = [
            __DIR__ . '/../storage/uploads',
            __DIR__ . '/../storage/converted',
            __DIR__ . '/../storage/logs'
        ];
        
        $issues = [];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                $issues[] = "Directory not found: " . basename($dir);
            } elseif (!is_writable($dir)) {
                $issues[] = "Not writable: " . basename($dir);
            }
        }
        
        $result = empty($issues);
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            if ($result) {
                $this->log("  {$status} All directories accessible and writable");
            } else {
                foreach ($issues as $issue) {
                    $this->log("  ❌ " . $issue);
                }
            }
        }
        
        return $result;
    }
    
    private function checkImageMagick(): bool
    {
        $result = false;
        $message = '';
        
        try {
            require_once __DIR__ . '/../src/Utils/ImageMagickChecker.php';
            $check = \Convertre\Utils\ImageMagickChecker::checkInstallation();
            $result = $check['available'];
            $message = $result ? 
                "Available (Version: {$check['version']})" : 
                "Not available or misconfigured";
        } catch (Exception $e) {
            $message = "Check failed: " . $e->getMessage();
        }
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            $this->log("  {$status} ImageMagick: {$message}");
        }
        
        return $result;
    }
    
    private function checkLibreOffice(): bool
    {
        $result = false;
        $message = '';
        
        try {
            require_once __DIR__ . '/../src/Utils/LibreOfficeChecker.php';
            $check = \Convertre\Utils\LibreOfficeChecker::checkInstallation();
            $result = $check['available'];
            $message = $result ? 
                "Available (Version: {$check['version']})" : 
                "Not available or misconfigured";
        } catch (Exception $e) {
            $message = "Check failed: " . $e->getMessage();
        }
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            $this->log("  {$status} LibreOffice: {$message}");
        }
        
        return $result;
    }
    
    private function checkWebServer(): bool
    {
        $url = 'http://localhost/convertre-api/public/health';
        $result = false;
        $message = '';
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = ($httpCode === 200);
            $message = $result ? 
                "Accessible (HTTP {$httpCode})" : 
                "Not accessible (HTTP {$httpCode})";
        } catch (Exception $e) {
            $message = "Connection failed: " . $e->getMessage();
        }
        
        if ($this->verbose) {
            $status = $result ? "✅" : "❌";
            $this->log("  {$status} Web Server: {$message}");
        }
        
        return $result;
    }
    
    // Integration Test Methods
    private function testApiHealth(): bool
    {
        return $this->makeApiRequest('/health', 'GET');
    }
    
    private function testApiInfo(): bool
    {
        return $this->makeApiRequest('/info', 'GET');
    }
    
    private function testKeyGeneration(): bool
    {
        return $this->makeApiRequest('/generate-key', 'POST', [
            'user_id' => 'test_runner',
            'name' => 'Test Runner Key'
        ]);
    }
    
    private function testAuthenticationFlow(): bool
    {
        // Generate key first
        $response = $this->makeApiRequest('/generate-key', 'POST', [
            'user_id' => 'auth_test',
            'name' => 'Auth Test Key'
        ], false);
        
        if (!$response) return false;
        
        $data = json_decode($response, true);
        if (!$data || !$data['success'] || !isset($data['api_key'])) {
            return false;
        }
        
        // Test validation
        return $this->makeApiRequest('/validate-key', 'POST', [
            'api_key' => $data['api_key']
        ]);
    }
    
    private function testErrorHandling(): bool
    {
        // Test 404 endpoint
        $result1 = !$this->makeApiRequest('/nonexistent', 'GET');
        
        // Test invalid method
        $result2 = !$this->makeApiRequest('/health', 'DELETE');
        
        return $result1 && $result2;
    }
    
    private function makeApiRequest(string $endpoint, string $method, array $data = [], bool $expectSuccess = true): bool|string
    {
        $url = 'http://localhost/convertre-api/public' . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($expectSuccess === false) {
            return $response; // Return raw response for further processing
        }
        
        if ($httpCode !== 200) {
            return false;
        }
        
        $data = json_decode($response, true);
        return $data && isset($data['success']) && $data['success'];
    }
    
    // Utility Methods
    private function printCheckResults(array $checks): void
    {
        foreach ($checks as $name => $result) {
            if (is_bool($result)) {
                $status = $result ? "✅ PASS" : "❌ FAIL";
                $this->log("  {$status} - {$name}");
            }
        }
    }
    
    private function log(string $message): void
    {
        if ($this->verbose) {
            echo $message . "\n";
        }
    }
    
    private function printFinalSummary(): void
    {
        $this->log("\n" . str_repeat("=", 80));
        $this->log("📊 FINAL TEST SUMMARY");
        $this->log(str_repeat("=", 80));
        
        $totalTests = 0;
        $passedTests = 0;
        
        // Environment checks
        if (isset($this->testResults['environment'])) {
            $envResults = $this->testResults['environment'];
            $envPassed = array_sum($envResults);
            $envTotal = count($envResults);
            
            $this->log("🔍 Environment Checks: {$envPassed}/{$envTotal} passed");
            $totalTests += $envTotal;
            $passedTests += $envPassed;
        }
        
        // Integration tests
        if (isset($this->testResults['integration_tests'])) {
            $intResults = $this->testResults['integration_tests'];
            $intPassed = array_sum($intResults);
            $intTotal = count($intResults);
            
            $this->log("🔗 Integration Tests: {$intPassed}/{$intTotal} passed");
            $totalTests += $intTotal;
            $passedTests += $intPassed;
        }
        
        // Overall status
        $this->log(str_repeat("-", 80));
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        $this->log("📈 Overall Success Rate: {$successRate}% ({$passedTests}/{$totalTests})");
        
        if ($successRate >= 90) {
            $this->log("🎉 EXCELLENT! Your API is ready for production deployment.");
        } elseif ($successRate >= 75) {
            $this->log("✅ GOOD! Minor issues to address before production.");
        } else {
            $this->log("⚠️  NEEDS WORK! Several issues require attention.");
        }
        
        $this->log(str_repeat("=", 80));
        
        // Save results to file
        $this->saveResultsToFile();
    }
    
    private function saveResultsToFile(): void
    {
        $resultsFile = __DIR__ . '/../storage/logs/test_results_' . date('Y-m-d_H-i-s') . '.json';
        
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_results' => $this->testResults,
            'summary' => [
                'total_tests' => 0,
                'passed_tests' => 0,
                'success_rate' => 0
            ]
        ];
        
        file_put_contents($resultsFile, json_encode($reportData, JSON_PRETTY_PRINT));
        $this->log("📄 Detailed results saved to: " . basename($resultsFile));
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $testType = $argv[1] ?? 'all';
    $verbose = !in_array('--quiet', $argv);
    
    $runner = new CompleteTestRunner($verbose);
    $runner->runTests($testType);
}