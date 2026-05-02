<?php
$dir = __DIR__ . '/../public_html';

function process_directory($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            process_directory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            $originalContent = $content;
            
            // Replace <a ... onclick="return confirm('MSG')"> 
            // We'll use regex to catch different quotes if needed
            $content = preg_replace(
                '/onclick="return confirm\(\'([^\']+)\'\)"/',
                'onclick="confirmAction(event, this.href, \'$1\')"',
                $content
            );
            
            // Replace <form ... onsubmit="return confirm('MSG')">
            $content = preg_replace(
                '/onsubmit="return confirm\(\'([^\']+)\'\)"/',
                'onsubmit="confirmFormSubmit(event, this, \'$1\')"',
                $content
            );

            if ($content !== $originalContent) {
                file_put_contents($path, $content);
                echo "Updated $path\n";
            }
        }
    }
}

process_directory($dir);
echo "Done replacing standard confirm tags.\n";
