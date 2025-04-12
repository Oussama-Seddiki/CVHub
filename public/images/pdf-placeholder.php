<?php
// This script generates PDF placeholder images dynamically

// Enable caching for these images
header('Cache-Control: public, max-age=86400'); // Cache for 1 day
header('Content-Type: image/png');

// Get page number from query string or default to 1
$pageNumber = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Create an image
$width = 210;
$height = 297;
$image = imagecreatetruecolor($width, $height);

// Set colors
$bgColor = imagecolorallocate($image, 245, 246, 250);
$textColor = imagecolorallocate($image, 80, 80, 80);
$borderColor = imagecolorallocate($image, 200, 200, 200);
$pageNumBgColor = imagecolorallocate($image, 230, 230, 230);
$pdfIconColor = imagecolorallocate($image, 220, 53, 69); // Red color for PDF icon

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Draw border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

// Draw PDF icon
$iconSize = 50;
$iconX = ($width - $iconSize) / 2;
$iconY = ($height - $iconSize) / 2 - 30;

// Draw stylized PDF file icon
imagefilledrectangle($image, $iconX, $iconY, $iconX + $iconSize, $iconY + $iconSize, $pdfIconColor);
imagefilledrectangle($image, $iconX + 15, $iconY - 10, $iconX + $iconSize - 5, $iconY, $pdfIconColor);
imagefilledrectangle($image, $iconX + $iconSize - 15, $iconY - 10, $iconX + $iconSize, $iconY + 15, $pdfIconColor);

// Add PDF text
$pdfText = "PDF";
$fontSize = 5;
$textX = $iconX + 10;
$textY = $iconY + 30;
imagestring($image, $fontSize, $textX, $textY, $pdfText, $bgColor);

// Add page number area at bottom
$pageNumY = $height - 40;
$pageNumHeight = 30;
$pageNumWidth = 80;
$pageNumX = ($width - $pageNumWidth) / 2;
imagefilledrectangle($image, $pageNumX, $pageNumY, $pageNumX + $pageNumWidth, $pageNumY + $pageNumHeight, $pageNumBgColor);
imagerectangle($image, $pageNumX, $pageNumY, $pageNumX + $pageNumWidth, $pageNumY + $pageNumHeight, $borderColor);

// Add page number text
$pageText = "Page " . $pageNumber;
$textWidth = strlen($pageText) * imagefontwidth(3);
$textX = $pageNumX + ($pageNumWidth - $textWidth) / 2;
$textY = $pageNumY + 10;
imagestring($image, 3, $textX, $textY, $pageText, $textColor);

// Set quality - balance between file size and appearance
$quality = 9; // PNG compression level (0-9, where 9 is highest compression)

// Output the image with compression
imagepng($image, null, $quality);
imagedestroy($image); 