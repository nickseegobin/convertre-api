<?php

/**
 * Helper script to create necessary directories for Phase 2.1
 * Run this once to set up the directory structure
 */

$directories = [
    __DIR__ . '/src/Services',
    __DIR__ . '/src/Controllers', 
    __DIR__ . '/src/Middleware'
];

echo "Creating directories for Phase 2.1...\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Created: {$dir}\n";
    } else {
        echo "✓ Exists: {$dir}\n";
    }
}

echo "\nDirectories ready for Phase 2.1 files!\n";