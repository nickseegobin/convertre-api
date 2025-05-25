<?php

/**
 * File Size and Processing Limits Configuration
 */

return [
    // File Size Limits (in bytes)
    'file_size' => [
        'max_upload_size' => 50 * 1024 * 1024, // 50MB
        'max_image_size' => 25 * 1024 * 1024,  // 25MB for images
        'max_document_size' => 50 * 1024 * 1024, // 50MB for documents
        'min_file_size' => 1024 // 1KB minimum
    ],
    
    // Processing Timeouts (in seconds)
    'timeouts' => [
        'image_conversion' => 60,    // 1 minute for image conversion
        'document_conversion' => 300, // 5 minutes for document conversion
        'batch_processing' => 600    // 10 minutes for batch operations
    ],
    
    // Storage Limits
    'storage' => [
        'max_disk_usage' => 10 * 1024 * 1024 * 1024, // 10GB total storage
        'cleanup_interval_hours' => 1, // Run cleanup every hour
        'file_retention_hours' => 3    // Keep files for 3 hours
    ],
    
    // Image Specific Limits
    'image' => [
        'max_width' => 10000,
        'max_height' => 10000,
        'max_pixels' => 50000000, // 50 megapixels
        'quality' => [
            'jpg' => 85,
            'webp' => 80,
            'png' => 9 // PNG compression level
        ]
    ],
    
    // Document Specific Limits
    'document' => [
        'max_pages' => 500,
        'max_characters' => 1000000 // 1 million characters
    ]
];