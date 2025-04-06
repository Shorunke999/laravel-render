<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Exception;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(config('cloudinary'));
    }

    /**
     * Upload a file to Cloudinary
     *
     * @param string $filePath Path to the file
     * @param string $folder Folder in Cloudinary
     * @param string|null $publicId Custom public ID (optional)
     * @param array $options Additional upload options
     * @return array Upload result
     * @throws Exception
     */
    public function upload(string $filePath, string $folder, ?string $publicId = null, array $options = [])
    {
        try {
            $uploadOptions = array_merge([
                'folder' => $folder,
            ], $options);

            if ($publicId) {
                $uploadOptions['public_id'] = $publicId;
            }

            return $this->cloudinary->uploadApi()->upload($filePath, $uploadOptions);
        } catch (Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a file from Cloudinary
     *
     * @param string $publicId Public ID of the file to delete
     * @param array $options Additional delete options
     * @return array Delete result
     * @throws Exception
     */
    public function delete(string $publicId, array $options = [])
    {
        try {
            return $this->cloudinary->uploadApi()->destroy($publicId, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary delete error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Move/Rename a file in Cloudinary
     *
     * @param string $fromPublicId Source public ID
     * @param string $toPublicId Destination public ID
     * @param array $options Additional options
     * @return array Rename result
     * @throws Exception
     */
    public function move(string $fromPublicId, string $toPublicId, array $options = [])
    {
        try {
            return $this->cloudinary->uploadApi()->rename($fromPublicId, $toPublicId, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary move error: ' . $e->getMessage());
            throw $e;
        }
    }
}