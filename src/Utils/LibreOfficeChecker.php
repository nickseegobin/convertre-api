<?php

namespace Convertre\Utils;

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * LibreOfficeChecker - Tool availability and health checking
 * Simple utility to verify LibreOffice setup
 */
class LibreOfficeChecker
{
    /**
     * Check if LibreOffice is properly installed and configured
     */
    public static function checkInstallation(): array
    {
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'libreoffice');
        
        $result = [
            'available' => false,
            'version' => '',
            'headless_support' => false,
            'libreoffice_path' => $librePath,
            'errors' => []
        ];
        
        // Test LibreOffice command
        $versionTest = self::testCommand($librePath . ' --version');
        if (!$versionTest['success']) {
            $result['errors'][] = 'LibreOffice command not found';
            return $result;
        }
        
        // Extract version
        if (preg_match('/LibreOffice (\d+\.\d+\.\d+)/', $versionTest['output'], $matches)) {
            $result['version'] = $matches[1];
        }
        
        // Check minimum version (7.0+)
        if ($result['version'] && version_compare($result['version'], '7.0', '<')) {
            $result['errors'][] = 'LibreOffice version too old (7.0+ required)';
        }
        
        // Test headless mode
        $headlessTest = self::testCommand($librePath . ' --headless --help');
        $result['headless_support'] = $headlessTest['success'];
        
        if (!$result['headless_support']) {
            $result['errors'][] = 'Headless mode not supported';
        }
        
        $result['available'] = empty($result['errors']);
        
        Logger::debug('LibreOffice installation check', $result);
        
        return $result;
    }
    
    /**
     * Test a document conversion
     */
    public static function testConversion(): array
    {
        // Create a minimal DOCX file (base64 encoded)
        $testInput = tempnam(sys_get_temp_dir(), 'lo_test_') . '.docx';
        $testOutput = tempnam(sys_get_temp_dir(), 'lo_test_') . '.pdf';
        $tempDir = sys_get_temp_dir() . '/lo_test_' . uniqid();
        
        // Minimal DOCX content (empty document)
        $docxData = self::createMinimalDocx();
        file_put_contents($testInput, $docxData);
        
        mkdir($tempDir, 0755, true);
        
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'libreoffice');
        $command = sprintf(
            '%s --headless --invisible --convert-to pdf --outdir %s %s',
            $librePath,
            escapeshellarg($tempDir),
            escapeshellarg($testInput)
        );
        
        $result = self::testCommand($command, 30); // 30 second timeout
        
        // Check if PDF was created
        $pdfFile = $tempDir . '/' . pathinfo($testInput, PATHINFO_FILENAME) . '.pdf';
        $pdfExists = file_exists($pdfFile);
        
        $testResult = [
            'success' => $result['success'] && $pdfExists,
            'error' => $result['error'],
            'pdf_created' => $pdfExists,
            'pdf_size' => $pdfExists ? filesize($pdfFile) : 0
        ];
        
        // Cleanup
        if (file_exists($testInput)) unlink($testInput);
        if (file_exists($pdfFile)) unlink($pdfFile);
        if (is_dir($tempDir)) rmdir($tempDir);
        
        return $testResult;
    }
    
    /**
     * Create minimal DOCX file for testing
     */
    private static function createMinimalDocx(): string
    {
        // Base64 encoded minimal DOCX file (just contains "Test")
        return base64_decode('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');
    }
    
    /**
     * Execute command safely for testing
     */
    private static function testCommand(string $command, int $timeout = 10): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return ['success' => false, 'output' => '', 'error' => 'Failed to start process'];
        }
        
        fclose($pipes[0]);
        
        // Set non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        $output = '';
        $error = '';
        $start = time();
        
        while (time() - $start < $timeout) {
            $status = proc_get_status($process);
            
            if (!$status['running']) {
                break;
            }
            
            $output .= stream_get_contents($pipes[1]);
            $error .= stream_get_contents($pipes[2]);
            
            usleep(100000); // 0.1 second
        }
        
        // Final read
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        
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
}