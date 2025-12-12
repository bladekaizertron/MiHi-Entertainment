<?php
/**
 * Image Resize and Compression Helper
 * Automatically resizes and compresses images to reduce file size
 */

/**
 * Resize and compress an image
 * 
 * @param string $sourcePath Path to source image
 * @param string $destinationPath Path to save resized image
 * @param int $maxWidth Maximum width in pixels (default: 1920)
 * @param int $maxHeight Maximum height in pixels (default: 1920)
 * @param int $quality JPEG quality 0-100 (default: 85)
 * @return bool True on success, false on failure
 */
function resizeAndCompressImage($sourcePath, $destinationPath, $maxWidth = 1920, $maxHeight = 1920, $quality = 85) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log("GD library not available. Image will not be resized. Install php-gd extension.");
        return false;
    }
    
    // Check if source file exists
    if (!file_exists($sourcePath)) {
        error_log("Source image file does not exist: " . $sourcePath);
        return false;
    }
    
    // Get image info
    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        error_log("Could not get image info for: " . $sourcePath);
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Only process if image is larger than max dimensions
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        // Image is already small enough, just copy it
        return copy($sourcePath, $destinationPath);
    }
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);
    
    // Create image resource based on type
    $sourceImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = @imagecreatefromwebp($sourcePath);
            }
            break;
        default:
            error_log("Unsupported image type: " . $mimeType);
            return false;
    }
    
    if ($sourceImage === false) {
        error_log("Could not create image resource from: " . $sourcePath);
        return false;
    }
    
    // Create new image with calculated dimensions
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($newImage === false) {
        imagedestroy($sourceImage);
        error_log("Could not create new image resource");
        return false;
    }
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    if (!imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight)) {
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        error_log("Could not resize image");
        return false;
    }
    
    // Save resized image
    $success = false;
    $ext = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $success = imagejpeg($newImage, $destinationPath, $quality);
            break;
        case 'png':
            // PNG compression level (0-9, where 9 is highest compression)
            $pngQuality = 9 - round($quality / 11.11); // Convert 0-100 to 9-0
            $success = imagepng($newImage, $destinationPath, $pngQuality);
            break;
        case 'gif':
            $success = imagegif($newImage, $destinationPath);
            break;
        case 'webp':
            if (function_exists('imagewebp')) {
                $success = imagewebp($newImage, $destinationPath, $quality);
            }
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    if (!$success) {
        error_log("Could not save resized image to: " . $destinationPath);
        return false;
    }
    
    // Log size reduction
    $originalSize = filesize($sourcePath);
    $newSize = filesize($destinationPath);
    $reduction = round((1 - $newSize / $originalSize) * 100, 1);
    error_log("Image resized: {$originalWidth}x{$originalHeight} -> {$newWidth}x{$newHeight}, Size: " . 
              round($originalSize / 1024, 2) . " KB -> " . round($newSize / 1024, 2) . " KB ({$reduction}% reduction)");
    
    return true;
}

/**
 * Get optimal image dimensions based on usage
 * 
 * @param string $usage 'content' for editor images, 'thumbnail' for thumbnails
 * @return array ['width' => int, 'height' => int, 'quality' => int]
 */
function getOptimalImageSettings($usage = 'content') {
    switch ($usage) {
        case 'thumbnail':
            return ['width' => 800, 'height' => 800, 'quality' => 80];
        case 'content':
        default:
            return ['width' => 1920, 'height' => 1920, 'quality' => 85];
    }
}

