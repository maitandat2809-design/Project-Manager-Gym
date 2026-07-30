<?php
function fixPaths($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // If the filename contains a backslash
        if (strpos($file, '\\') !== false) {
            $oldPath = $dir . '/' . $file;
            $newRelativePath = str_replace('\\', '/', $file);
            $newPath = $dir . '/' . $newRelativePath;
            
            // Create directories if they don't exist
            $newDir = dirname($newPath);
            if (!file_exists($newDir)) {
                mkdir($newDir, 0755, true);
            }
            
            // Move file
            rename($oldPath, $newPath);
            echo "Moved: $file -> $newPath <br>";
        }
    }
}

fixPaths(__DIR__);
echo "Path fixing complete! Please refresh the page.";
@unlink(__FILE__);
?>
