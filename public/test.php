<?php
phpinfo();

echo "<h2>Tool Tests:</h2>";

// Test ImageMagick
$magick = shell_exec('magick --version 2>&1');
echo "<h3>ImageMagick:</h3><pre>" . $magick . "</pre>";

// Test LibreOffice
$libreoffice = shell_exec('/Applications/LibreOffice.app/Contents/MacOS/soffice --version 2>&1');
echo "<h3>LibreOffice:</h3><pre>" . $libreoffice . "</pre>";

// Test HEIC support
$heicSupport = shell_exec('magick identify -list format | grep -i heic 2>&1');
echo "<h3>HEIC Support:</h3><pre>" . ($heicSupport ? $heicSupport : 'HEIC support not found') . "</pre>";
?>
