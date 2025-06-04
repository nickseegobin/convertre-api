<?php

namespace Convertre\Utils;

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * ImageMagickChecker - Tool availability and health checking
 * Simple utility to verify ImageMagick setup
 */
class ImageMagickChecker
{
    /**
     * Check if ImageMagick is properly installed and configured
     */
    public static function checkInstallation(): array
    {
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        $identifyPath = ConfigLoader::get('tools.imagemagick.identify_path', 'identify');
        
        $result = [
            'available' => false,
            'version' => '',
            'heic_support' => false,
            'convert_path' => $convertPath,
            'identify_path' => $identifyPath,
            'errors' => []
        ];
        
        // Test convert command
        $convertTest = self::testCommand($convertPath . ' -version');
        if (!$convertTest['success']) {
            $result['errors'][] = 'ImageMagick convert command not found';
            return $result;
        }
        
        // Extract version
        if (preg_match('/ImageMagick (\d+\.\d+\.\d+)/', $convertTest['output'], $matches)) {
            $result['version'] = $matches[1];
        }
        
        // Test identify command
        $identifyTest = self::testCommand($identifyPath . ' -version');
        if (!$identifyTest['success']) {
            $result['errors'][] = 'ImageMagick identify command not found';
        }
        
        // Check HEIC support
        $heicTest = self::testCommand($convertPath . ' -list format | grep -i heic');
        $result['heic_support'] = $heicTest['success'] && !empty(trim($heicTest['output']));
        
        if (!$result['heic_support']) {
            $result['errors'][] = 'HEIC format support not detected';
        }
        
        $result['available'] = empty($result['errors']);
        
        Logger::debug('ImageMagick installation check', $result);
        
        return $result;
    }
    
    /**
     * Test a specific ImageMagick operation
     */
    public static function testConversion(): array
    {
        // Create a simple test image (1x1 pixel)
        $testInput = tempnam(sys_get_temp_dir(), 'im_test_') . '.png';
        $testOutput = tempnam(sys_get_temp_dir(), 'im_test_') . '.jpg';
        
        // Create minimal PNG
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
        file_put_contents($testInput, $pngData);
        
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        $command = sprintf('%s %s -quality 85 %s', $convertPath, escapeshellarg($testInput), escapeshellarg($testOutput));
        
        $result = self::testCommand($command);
        
        $testResult = [
            'success' => $result['success'] && file_exists($testOutput),
            'error' => $result['error'],
            'output_created' => file_exists($testOutput)
        ];
        
        // Cleanup
        if (file_exists($testInput)) unlink($testInput);
        if (file_exists($testOutput)) unlink($testOutput);
        
        return $testResult;
    }
    
    /**
     * Execute command safely for testing
     */
    private static function testCommand(string $command): array
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
}