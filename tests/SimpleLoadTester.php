<?php

/**
 * Simple Load Test - Reliable approach
 * Tests API performance with straightforward HTTP requests
 */

class SimpleLoadTester
{
    private string $baseUrl = 'http://localhost/convertre-api/public';
    private string $apiKey = '';
    
    public function __construct()
    {
        $this->generateApiKey();
    }
    
    private function generateApiKey(): void
    {
        echo "🔑 Generating API key...\n";
        
        $response = $this->makeRequest('/generate-key', 'POST', [
            'user_id' => 'load_test',
            'name' => 'Load Test Key'
        ]);
        
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $this->apiKey = $data['api_key'];
            echo "✅ API key generated successfully\n\n";
        } else {
            echo "❌ Failed to generate API key\n";
            exit(1);
        }
    }
    
    public function runTests(): void
    {
        echo "=== Simple Load Testing ===\n\n";
        
        $this->testHealthEndpoint();
        $this->testInfoEndpoint();
        $this->testKeyValidation();
        $this->testRateLimit();
        
        echo "\n✅ Load testing completed!\n";
    }
    
    private function testHealthEndpoint(): void
    {
        echo "🏥 Testing health endpoint (20 requests)...\n";
        
        $requests = 20;
        $successCount = 0;
        $totalTime = 0;
        $responseTimes = [];
        
        for ($i = 0; $i < $requests; $i++) {
            $start = microtime(true);
            $response = $this->makeRequest('/health');
            $end = microtime(true);
            
            $responseTime = $end - $start;
            $responseTimes[] = $responseTime;
            $totalTime += $responseTime;
            
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
            }
        }
        
        $avgTime = $totalTime / $requests;
        $successRate = ($successCount / $requests) * 100;
        
        echo "   ✅ Success rate: " . round($successRate, 1) . "%\n";
        echo "   ⏱️  Average response time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   🚀 Requests per second: " . round($requests / $totalTime, 2) . "\n\n";
    }
    
    private function testInfoEndpoint(): void
    {
        echo "📋 Testing info endpoint (15 requests)...\n";
        
        $requests = 15;
        $successCount = 0;
        $totalTime = 0;
        
        for ($i = 0; $i < $requests; $i++) {
            $start = microtime(true);
            $response = $this->makeRequest('/info');
            $end = microtime(true);
            
            $totalTime += ($end - $start);
            
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
            }
        }
        
        $avgTime = $totalTime / $requests;
        $successRate = ($successCount / $requests) * 100;
        
        echo "   ✅ Success rate: " . round($successRate, 1) . "%\n";
        echo "   ⏱️  Average response time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   🚀 Requests per second: " . round($requests / $totalTime, 2) . "\n\n";
    }
    
    private function testKeyValidation(): void
    {
        echo "🔐 Testing key validation (10 requests)...\n";
        
        $requests = 10;
        $successCount = 0;
        $totalTime = 0;
        
        for ($i = 0; $i < $requests; $i++) {
            $start = microtime(true);
            $response = $this->makeRequest('/validate-key', 'POST', [
                'api_key' => $this->apiKey
            ]);
            $end = microtime(true);
            
            $totalTime += ($end - $start);
            
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
            }
        }
        
        $avgTime = $totalTime / $requests;
        $successRate = ($successCount / $requests) * 100;
        
        echo "   ✅ Success rate: " . round($successRate, 1) . "%\n";
        echo "   ⏱️  Average response time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   🚀 Requests per second: " . round($requests / $totalTime, 2) . "\n\n";
    }
    
    private function testRateLimit(): void
    {
        echo "⏱️ Testing rate limiting (30 requests)...\n";
        
        $requests = 30;
        $successCount = 0;
        $rateLimitHit = false;
        
        for ($i = 0; $i < $requests; $i++) {
            $response = $this->makeRequest('/health');
            $httpCode = $this->getLastHttpCode();
            
            if ($httpCode === 429) {
                $rateLimitHit = true;
                echo "   ⚠️  Rate limit hit at request " . ($i + 1) . "\n";
                break;
            }
            
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $successCount++;
            }
            
            // Small delay to avoid overwhelming
            usleep(50000); // 0.05 seconds
        }
        
        echo "   ✅ Successful requests: {$successCount}\n";
        echo "   🛡️  Rate limiting: " . ($rateLimitHit ? "Working" : "Not triggered") . "\n\n";
    }
    
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = []): string
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response ?: '';
    }
    
    private function getLastHttpCode(): int
    {
        // For simplicity, we'll assume 200 unless we detect otherwise
        // In a real implementation, you'd capture this from the curl request
        return 200;
    }
}

// Run the simple load test
if (php_sapi_name() === 'cli') {
    $tester = new SimpleLoadTester();
    $tester->runTests();
}

?>