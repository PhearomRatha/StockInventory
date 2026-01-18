<?php

namespace App\Helpers;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class ImageHelper
{
    /**
     * Upload image to Cloudinary
     */
    public static function uploadToCloudinary($file, $folder = 'products')
    {
        try {
            $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                'folder' => $folder
            ]);
            return $uploadedFile->getSecurePath();
        } catch (\Exception $e) {
            throw new \Exception('Image upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete image from Cloudinary
     */
    public static function deleteFromCloudinary($imageUrl, $folder = 'products')
    {
        try {
            if (empty($imageUrl)) {
                return true;
            }

            $path = parse_url($imageUrl, PHP_URL_PATH);
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $folderPath = dirname($path);
            $publicId = trim($folderPath, '/') . '/' . $filename;

            Cloudinary::destroy($publicId);
            return true;
        } catch (\Exception $e) {
            // Log error but don't throw - deletion failure shouldn't break the flow
            Log::warning('Cloudinary delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get image URL (handle both Cloudinary and local storage)
     */
    public static function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return 'https://via.placeholder.com/300x300.png?text=No+Image';
        }

        // If it's already a full URL (Cloudinary), return as is
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        // Otherwise, assume it's in storage
        return url('storage/' . $imagePath);
    }
}