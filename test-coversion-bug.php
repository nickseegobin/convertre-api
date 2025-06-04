#!/bin/bash
# Save this as test_config.php and run with: php test_config.php

cd /var/www/api

# Test 1: Basic config loading
echo "=== Testing Configuration Loading ==="
php -r "
\$config = include 'config/tools.php';
echo 'ImageMagick Path: ' . \$config['imagemagick']['binary_path'] . PHP_EOL;
echo 'LibreOffice Path: ' . \$config['libreoffice']['binary_path'] . PHP_EOL;
echo 'Platform: ' . \$config['detection_status']['platform'] . PHP_EOL;
echo 'ImageMagick Found: ' . (\$config['detection_status']['imagemagick_found'] ? 'YES' : 'NO') . PHP_EOL;
echo 'LibreOffice Found: ' . (\$config['detection_status']['libreoffice_found'] ? 'YES' : 'NO') . PHP_EOL;
"

echo ""
echo "=== Testing Convert Command ==="
php -r "
echo 'Testing convert command directly: ';
\$output = shell_exec('convert -version 2>&1');
if (\$output && strpos(\$output, 'ImageMagick') !== false) {
    echo 'SUCCESS' . PHP_EOL;
    echo 'Version: ' . trim(explode('\n', \$output)[0]) . PHP_EOL;
} else {
    echo 'FAILED' . PHP_EOL;
    echo 'Output: ' . \$output . PHP_EOL;
}
"

echo ""
echo "=== Testing LibreOffice Command ==="
php -r "
echo 'Testing libreoffice command: ';
\$output = shell_exec('libreoffice --version 2>&1');
if (\$output && strpos(\$output, 'LibreOffice') !== false) {
    echo 'SUCCESS' . PHP_EOL;
    echo 'Version: ' . trim(\$output) . PHP_EOL;
} else {
    echo 'FAILED' . PHP_EOL;
    echo 'Output: ' . \$output . PHP_EOL;
}
"