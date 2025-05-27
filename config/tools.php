<?php

/**
 * External Tools Configuration - Corrected for macOS
 * 
 * Paths and settings for ImageMagick and LibreOffice
 * Fixed syntax errors and compatibility issues
 */

return [
    // ImageMagick Configuration
    'imagemagick' => [
        // Use convert command for module compatibility
        'binary_path' => '/opt/homebrew/bin/magick', // Primary path for modules
        'magick_path' => '/opt/homebrew/bin/magick',  // IM7 main binary  
        'identify_path' => '/opt/homebrew/bin/magick identify',
        'version_check' => '/opt/homebrew/bin/magick -version', // Fixed: removed 'convert'
        'required_version' => '7.0',
        'timeout' => 120, // Increased for PDF conversions (was 60)
        'memory_limit' => '256MB',
        'quality_settings' => [
            'jpg' => 85,
            'webp' => 80,
            'png' => 9,      // Fixed: Added missing comma
            'pdf' => 85      // Fixed: Added PDF quality setting
        ],
        'pdf_settings' => [
            'page_size' => 'A4',
            'density' => 300,
            'compress' => 'JPEG',
            'gravity' => 'center'
        ],
        'common_options' => [
            '-strip',           // Remove metadata
            '-auto-orient',     // Fix image orientation
            '-colorspace sRGB'  // Ensure consistent color space
        ]
    ],
    
    // LibreOffice Configuration
    'libreoffice' => [
        'binary_path' => '/opt/homebrew/bin/soffice', // Use full path
        'headless_mode' => true,
        'version_check' => '/opt/homebrew/bin/soffice --version', // Use full path
        'required_version' => '7.0',
        'timeout' => 300, // 5 minutes for document conversion
        'temp_profile_dir' => sys_get_temp_dir() . '/libreoffice_profiles',
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
    
    // Tool health check settings
    'health_check' => [
        'enabled' => true,
        'cache_duration' => 300, // Cache health status for 5 minutes
        'check_on_startup' => true,
        'retry_attempts' => 3,
        'retry_delay' => 1 // seconds between retries
    ],
    
    // Path detection and fallback system
    'path_detection' => [
        'auto_detect' => true,
        'prefer_homebrew' => true, // Prefer Homebrew on macOS
        'fallback_to_system' => true,
        'cache_detected_paths' => true
    ],
    
    // Platform-specific paths for fallback detection
    'macos_paths' => [
        'imagemagick' => [
            // Apple Silicon (M1/M2) Homebrew paths first
            '/opt/homebrew/bin/magick',
            '/opt/homebrew/bin/convert',       // IM7 convert compatibility
            // Intel Mac Homebrew paths
            '/usr/local/bin/magick',
            '/usr/local/bin/convert',
            // MacPorts
            '/opt/local/bin/magick',
            '/opt/local/bin/convert',
            // System paths
            '/usr/bin/magick',
            '/usr/bin/convert'
        ],
        'libreoffice' => [
            // Homebrew installation (preferred for CLI)
            '/opt/homebrew/bin/soffice',
            '/usr/local/bin/soffice',
            // Application bundle
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            // Other locations
            '/opt/local/bin/soffice'
        ]
    ],
    
    // Windows-specific paths
    'windows_paths' => [
        'imagemagick' => [
            'C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\magick.exe',
            'C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\convert.exe',
            'C:\ImageMagick\magick.exe',
            'C:\ImageMagick\convert.exe'
        ],
        'libreoffice' => [
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe'
        ]
    ],
    
    // Linux/Unix common paths
    'unix_paths' => [
        'imagemagick' => [
            '/usr/bin/convert',     // Traditional IM6 (most common)
            '/usr/bin/magick',      // IM7
            '/usr/local/bin/convert',
            '/usr/local/bin/magick',
            '/opt/local/bin/convert',
            '/snap/bin/convert'     // Snap package
        ],
        'libreoffice' => [
            '/usr/bin/soffice',
            '/usr/bin/libreoffice',
            '/usr/local/bin/soffice',
            '/usr/local/bin/libreoffice',
            '/opt/libreoffice/program/soffice',
            '/snap/bin/libreoffice'
        ]
    ],
    
    // Development and debugging options
    'debug' => [
        'log_commands' => true,
        'log_output' => true,
        'save_failed_conversions' => false, // Set to true for debugging
        'verbose_errors' => true
    ],
    
    // Resource limits and optimization
    'limits' => [
        'max_processes' => 4,           // Concurrent conversion processes
        'memory_per_process' => '256MB',
        'temp_cleanup_interval' => 3600, // 1 hour
        'max_conversion_time' => 300     // 5 minutes max per conversion
    ]
];