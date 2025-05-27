<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * XlsxToPdfModule - XLSX to PDF conversion using LibreOffice
 * Simple, functional LibreOffice headless integration for Excel spreadsheets
 */
class XlsxToPdfModule extends AbstractConversionModule
{
    public function __construct()
    {
        parent::__construct('xlsx', 'pdf', 'libreoffice');
    }
    
    /**
     * Execute XLSX to PDF conversion using LibreOffice
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        // Get LibreOffice settings
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'soffice');
        $timeout = ConfigLoader::get('tools.libreoffice.timeout', 300);
        
        // Create temporary output directory
        $tempDir = sys_get_temp_dir() . '/convertre_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Build LibreOffice command
            $command = $this->buildLibreOfficeCommand($librePath, $inputFile, $tempDir);
            
            Logger::debug('LibreOffice XLSX conversion', [
                'input' => basename($inputFile),
                'temp_dir' => $tempDir,
                'timeout' => $timeout
            ]);
            
            // Execute conversion
            $result = $this->executeCommand($command, $timeout);
            
            if (!$result['success']) {
                Logger::error('LibreOffice conversion failed', [
                    'command' => $command,
                    'exit_code' => $result['exit_code'],
                    'error' => $result['error']
                ]);
                return false;
            }
            
            // Find generated PDF file
            $pdfFile = $this->findGeneratedPdf($tempDir, $inputFile);
            
            if (!$pdfFile || !file_exists($pdfFile)) {
                Logger::error('LibreOffice PDF not generated', ['temp_dir' => $tempDir]);
                return false;
            }
            
            // Move PDF to final location
            $success = rename($pdfFile, $outputFile);
            
            if (!$success) {
                Logger::error('Failed to move PDF to output location');
                return false;
            }
            
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
        
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'soffice');
        
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
     * Build LibreOffice conversion command
     */
    private function buildLibreOfficeCommand(string $librePath, string $inputFile, string $tempDir): string
    {
        // Escape paths
        $inputFile = escapeshellarg($inputFile);
        $tempDir = escapeshellarg($tempDir);
        
        // LibreOffice headless options
        $options = [
            '--headless',
            '--invisible',
            '--nodefault',
            '--nolockcheck',
            '--nologo',
            '--norestore',
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
        
        // Remove all files in temp dir
        $files = glob($tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Remove directory
        rmdir($tempDir);
        
        Logger::debug('Cleaned up temp directory', ['dir' => $tempDir]);
    }
}