<?php

/**
 * External Tools Configuration - Fixed for macOS
 * 
 * Paths and settings for ImageMagick and LibreOffice
 */

return [
    // ImageMagick Configuration
    'imagemagick' => [
        'binary_path' => 'magick', // Updated for ImageMagick 7
        'identify_path' => 'magick identify',
        'version_check' => 'magick convert -version',
        'required_version' => '7.0',
        'timeout' => 60, // seconds
        'memory_limit' => '256MB',
        'quality_settings' => [
            'jpg' => 85,
            'webp' => 80,
            'png' => 9
        ],
        'common_options' => [
            '-strip',           // Remove metadata
            '-auto-orient',     // Fix image orientation
            '-colorspace sRGB'  // Ensure consistent color space
        ]
    ],
    
    // LibreOffice Configuration - FIXED
    'libreoffice' => [
        'binary_path' => 'soffice', // CHANGED: Use 'soffice' instead of 'libreoffice'
        'headless_mode' => true,
        'version_check' => 'soffice --version',
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
        'check_on_startup' => true
    ],
    
    // Windows-specific paths (if detected)
    'windows_paths' => [
        'imagemagick' => [
            'C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\convert.exe',
            'C:\ImageMagick\convert.exe'
        ],
        'libreoffice' => [
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe'
        ]
    ],
    
    // macOS paths (detected from diagnostic)
    'macos_paths' => [
        'imagemagick' => [
            '/opt/homebrew/bin/magick',
            '/usr/local/bin/magick',
            '/opt/local/bin/magick'
        ],
        'libreoffice' => [
            '/opt/homebrew/bin/soffice', // Homebrew installation - WORKING
            '/Applications/LibreOffice.app/Contents/MacOS/soffice', // App installation - WORKING
            '/usr/local/bin/soffice'
        ]
    ],
    
    // Linux/Unix common paths
    'unix_paths' => [
        'imagemagick' => [
            '/usr/bin/convert',
            '/usr/local/bin/convert',
            '/opt/local/bin/convert'
        ],
        'libreoffice' => [
            '/usr/bin/libreoffice',
            '/usr/local/bin/libreoffice',
            '/opt/libreoffice/program/soffice'
        ]
    ]
];