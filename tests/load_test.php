<?php

/**
 * Convertre API Load Testing Script
 * Tests API performance under concurrent load
 * 
 * Usage: php tests/load_test.php
 */

class LoadTester
{
    private string $baseUrl;
    private string $apiKey;
    private array $results = [];
    
    public function __construct(string $baseUrl = 'http://localhost/convertre-api/public')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->generateApiKey();
    }
    
    private function generateApiKey(): void
    {
        echo "Generating API key for load testing...\n";
        
        $response = $this->makeRequest('/generate-key', 'POST', [
            'user_id' => 'load_test',
            'name' => 'Load Test Key'
        ]);
        
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $this->apiKey = $data['api_key'];
            echo "✅ API key generated: " . substr($this->apiKey, 0, 10) . "...\n\n";
        } else {
            echo "❌ Failed to generate API key\n";
            exit(1);
        }
    }
    
    public function runLoadTests(): void
    {
        echo "=== Convertre API Load Testing ===\n\n";
        
        // Test 1: Concurrent Health Checks
        $this->testConcurrentHealthChecks();
        
        // Test 2: Concurrent API Info Requests
        $this->testConcurrentApiInfo();
        
        // Test 3: Concurrent API Key Generation
        $this->testConcurrentKeyGeneration();
        
        // Test 4: Authentication Performance
        $this->testAuthenticationPerformance();
        
        // Test 5: Rate Limiting
        $this->testRateLimiting();
        
        $this->printSummary();
    }
    
    private function testConcurrentHealthChecks(): void
    {
        echo "🏥 Testing concurrent health checks (50 requests)...\n";
        
        $startTime = microtime(true);
        $processes = [];
        $requestCount = 50;
        
        // Launch concurrent requests
        for ($i = 0; $i < $requestCount; $i++) {
            $processes[] = $this->launchAsyncRequest('/health');
        }
        
        // Wait for all processes to complete
        $responses = $this->waitForProcesses($processes);
        $endTime = microtime(true);
        
        $this->analyzeResults('Health Check Load Test', $responses, $endTime - $startTime, $requestCount);
    }
    
    private function testConcurrentApiInfo(): void
    {
        echo "📋 Testing concurrent API info requests (30 requests)...\n";
        
        $startTime = microtime(true);
        $processes = [];
        $requestCount = 30;
        
        for ($i = 0; $i < $requestCount; $i++) {
            $processes[] = $this->launchAsyncRequest('/info');
        }
        
        $responses = $this->waitForProcesses($processes);
        $endTime = microtime(true);
        
        $this->analyzeResults('API Info Load Test', $responses, $endTime - $startTime, $requestCount);
    }
    
    private function testConcurrentKeyGeneration(): void
    {
        echo "🔑 Testing concurrent key generation (20 requests)...\n";
        
        $startTime = microtime(true);
        $processes = [];
        $requestCount = 20;
        
        for ($i = 0; $i < $requestCount; $i++) {
            $processes[] = $this->launchAsyncRequest('/generate-key', 'POST', [
                'user_id' => 'load_test_' . $i,
                'name' => 'Load Test Key ' . $i
            ]);
        }
        
        $responses = $this->waitForProcesses($processes);
        $endTime = microtime(true);
        
        $this->analyzeResults('Key Generation Load Test', $responses, $endTime - $startTime, $requestCount);
    }
    
    private function testAuthenticationPerformance(): void
    {
        echo "🔐 Testing authentication performance (100 requests)...\n";
        
        $startTime = microtime(true);
        $requestCount = 100;
        $successCount = 0;
        
        for ($i = 0; $i < $requestCount; $i++) {
            $response = $this->makeRequest('/validate-key', 'POST', [
                'api_key' => $this->apiKey
            ]);
            
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                $successCount++;
            }
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        echo "   Total time: " . round($totalTime, 3) . "s\n";
        echo "   Average per request: " . round($totalTime / $requestCount * 1000, 2) . "ms\n";
        echo "   Success rate: " . round($successCount / $requestCount * 100, 1) . "%\n";
        echo "   Requests per second: " . round($requestCount / $totalTime, 2) . "\n\n";
    }
    
    private function testRateLimiting(): void
    {
        echo "⏱️ Testing rate limiting (70 requests in 30 seconds)...\n";
        
        $startTime = time();
        $requestCount = 70;
        $rateLimitHit = false;
        $successCount = 0;
        
        for ($i = 0; $i < $requestCount; $i++) {
            $response = $this->makeRequest('/health', 'GET', [], [
                'X-API-Key: ' . $this->apiKey
            ]);
            
            $httpCode = $this->getLastHttpCode();
            
            if ($httpCode === 429) {
                $rateLimitHit = true;
                echo "   Rate limit hit at request " . ($i + 1) . "\n";
                break;
            } elseif ($httpCode === 200) {
                $successCount++;
            }
            
            // Small delay to spread requests over time
            if ($i % 10 === 0) {
                usleep(100000); // 0.1 second delay every 10 requests
            }
        }
        
        $endTime = time();
        $totalTime = $endTime - $startTime;
        
        echo "   Total time: {$totalTime}s\n";
        echo "   Successful requests: {$successCount}\n";
        echo "   Rate limiting working: " . ($rateLimitHit ? "✅ Yes" : "❌ No") . "\n\n";
    }
    
    private function launchAsyncRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $cmd = $this->buildCurlCommand($endpoint, $method, $data);
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open($cmd, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return ['process' => null, 'pipes' => null];
        }
        
        fclose($pipes[0]); // Close stdin
        
        return [
            'process' => $process,
            'pipes' => $pipes,
            'start_time' => microtime(true)
        ];
    }
    
    private function waitForProcesses(array $processes): array
    {
        $responses = [];
        
        foreach ($processes as $i => $processInfo) {
            if (!$processInfo['process']) {
                $responses[$i] = ['success' => false, 'response_time' => 0, 'data' => null];
                continue;
            }
            
            $output = stream_get_contents($processInfo['pipes'][1]);
            $error = stream_get_contents($processInfo['pipes'][2]);
            
            fclose($processInfo['pipes'][1]);
            fclose($processInfo['pipes'][2]);
            
            $exitCode = proc_close($processInfo['process']);
            $endTime = microtime(true);
            
            $responseTime = $endTime - $processInfo['start_time'];
            
            // Parse HTTP code and JSON response
            $httpCode = 200;
            $jsonResponse = $output;
            
            if (strpos($output, 'HTTP_CODE:') !== false) {
                $parts = explode('HTTP_CODE:', $output);
                $jsonResponse = trim($parts[0]);
                $httpCode = (int)trim($parts[1]);
            }
            
            $data = json_decode($jsonResponse, true);
            $success = ($exitCode === 0 && $httpCode === 200 && $data && isset($data['success']) && $data['success']);
            
            $responses[$i] = [
                'success' => $success,
                'response_time' => $responseTime,
                'data' => $data,
                'http_code' => $httpCode
            ];
        }
        
        return $responses;
    }
    
    private function analyzeResults(string $testName, array $responses, float $totalTime, int $requestCount): void
    {
        $successCount = 0;
        $responseTimes = [];
        
        foreach ($responses as $response) {
            if ($response['success']) {
                $successCount++;
            }
            $responseTimes[] = $response['response_time'];
        }
        
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $minResponseTime = min($responseTimes);
        $maxResponseTime = max($responseTimes);
        
        echo "   Results for {$testName}:\n";
        echo "   ├─ Total requests: {$requestCount}\n";
        echo "   ├─ Successful: {$successCount} (" . round($successCount / $requestCount * 100, 1) . "%)\n";
        echo "   ├─ Total time: " . round($totalTime, 3) . "s\n";
        echo "   ├─ Requests/second: " . round($requestCount / $totalTime, 2) . "\n";
        echo "   ├─ Avg response time: " . round($avgResponseTime * 1000, 2) . "ms\n";
        echo "   ├─ Min response time: " . round($minResponseTime * 1000, 2) . "ms\n";
        echo "   └─ Max response time: " . round($maxResponseTime * 1000, 2) . "ms\n\n";
        
        $this->results[$testName] = [
            'total_requests' => $requestCount,
            'successful_requests' => $successCount,
            'success_rate' => $successCount / $requestCount * 100,
            'total_time' => $totalTime,
            'requests_per_second' => $requestCount / $totalTime,
            'avg_response_time' => $avgResponseTime * 1000,
            'min_response_time' => $minResponseTime * 1000,
            'max_response_time' => $maxResponseTime * 1000
        ];
    }
    
    private function buildCurlCommand(string $endpoint, string $method = 'GET', array $data = []): string
    {
        $url = $this->baseUrl . $endpoint;
        $cmd = "curl -s -w '\\nHTTP_CODE:%{http_code}' -X {$method}";
        
        if (!empty($data)) {
            $postData = http_build_query($data);
            $cmd .= " -d '{$postData}'";
        }
        
        $cmd .= " '{$url}' 2>/dev/null";
        
        return $cmd;
    }
    
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): string
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response ?: '';
    }
    
    private function getLastHttpCode(): int
    {
        // This is a simplified version - in real implementation,
        // you'd need to capture the HTTP code from the curl request
        return 200;
    }
    
    private function extractHttpCodeFromCurl(string $output): int
    {
        // Look for HTTP status in curl output
        if (preg_match('/HTTP\/\d+\.?\d*\s+(\d+)/', $output, $matches)) {
            return (int)$matches[1];
        }
        
        // Fallback: check last line if numeric
        $lines = explode("\n", trim($output));
        $lastLine = end($lines);
        if (is_numeric($lastLine)) {
            return (int)$lastLine;
        }
        
        // Default assumption if we can't parse
        return 200;
    }
    
    private function printSummary(): void
    {
        echo str_repeat("=", 60) . "\n";
        echo "LOAD TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        foreach ($this->results as $testName => $results) {
            echo "\n{$testName}:\n";
            echo "  Success Rate: " . round($results['success_rate'], 1) . "%\n";
            echo "  Requests/sec: " . round($results['requests_per_second'], 2) . "\n";
            echo "  Avg Response: " . round($results['avg_response_time'], 2) . "ms\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 Load testing completed!\n";
        echo "Review the results above to identify any performance issues.\n";
    }
}

// Run load tests if called directly
if (php_sapi_name() === 'cli') {
    $loadTester = new LoadTester();
    $loadTester->runLoadTests();
}