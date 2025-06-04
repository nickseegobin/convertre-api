<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * DocxToPdfModule - DOCX to PDF conversion using LibreOffice
 * Simple, functional LibreOffice headless integration
 */
class DocxToPdfModule extends AbstractConversionModule
{
    public function __construct()
    {
        parent::__construct('docx', 'pdf', 'libreoffice');
    }
    
    /**
     * Execute DOCX to PDF conversion using LibreOffice
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        // Get LibreOffice settings
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'libreoffice');
        $timeout = ConfigLoader::get('tools.libreoffice.timeout', 300);
        
        // Create temporary output directory
        $tempDir = sys_get_temp_dir() . '/convertre_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Build LibreOffice command with proper user directory
            $command = $this->buildLibreOfficeCommand($librePath, $inputFile, $tempDir);
            
            // Enhanced logging
            Logger::debug('LibreOffice DOCX conversion starting', [
                'input' => basename($inputFile),
                'input_file_exists' => file_exists($inputFile),
                'input_size' => file_exists($inputFile) ? filesize($inputFile) : 'N/A',
                'temp_dir' => $tempDir,
                'temp_dir_exists' => is_dir($tempDir),
                'temp_dir_writable' => is_writable($tempDir),
                'libre_path' => $librePath,
                'timeout' => $timeout,
                'current_user' => get_current_user(),
                'process_uid' => getmyuid()
            ]);
            
            // Execute conversion
            $result = $this->executeCommand($command, $timeout);
            
            // Enhanced result logging
            Logger::debug('LibreOffice command result', [
                'success' => $result['success'],
                'exit_code' => $result['exit_code'],
                'output' => $result['output'],
                'error' => $result['error'],
                'command' => $command
            ]);
            
            if (!$result['success']) {
                Logger::error('LibreOffice conversion failed with details', [
                    'command' => $command,
                    'exit_code' => $result['exit_code'],
                    'output' => $result['output'],
                    'error' => $result['error'],
                    'input_file' => $inputFile,
                    'temp_dir' => $tempDir
                ]);
                return false;
            }
            
            // Check what files were created
            if (is_dir($tempDir)) {
                $tempContents = scandir($tempDir);
                Logger::debug('Temp directory contents after conversion', [
                    'temp_dir' => $tempDir,
                    'files' => array_diff($tempContents, ['.', '..']),
                    'file_count' => count($tempContents) - 2
                ]);
            }
            
            // Find generated PDF file
            $pdfFile = $this->findGeneratedPdf($tempDir, $inputFile);
            
            if (!$pdfFile || !file_exists($pdfFile)) {
                Logger::error('LibreOffice PDF not generated', [
                    'temp_dir' => $tempDir,
                    'expected_pdf' => $pdfFile,
                    'temp_contents' => is_dir($tempDir) ? scandir($tempDir) : 'temp dir missing'
                ]);
                return false;
            }
            
            // Move PDF to final location
            $success = rename($pdfFile, $outputFile);
            
            if (!$success) {
                Logger::error('Failed to move PDF to output location', [
                    'source' => $pdfFile,
                    'destination' => $outputFile,
                    'source_exists' => file_exists($pdfFile),
                    'destination_dir_writable' => is_writable(dirname($outputFile))
                ]);
                return false;
            }
            
            Logger::debug('PDF conversion completed successfully', [
                'output_file' => $outputFile,
                'output_size' => filesize($outputFile)
            ]);
            
            return true;
            
        } finally {
            // Cleanup temp directory
            $this->cleanupTempDir($tempDir);
        }
    }
    
    /**
     * Check if LibreOffice is available
     */
    protected function isToolAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'libreoffice');
        
        // Test LibreOffice availability
        $result = $this->executeCommand($librePath . ' --version', 10);
        
        if (!$result['success']) {
            Logger::warning('LibreOffice not available', ['path' => $librePath]);
            $available = false;
            return false;
        }
        
        // Check version (should be 7.0+)
        $version = $this->extractVersion($result['output']);
        if ($version && version_compare($version, '7.0', '<')) {
            Logger::warning('LibreOffice version too old', ['version' => $version]);
            $available = false;
            return false;
        }
        
        Logger::debug('LibreOffice available', ['version' => $version]);
        $available = true;
        return true;
    }
    
    /**
     * Build LibreOffice conversion command with proper user directory handling
     */
    private function buildLibreOfficeCommand(string $librePath, string $inputFile, string $tempDir): string
    {
        // Create a dedicated user directory for this process
        $userDir = $tempDir . '/user_profile';
        mkdir($userDir, 0755, true);
        
        // Escape paths
        $inputFile = escapeshellarg($inputFile);
        $tempDir = escapeshellarg($tempDir);
        $userDir = escapeshellarg($userDir);
        
        // LibreOffice headless options with user directory
        $options = [
            '--headless',
            '--invisible',
            '--nodefault',
            '--nolockcheck',
            '--nologo',
            '--norestore',
            '-env:UserInstallation=file://' . trim($userDir, "'\""), // Remove quotes for this part
            '--convert-to pdf',
            '--outdir ' . $tempDir
        ];
        
        return sprintf(
            '%s %s %s',
            $librePath,
            implode(' ', $options),
            $inputFile
        );
    }
    
    /**
     * Find the generated PDF file in temp directory
     */
    private function findGeneratedPdf(string $tempDir, string $inputFile): ?string
    {
        $inputName = pathinfo($inputFile, PATHINFO_FILENAME);
        $expectedPdf = $tempDir . '/' . $inputName . '.pdf';
        
        if (file_exists($expectedPdf)) {
            return $expectedPdf;
        }
        
        // Fallback: look for any PDF in temp dir
        $files = glob($tempDir . '/*.pdf');
        return $files[0] ?? null;
    }
    
    /**
     * Extract version from LibreOffice --version output
     */
    private function extractVersion(string $output): ?string
    {
        if (preg_match('/LibreOffice (\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Cleanup temporary directory
     */
    private function cleanupTempDir(string $tempDir): void
    {
        if (!is_dir($tempDir)) {
            return;
        }
        
        // Remove all files and subdirectories recursively
        $this->removeDirectoryRecursive($tempDir);
        
        Logger::debug('Cleaned up temp directory', ['dir' => $tempDir]);
    }
    
    /**
     * Recursively remove directory and all contents
     */
    private function removeDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $this->removeDirectoryRecursive($filePath);
            } else {
                unlink($filePath);
            }
        }
        
        rmdir($dir);
    }
}