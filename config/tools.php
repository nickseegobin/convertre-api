<?php

/**
 * External Tools Configuration - Cross-Platform Compatible
 * Updated to properly detect ImageMagick on Linux systems
 */

// Detect platform
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Initialize tool paths
$imageMagickPath = null;
$libreOfficePath = null;

if ($isWindows) {
    // Windows-specific detection
    $windowsImageMagickPaths = [
        'C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\magick.exe',
        'C:\Program Files\ImageMagick-7.1.0-Q16-HDRI\magick.exe',
        'C:\Program Files (x86)\ImageMagick-7.1.1-Q16-HDRI\magick.exe',
        'C:\Program Files (x86)\ImageMagick-7.1.0-Q16-HDRI\magick.exe',
        'C:\ImageMagick\magick.exe'
    ];
    
    $windowsLibreOfficePaths = [
        'C:\Program Files\LibreOffice\program\soffice.exe',
        'C:\Program Files (x86)\LibreOffice\program\soffice.exe'
    ];
    
    // Find ImageMagick
    foreach ($windowsImageMagickPaths as $path) {
        if (file_exists($path)) {
            $imageMagickPath = $path;
            break;
        }
    }
    
    // Find LibreOffice
    foreach ($windowsLibreOfficePaths as $path) {
        if (file_exists($path)) {
            $libreOfficePath = $path;
            break;
        }
    }
} else {
    // Unix/Linux/Mac detection - CHECK BOTH magick AND convert
    $unixImageMagickPaths = [
        '/opt/homebrew/bin/magick',
        '/usr/local/bin/magick',
        '/usr/bin/magick',
        '/usr/bin/convert',  // Traditional ImageMagick command
        '/usr/local/bin/convert'
    ];
    
    $unixLibreOfficePaths = [
        '/opt/homebrew/bin/soffice',
        '/usr/local/bin/soffice',
        '/usr/bin/soffice',
        '/usr/bin/libreoffice',
        '/Applications/LibreOffice.app/Contents/MacOS/soffice'
    ];
    
    // Find ImageMagick
    foreach ($unixImageMagickPaths as $path) {
        if (file_exists($path) && is_executable($path)) {
            $imageMagickPath = $path;
            break;
        }
    }
    
    // Find LibreOffice
    foreach ($unixLibreOfficePaths as $path) {
        if (file_exists($path) && is_executable($path)) {
            $libreOfficePath = $path;
            break;
        }
    }
}

// Fallback to PATH detection with better logic
if (!$imageMagickPath) {
    // Try 'magick' first, then fall back to 'convert'
    $magickCheck = shell_exec($isWindows ? 'where magick 2>nul' : 'which magick 2>/dev/null');
    if ($magickCheck && trim($magickCheck)) {
        $imageMagickPath = 'magick';
    } else {
        // Fall back to 'convert' command
        $convertCheck = shell_exec($isWindows ? 'where convert 2>nul' : 'which convert 2>/dev/null');
        if ($convertCheck && trim($convertCheck)) {
            $imageMagickPath = 'convert';
        }
    }
}

if (!$libreOfficePath) {
    // Check if soffice is in PATH
    $pathCheck = shell_exec($isWindows ? 'where soffice 2>nul' : 'which soffice 2>/dev/null');
    if ($pathCheck && trim($pathCheck)) {
        $libreOfficePath = 'soffice';
    } else {
        // Try libreoffice command
        $pathCheck = shell_exec($isWindows ? 'where libreoffice 2>nul' : 'which libreoffice 2>/dev/null');
        if ($pathCheck && trim($pathCheck)) {
            $libreOfficePath = 'libreoffice';
        }
    }
}

// Build the configuration array
return [
    // ImageMagick Configuration
    'imagemagick' => [
        'binary_path' => $imageMagickPath ?: 'convert',  // Use detected path or default to 'convert'
        'magick_path' => $imageMagickPath ?: 'convert',
        'identify_path' => ($imageMagickPath === 'magick') ? 'magick identify' : 'identify',
        'version_check' => ($imageMagickPath ?: 'convert') . ' -version',
        'required_version' => '6.9',  // Lowered for compatibility with older versions
        'timeout' => 120,
        'memory_limit' => '256MB',
        'quality_settings' => [
            'jpg' => 85,
            'webp' => 80,
            'png' => 9,
            'pdf' => 85
        ],
        'pdf_settings' => [
            'page_size' => 'A4',
            'density' => 300,
            'compress' => 'JPEG',
            'gravity' => 'center'
        ],
        'common_options' => [
            '-strip',
            '-auto-orient',
            '-colorspace sRGB'
        ]
    ],
    
    // LibreOffice Configuration
    'libreoffice' => [
        'binary_path' => $libreOfficePath ?: 'libreoffice',
        'headless_mode' => true,
        'version_check' => ($libreOfficePath ?: 'libreoffice') . ' --version',
        'required_version' => '7.0',
        'timeout' => 300,
        'temp_profile_dir' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'libreoffice_profiles',
        'export_options' => [
            'pdf' => [
                'export_format' => 'pdf',
                'quality' => '90'
            ]
        ],
        'common_options' => [
            '--headless',
            '--invisible',
            '--nodefault',
            '--nolockcheck',
            '--nologo',
            '--norestore'
        ]
    ],
    
    // Detection status (for debugging)
    'detection_status' => [
        'platform' => $isWindows ? 'Windows' : PHP_OS,
        'imagemagick_found' => !is_null($imageMagickPath),
        'imagemagick_path' => $imageMagickPath,
        'imagemagick_command' => $imageMagickPath ?: 'convert',
        'libreoffice_found' => !is_null($libreOfficePath),
        'libreoffice_path' => $libreOfficePath,
        'current_user' => get_current_user(),
        'php_os' => PHP_OS
    ],
    
    // Tool health check settings
    'health_check' => [
        'enabled' => true,
        'cache_duration' => 300,
        'check_on_startup' => true,
        'retry_attempts' => 3,
        'retry_delay' => 1
    ],
    
    // Development and debugging options
    'debug' => [
        'log_commands' => true,
        'log_output' => true,
        'save_failed_conversions' => false,
        'verbose_errors' => true
    ],
    
    // Resource limits
    'limits' => [
        'max_processes' => 4,
        'memory_per_process' => '256MB',
        'temp_cleanup_interval' => 3600,
        'max_conversion_time' => 300
    ]
];