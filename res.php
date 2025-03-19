<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base directory where images are stored
$baseDir = __DIR__;

// Parse request URI to extract the image path
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Get the relative image path
$imagePath = str_replace($scriptName, '', $requestUri);
$imagePath = trim($imagePath, '/'); // Remove leading slash

// Get query parameters for width and height
$width = isset($_GET['w']) ? (int)$_GET['w'] : null;
$height = isset($_GET['h']) ? (int)$_GET['h'] : null;

// Full path to the image
$fullImagePath = $baseDir . DIRECTORY_SEPARATOR . $imagePath;

// Check if file exists
if (!file_exists($fullImagePath) || !is_file($fullImagePath)) {
    header("HTTP/1.1 404 Not Found");
    die("Error: Image not found.");
}

// Get image information
$imageInfo = getimagesize($fullImagePath);
if (!$imageInfo) {
    header("HTTP/1.1 400 Bad Request");
    die("Error: Invalid image file.");
}

list($originalWidth, $originalHeight, $imageType) = $imageInfo;

// Determine the new dimensions while maintaining aspect ratio
if ($width && !$height) {
    $height = intval(($width / $originalWidth) * $originalHeight);
} elseif ($height && !$width) {
    $width = intval(($height / $originalHeight) * $originalWidth);
} elseif (!$width && !$height) {
    $width = $originalWidth;
    $height = $originalHeight;
}

// Create a new image resource from the original
switch ($imageType) {
    case IMAGETYPE_JPEG:
        $sourceImage = imagecreatefromjpeg($fullImagePath);
        header("Content-Type: image/jpeg");
        break;
    case IMAGETYPE_PNG:
        $sourceImage = imagecreatefrompng($fullImagePath);
        header("Content-Type: image/png");
        break;
    case IMAGETYPE_GIF:
        $sourceImage = imagecreatefromgif($fullImagePath);
        header("Content-Type: image/gif");
        break;
    default:
        header("HTTP/1.1 400 Bad Request");
        die("Error: Unsupported image format.");
}

// Create a blank true color image
$resizedImage = imagecreatetruecolor($width, $height);

// Preserve transparency for PNG and GIF
if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
    imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
    imagealphablending($resizedImage, false);
    imagesavealpha($resizedImage, true);
}

// Resample the image
imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

// Output the resized image
switch ($imageType) {
    case IMAGETYPE_JPEG:
        imagejpeg($resizedImage);
        break;
    case IMAGETYPE_PNG:
        imagepng($resizedImage);
        break;
    case IMAGETYPE_GIF:
        imagegif($resizedImage);
        break;
}

// Free memory
imagedestroy($sourceImage);
imagedestroy($resizedImage);

?>
