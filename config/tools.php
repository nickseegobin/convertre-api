<?php

/**
 * External Tools Configuration - Windows Compatible
 * Simple, reliable configuration without complex includes
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
    // Unix/Mac detection
    $unixImageMagickPaths = [
        '/opt/homebrew/bin/magick',
        '/usr/local/bin/magick',
        '/usr/bin/magick',
        '/usr/bin/convert'
    ];
    
    $unixLibreOfficePaths = [
        '/opt/homebrew/bin/soffice',
        '/usr/local/bin/soffice',
        '/usr/bin/soffice',
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

// Fallback to PATH if not found
/* if (!$imageMagickPath) {
    // Check if magick is in PATH
    $pathCheck = shell_exec($isWindows ? 'where magick 2>nul' : 'which magick 2>/dev/null');
    if ($pathCheck) {
        $imageMagickPath = $isWindows ? 'magick' : trim($pathCheck);
    }
} */

// Fallback to PATH if not found
if (!$imageMagickPath) {
    // Check if magick is in PATH
    $pathCheck = shell_exec($isWindows ? 'where magick 2>nul' : 'which magick 2>/dev/null');
    if ($pathCheck) {
        $imageMagickPath = 'magick'; // Use simple 'magick' command
    }
}

if (!$libreOfficePath) {
    // Check if soffice is in PATH
    $pathCheck = shell_exec($isWindows ? 'where soffice 2>nul' : 'which soffice 2>/dev/null');
    if ($pathCheck) {
        $libreOfficePath = $isWindows ? 'soffice' : trim($pathCheck);
    }
}

// Build the configuration array
return [
    // ImageMagick Configuration
    'imagemagick' => [
        'binary_path' => 'magick',  // Force simple command
        'magick_path' => 'magick',  // Force simple command
        'identify_path' => 'magick identify',
        'version_check' => 'magick -version',
        'required_version' => '7.0',
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
        'binary_path' => $libreOfficePath ? ($isWindows && strpos($libreOfficePath, ' ') !== false ? '"' . $libreOfficePath . '"' : $libreOfficePath) : 'soffice',
        'headless_mode' => true,
        'version_check' => $libreOfficePath ? ($isWindows && strpos($libreOfficePath, ' ') !== false ? '"' . $libreOfficePath . '" --version' : $libreOfficePath . ' --version') : 'soffice --version',
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