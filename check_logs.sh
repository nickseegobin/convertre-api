#!/bin/bash
# Save this as: /var/www/api/check_logs.sh
# Make executable with: chmod +x check_logs.sh
# Run with: ./check_logs.sh

echo "=== Convertre API Log Analysis ==="
echo "Generated at: $(date)"
echo "========================================"

cd /var/www/api

echo -e "\n=== 1. API Application Logs ==="
if [ -d "storage/logs" ]; then
    echo "✅ API log directory found:"
    ls -la storage/logs/
    echo ""
    
    # Show recent API logs
    echo "📋 Recent API logs (last 50 lines):"
    find storage/logs/ -name "*.log" -type f -exec echo "--- {} ---" \; -exec tail -n 50 {} \; 2>/dev/null
else
    echo "❌ No API logs directory found at storage/logs/"
    echo "   Creating logs directory..."
    mkdir -p storage/logs
    chmod 775 storage/logs
    chown www-data:www-data storage/logs
fi

echo -e "\n=== 2. PHP Error Logs ==="
php_error_logs=(
    "/var/log/php8.3-fpm.log"
    "/var/log/php-fpm.log" 
    "/var/log/php/error.log"
    "/var/log/php_errors.log"
    "/var/log/apache2/error.log"
)

for log in "${php_error_logs[@]}"; do
    if [ -f "$log" ]; then
        echo "✅ Found PHP/Web log: $log"
        echo "📋 Recent errors (last 20 lines):"
        sudo tail -n 20 "$log" | grep -E "(error|warning|fatal)" --color=never || echo "   No recent errors found"
        echo ""
    fi
done

echo -e "\n=== 3. Nginx Error Logs ==="
nginx_logs=(
    "/var/log/nginx/error.log"
    "/var/log/nginx/access.log"
)

for log in "${nginx_logs[@]}"; do
    if [ -f "$log" ]; then
        echo "✅ Found Nginx log: $log"
        echo "📋 Recent entries (last 10 lines):"
        sudo tail -n 10 "$log"
        echo ""
    fi
done

echo -e "\n=== 4. System Logs (LibreOffice Related) ==="
echo "🔍 Checking for LibreOffice errors in system logs:"
sudo journalctl --since "1 hour ago" --no-pager | grep -i libre | tail -n 10 || echo "   No LibreOffice entries found"

echo -e "\n=== 5. Check for Recent Crashes ==="
echo "🔍 Checking for segfaults or crashes:"
sudo dmesg | tail -n 20 | grep -E "(segfault|killed|error|libre)" || echo "   No recent crashes found"

echo -e "\n=== 6. File Permissions Check ==="
echo "📁 Checking API directory permissions:"
ls -la storage/
echo ""
echo "👤 Current process info:"
echo "   User: $(whoami)"
echo "   Groups: $(groups)"

echo -e "\n=== 7. LibreOffice Process Check ==="
echo "🔍 Checking for running LibreOffice processes:"
ps aux | grep -i libre | grep -v grep || echo "   No LibreOffice processes running"

echo -e "\n========================================"
echo "✅ Log analysis complete!"
echo ""
echo "🔧 To monitor logs in real-time while testing:"
echo "   Terminal 1: sudo tail -f /var/log/nginx/error.log"
echo "   Terminal 2: sudo tail -f /var/log/php8.3-fpm.log"
echo "   Terminal 3: tail -f /var/www/api/storage/logs/*.log"
echo "========================================"