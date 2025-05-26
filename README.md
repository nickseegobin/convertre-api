# Convertre API - Complete Documentation

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [Features](#features)
3. [System Requirements](#system-requirements)
4. [File Structure](#file-structure)
5. [Installation & Setup](#installation--setup)
6. [API Reference](#api-reference)
7. [Authentication](#authentication)
8. [Error Handling](#error-handling)
9.  [Examples](#examples)
10. [Configuration](#configuration)
11. [Development Notes](#development-notes)

---

## 🎯 Project Overview

**Convertre API** is a scalable, modular REST API for file conversions. Built with PHP, it provides enterprise-level file conversion capabilities for both images and documents using industry-standard tools (ImageMagick and LibreOffice).

### **Current Version**: 1.0.0-MVP
### **Status**: Production Ready (MVP Complete)

### **Core Capabilities**
- **Image Conversions**: HEIC → JPG (via ImageMagick)
- **Document Conversions**: DOCX → PDF (via LibreOffice)
- **Batch Processing**: Up to 10 files per request
- **Secure Authentication**: API key-based system
- **File Management**: 3-hour automatic cleanup
- **Download System**: Secure file retrieval

---

## ✨ Features

### **🔐 Authentication System**
- API key generation and validation
- Usage tracking per key
- Session-based key storage
- Secure header-based authentication

### **📁 File Processing**
- **Single File Conversions**: Individual file processing
- **Batch Conversions**: Multiple files in one request
- **File Validation**: Size, type, and security checks
- **Automatic Cleanup**: 3-hour file retention policy

### **🔧 Conversion Modules**
- **ImageMagick Integration**: High-quality image conversions
- **LibreOffice Integration**: Professional document conversions
- **Modular Architecture**: Easy addition of new conversion types
- **Error Recovery**: Graceful handling of conversion failures

### **🌐 API Features**
- **RESTful Design**: Standard HTTP methods and status codes
- **JSON Responses**: Consistent response formatting
- **CORS Support**: Cross-origin request handling
- **Health Monitoring**: System status endpoints

---

## 🔧 System Requirements

### **PHP Requirements**
- **PHP**: 8.2 or higher
- **Extensions**: curl, fileinfo, json, mbstring, xml

### **External Tools**
- **ImageMagick**: 7.0+ with HEIC/HEIF support
- **LibreOffice**: 7.0+ with headless mode

### **Server Requirements**
- **Memory**: 2GB RAM (4GB recommended)
- **Storage**: 10GB available space
- **Web Server**: Apache or Nginx

### **macOS Setup** (Development)
```bash
# Install ImageMagick with HEIC support
brew install imagemagick

# Install LibreOffice
brew install --cask libreoffice

# Verify installations
magick -version
soffice --version
```

---

## 📂 File Structure

```
/convertre-api/
├── /public/                    # Web-accessible entry point
│   ├── index.php              # Main API router
│   └── .htaccess              # Apache rewrite rules
├── /src/                      # Core application code
│   ├── /Controllers/          # Request handling
│   │   ├── ConversionController.php
│   │   └── AuthController.php
│   ├── /Services/             # Business logic
│   │   ├── AbstractConversionModule.php
│   │   ├── /Modules/
│   │   │   ├── HeicToJpgModule.php
│   │   │   └── DocxToPdfModule.php
│   │   ├── AuthenticationService.php
│   │   ├── FileValidationService.php
│   │   ├── RateLimitService.php
│   │   ├── RequestValidator.php
│   │   ├── ConversionResult.php
│   │   └── ModuleFactory.php
│   ├── /Utils/                # Helper classes
│   │   ├── ConfigLoader.php
│   │   ├── ResponseFormatter.php
│   │   ├── Logger.php
│   │   ├── FileHandler.php
│   │   ├── ImageMagickChecker.php
│   │   └── LibreOfficeChecker.php
│   ├── /Middleware/           # Request middleware
│   │   └── ValidationMiddleware.php
│   └── /Exceptions/           # Custom exceptions
│       ├── ConversionException.php
│       ├── ValidationException.php
│       └── AuthenticationException.php
├── /config/                   # Configuration files
│   ├── api.php               # API settings
│   ├── conversions.php       # Conversion mappings
│   ├── limits.php            # File size & processing limits
│   └── tools.php             # Tool paths & settings
├── /storage/                  # File storage
│   ├── /uploads/             # Temporary upload storage
│   ├── /converted/           # Processed files (3-hour retention)
│   └── /logs/                # Application logs
└── /tests/                   # Test files
    └── test_docx.php         # Conversion testing script
```

---

## 🚀 Installation & Setup

### **1. Clone & Setup**
```bash
# Clone repository
git clone [repository-url] convertre-api
cd convertre-api

# Set permissions
chmod 755 public/
chmod 777 storage/
chmod 777 storage/uploads/
chmod 777 storage/converted/
chmod 777 storage/logs/
```

### **2. Web Server Configuration**

#### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### **Nginx**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### **3. Configuration**
Update `/config/tools.php` for your environment:
```php
'imagemagick' => [
    'binary_path' => 'magick', // or full path
],
'libreoffice' => [
    'binary_path' => 'soffice', // or full path
]
```

---

## 🔌 API Reference

### **Base URL**
```
http://localhost/convertre-api/public/
```

### **Authentication**
All conversion endpoints require API key in header:
```
X-API-Key: your_generated_api_key
```

---

### **📋 Endpoints**

#### **GET /info**
Get API information and available endpoints.

**Response:**
```json
{
  "success": true,
  "name": "Convertre API",
  "version": "1.0.0-MVP",
  "status": "running",
  "endpoints": {
    "GET /info": "API information",
    "GET /health": "Health check",
    "POST /generate-key": "Generate API key",
    "POST /convert": "Single file conversion",
    "POST /convert-batch": "Batch file conversion"
  }
}
```

#### **GET /health**
Check system health and status.

**Response:**
```json
{
  "success": true,
  "status": "ok",
  "timestamp": "2025-05-26T10:30:00Z",
  "version": "1.0.0-MVP"
}
```

#### **POST /generate-key**
Generate a new API key for authentication.

**Parameters:**
- `user_id` (optional): User identifier
- `name` (optional): Key name/description

**Response:**
```json
{
  "success": true,
  "api_key": "ck_c309c4402f3357266db931e7bb8b7f53",
  "user_id": "test123",
  "name": "Test Key",
  "created_at": "2025-05-26 13:39:47"
}
```

#### **POST /validate-key**
Validate an existing API key.

**Parameters:**
- `api_key`: The API key to validate

**Response:**
```json
{
  "success": true,
  "valid": true,
  "user_id": "test123",
  "usage_count": 5
}
```

#### **POST /convert**
Convert a single file.

**Headers:**
- `X-API-Key`: Your API key

**Parameters:**
- `file`: File to convert (form-data)
- `to`: Target format (`jpg` or `pdf`)

**Response:**
```json
{
  "success": true,
  "download_url": "http://localhost/convertre-api/public/download/sample1_1748267833_dae05718.jpg",
  "original_filename": "sample1.heic",
  "converted_filename": "sample1_1748267833_dae05718.jpg",
  "expires_at": "2025-05-26T16:42:10+00:00",
  "processing_time": "0.519s",
  "file_size": "487967 bytes",
  "conversion": "heic → jpg"
}
```

#### **POST /convert-batch**
Convert multiple files in one request.

**Headers:**
- `X-API-Key`: Your API key

**Parameters:**
- `file1`: First file (form-data)
- `file2`: Second file (form-data)
- `file3`: Third file (form-data) - up to 10 files
- `to`: Target format (`jpg` or `pdf`)

**Response:**
```json
{
  "success": true,
  "files": [
    {
      "success": true,
      "original_filename": "sample1.heic",
      "converted_filename": "sample1_1748267833_dae05718.jpg",
      "download_url": "http://localhost/convertre-api/public/download/sample1_1748267833_dae05718.jpg",
      "processing_time": "0.444s",
      "file_size": "487967 bytes"
    },
    {
      "success": true,
      "original_filename": "sample2.heic",
      "converted_filename": "sample2_1748267833_90e1b656.jpg",
      "download_url": "http://localhost/convertre-api/public/download/sample2_1748267833_90e1b656.jpg",
      "processing_time": "0.521s",
      "file_size": "523441 bytes"
    }
  ],
  "count": 2,
  "total_files": 2,
  "successful_conversions": 2,
  "failed_conversions": 0,
  "total_processing_time": "0.965s",
  "conversion_type": "jpg",
  "expires_at": "2025-05-26T16:42:10+00:00"
}
```

#### **GET /download/{filename}**
Download a converted file.

**URL Parameters:**
- `filename`: The converted filename from conversion response

**Response:**
- **Success**: File download (binary)
- **Error**: JSON error response

---

## 🔐 Authentication

### **API Key System**
The API uses a simple API key system for authentication.

### **Getting an API Key**
```bash
curl -X POST http://localhost/convertre-api/public/generate-key \
  -d "user_id=myapp&name=My Application"
```

### **Using API Keys**
Include the API key in request headers:
```bash
curl -X POST http://localhost/convertre-api/public/convert \
  -H "X-API-Key: your_api_key_here" \
  -F "file=@photo.heic" \
  -F "to=jpg"
```

### **Key Features**
- **Usage Tracking**: Keys track conversion count
- **Session Storage**: Keys stored in PHP sessions (development)
- **Validation**: Real-time key validation on requests

---

## ⚠️ Error Handling

### **Standard Error Response**
```json
{
  "success": false,
  "error": "Error description",
  "error_code": "ERROR_CODE"
}
```

### **Common Error Codes**
- `UNAUTHORIZED` (401): Missing or invalid API key
- `VALIDATION_ERROR` (400): Invalid file or parameters
- `UNSUPPORTED_FORMAT` (400): File format not supported
- `FILE_TOO_LARGE` (413): File exceeds size limits
- `RATE_LIMIT_EXCEEDED` (429): Too many requests
- `CONVERSION_FAILED` (500): Conversion process failed
- `NOT_FOUND` (404): File or endpoint not found
- `METHOD_NOT_ALLOWED` (405): Wrong HTTP method

### **File Validation Errors**
- **Unsupported Format**: File type not supported
- **File Too Large**: Exceeds 50MB limit
- **File Too Small**: Below 1KB minimum
- **Upload Error**: File upload failed
- **Security Error**: Executable files blocked

---

## 📚 Examples

### **Example 1: Single HEIC to JPG Conversion**
```bash
# 1. Generate API key
curl -X POST http://localhost/convertre-api/public/generate-key \
  -d "user_id=example&name=Test App"

# Response: {"success":true,"api_key":"ck_abc123..."}

# 2. Convert file
curl -X POST http://localhost/convertre-api/public/convert \
  -H "X-API-Key: ck_abc123..." \
  -F "file=@photo.heic" \
  -F "to=jpg"

# 3. Download result
curl -O http://localhost/convertre-api/public/download/photo_123456_abc.jpg
```

### **Example 2: Batch DOCX to PDF Conversion**
```bash
curl -X POST http://localhost/convertre-api/public/convert-batch \
  -H "X-API-Key: ck_abc123..." \
  -F "file1=@document1.docx" \
  -F "file2=@document2.docx" \
  -F "to=pdf"
```

### **Example 3: JavaScript Integration**
```javascript
// Generate API key
const generateKey = async () => {
  const response = await fetch('http://localhost/convertre-api/public/generate-key', {
    method: 'POST',
    body: new FormData([
      ['user_id', 'webapp'],
      ['name', 'Web Application']
    ])
  });
  const data = await response.json();
  return data.api_key;
};

// Convert file
const convertFile = async (file, apiKey) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('to', 'jpg');
  
  const response = await fetch('http://localhost/convertre-api/public/convert', {
    method: 'POST',
    headers: {
      'X-API-Key': apiKey
    },
    body: formData
  });
  
  return await response.json();
};
```

### **Example 4: PHP Integration**
```php
// Generate API key
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/convertre-api/public/generate-key');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'user_id=phpapp&name=PHP Application');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$keyData = json_decode($result, true);
$apiKey = $keyData['api_key'];

// Convert file
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/convertre-api/public/convert');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $apiKey"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new CURLFile('/path/to/file.heic'),
    'to' => 'jpg'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$conversionData = json_decode($result, true);
```

---

## ⚙️ Configuration

### **API Configuration** (`/config/api.php`)
```php
return [
    'rate_limit' => [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000
    ],
    'download' => [
        'base_url' => 'http://localhost/convertre-api/public/download',
        'expiry_hours' => 3
    ],
    'limits' => [
        'batch_max_files' => 10,
        'max_concurrent_conversions' => 5
    ]
];
```

### **File Limits** (`/config/limits.php`)
```php
return [
    'file_size' => [
        'max_upload_size' => 50 * 1024 * 1024, // 50MB
        'max_image_size' => 25 * 1024 * 1024,  // 25MB
        'max_document_size' => 50 * 1024 * 1024, // 50MB
        'min_file_size' => 1024 // 1KB
    ],
    'timeouts' => [
        'image_conversion' => 60,    // 1 minute
        'document_conversion' => 300, // 5 minutes
        'batch_processing' => 600    // 10 minutes
    ]
];
```

### **Tool Configuration** (`/config/tools.php`)
```php
return [
    'imagemagick' => [
        'binary_path' => 'magick',
        'timeout' => 60,
        'quality_settings' => [
            'jpg' => 85,
            'webp' => 80,
            'png' => 9
        ]
    ],
    'libreoffice' => [
        'binary_path' => 'soffice',
        'timeout' => 300,
        'common_options' => [
            '--headless',
            '--invisible',
            '--nodefault',
            '--nolockcheck'
        ]
    ]
];
```

---

## 🧪 Development Notes

### **Testing**
```bash
# Test DOCX conversion
php test_docx.php

# Test API endpoints
curl -X GET http://localhost/convertre-api/public/info

# Debug file uploads
curl -X POST http://localhost/convertre-api/public/convert-batch?debug=1
```

### **Logging**
Logs are stored in `/storage/logs/convertre-YYYY-MM-DD.log`:
```bash
# View today's logs
tail -f storage/logs/convertre-$(date +%Y-%m-%d).log

# Search for errors
grep ERROR storage/logs/convertre-*.log
```

### **Troubleshooting**

#### **ImageMagick Issues**
```bash
# Check ImageMagick installation
magick -version

# Test HEIC support
magick identify sample.heic
```

#### **LibreOffice Issues**
```bash
# Check LibreOffice installation
soffice --version

# Test headless conversion
soffice --headless --convert-to pdf --outdir /tmp document.docx
```

#### **Permission Issues**
```bash
# Fix storage permissions
chmod -R 777 storage/
chown -R www-data:www-data storage/
```

### **Performance Optimization**
- **File Cleanup**: Automatic 3-hour cleanup prevents storage bloat
- **Process Isolation**: Each conversion runs independently
- **Memory Management**: Proper cleanup of temporary files
- **Error Recovery**: Failed conversions don't affect other files

---

## 🚀 Production Deployment

### **Environment Variables**
```bash
# Set production environment
export APP_ENV=production
export APP_DEBUG=false
```

### **Security Considerations**
- **File Validation**: Strict MIME type checking
- **Path Sanitization**: Prevents directory traversal
- **Upload Limits**: Enforced file size restrictions
- **Executable Blocking**: Prevents malicious file uploads

### **Monitoring**
- **Health Endpoint**: `/health` for uptime monitoring
- **Log Analysis**: Structured logging for monitoring tools
- **Error Tracking**: Comprehensive error logging

---

## 📈 Roadmap

### **Phase 6: File Management & Cleanup**
- Automatic file cleanup system
- Storage quota monitoring
- Advanced file retention policies

### **Phase 7: Additional Conversions**
- JPG ↔ PNG, WEBP conversions
- Additional document formats (DOC, ODT, XLSX, PPTX)
- PDF to image conversions

### **Phase 8: Advanced Features**
- Asynchronous processing
- Webhook notifications
- Advanced analytics
- SDK development

---

## 📞 Support

### **Documentation Updates**
This documentation reflects the current MVP implementation (Phase 5 complete).

### **Version Information**
- **API Version**: 1.0.0-MVP
- **PHP Version**: 8.2+
- **ImageMagick**: 7.0+
- **LibreOffice**: 25.2.3.2+

### **Development Status**
✅ **Complete**: Authentication, Single/Batch Conversions, Download System  
🔄 **In Progress**: File Cleanup, Additional Formats  
📋 **Planned**: Advanced Features, Production Optimization

---

*Last Updated: May 26, 2025*