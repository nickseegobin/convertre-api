<?php

/**
 * Supported Conversion Mappings Configuration
 * 
 * Maps source formats to target formats and their processing modules
 * Based on the 15 conversion types specified in the project document
 */

return [
    // ImageMagick-based conversions
    'image_conversions' => [
        'heic' => [
            'targets' => ['jpg', 'png', 'pdf'],
            'module' => 'HeicToJpgModule', // Will expand to support multiple targets
            'tool' => 'imagemagick',
            'mime_types' => ['image/heic', 'image/heif']
        ],
        'jpg' => [
            'targets' => ['png', 'webp', 'pdf'],
            'module' => 'JpgConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/jpeg', 'image/jpg']
        ],
        'png' => [
            'targets' => ['jpg', 'webp', 'pdf'],
            'module' => 'PngConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/png']
        ],
        'webp' => [
            'targets' => ['jpg', 'png', 'pdf'],
            'module' => 'WebpConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/webp']
        ],
        'gif' => [
            'targets' => ['jpg', 'png', 'pdf'],
            'module' => 'GifConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/gif']
        ],
        'bmp' => [
            'targets' => ['jpg', 'png'],
            'module' => 'BmpConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/bmp']
        ],
        'tiff' => [
            'targets' => ['jpg', 'png'],
            'module' => 'TiffConversionModule',
            'tool' => 'imagemagick',
            'mime_types' => ['image/tiff', 'image/tif']
        ],
        'pdf' => [
            'targets' => ['jpg', 'png'],
            'module' => 'PdfToImageModule',
            'tool' => 'imagemagick',
            'mime_types' => ['application/pdf']
        ]
    ],
    
    // LibreOffice-based conversions (all to PDF)
    'document_conversions' => [
        'doc' => [
            'targets' => ['pdf'],
            'module' => 'DocToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/msword']
        ],
        'docx' => [
            'targets' => ['pdf'],
            'module' => 'DocxToPdfModule', // MVP module
            'tool' => 'libreoffice',
            'mime_types' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ],
        'odt' => [
            'targets' => ['pdf'],
            'module' => 'OdtToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/vnd.oasis.opendocument.text']
        ],
        'xlsx' => [
            'targets' => ['pdf'],
            'module' => 'XlsxToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        ],
        'pptx' => [
            'targets' => ['pdf'],
            'module' => 'PptxToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation']
        ],
        'epub' => [
            'targets' => ['pdf'],
            'module' => 'EpubToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/epub+zip']
        ],
        'rtf' => [
            'targets' => ['pdf'],
            'module' => 'RtfToPdfModule',
            'tool' => 'libreoffice',
            'mime_types' => ['application/rtf', 'text/rtf']
        ]
    ],
    
    // MVP Priority (Phase 4 - only these two will be implemented initially)
    'mvp_conversions' => [
        'heic' => 'jpg',
        'docx' => 'pdf'
    ],
    
    // Helper function to get all supported input formats
    'supported_input_formats' => function() {
        $config = include __FILE__;
        $imageFormats = array_keys($config['image_conversions']);
        $documentFormats = array_keys($config['document_conversions']);
        return array_merge($imageFormats, $documentFormats);
    },
    
    // Helper function to check if conversion is supported
    'is_conversion_supported' => function(string $from, string $to) {
        $config = include __FILE__;
        
        // Check image conversions
        if (isset($config['image_conversions'][$from])) {
            return in_array($to, $config['image_conversions'][$from]['targets']);
        }
        
        // Check document conversions
        if (isset($config['document_conversions'][$from])) {
            return in_array($to, $config['document_conversions'][$from]['targets']);
        }
        
        return false;
    }
];