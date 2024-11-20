<?php
header('Content-Type: image/png');
$width = 300;
$height = 300;

// Create image
$image = imagecreatetruecolor($width, $height);

// Set background color (light gray)
$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bg_color);

// Text color
$text_color = imagecolorallocate($image, 100, 100, 100);

// Font path (using a default system font)
$font = 'c:/windows/fonts/arial.ttf';

// Add text
$text = "Product Image";
$font_size = 20;
$bbox = imagettfbbox($font_size, 0, $font, $text);
$x = ($width - $bbox[2]) / 2;
$y = ($height - $bbox[7]) / 2;
imagettftext($image, $font_size, 0, $x, $y, $text_color, $font, $text);

// Output image
imagepng($image);
imagedestroy($image);
?>
