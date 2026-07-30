<?php
$src = 'assets/images/zalo_qr.png';
$dest = 'assets/images/zalo_qr_cropped.png';

$img = @imagecreatefrompng($src);
if ($img !== false) {
    // Width is 471. Let's crop a square of 370x370.
    // Center it horizontally: (471 - 370) / 2 = 50.
    // Vertically it starts near the top, around 160px down.
    $rect = ['x' => 50, 'y' => 160, 'width' => 371, 'height' => 371];
    
    $cropped = imagecrop($img, $rect);
    if ($cropped !== false) {
        imagepng($cropped, $dest);
        imagedestroy($cropped);
        echo "Cropped successfully.\n";
    } else {
        echo "Failed to crop.\n";
    }
    imagedestroy($img);
} else {
    echo "Failed to load image.\n";
}
