<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    protected string $disk;
    protected array $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    protected array $allowedAudioTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
    protected int $maxImageSize = 5120; // 5MB in KB
    protected int $maxAudioSize = 10240; // 10MB in KB
    protected int $imageMaxWidth = 1920;
    protected int $imageMaxHeight = 1080;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public');
    }

    /**
     * Upload and process an image.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return array
     */
    public function uploadImage(UploadedFile $file, string $folder = 'posts'): array
    {
        // Validate file type
        if (!in_array($file->getMimeType(), $this->allowedImageTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP',
            ];
        }

        // Validate file size
        if ($file->getSize() > $this->maxImageSize * 1024) {
            return [
                'success' => false,
                'message' => "Image size exceeds maximum allowed size of {$this->maxImageSize}KB",
            ];
        }

        try {
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "{$folder}/{$filename}";

            // Check if Intervention Image is available
            if (class_exists(\Intervention\Image\Facades\Image::class)) {
                // Resize and optimize image using Intervention Image
                $image = \Intervention\Image\Facades\Image::make($file);
                $image->resize($this->imageMaxWidth, $this->imageMaxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Save to storage
                Storage::disk($this->disk)->put($path, (string) $image->encode());
            } else {
                // Fallback: store file directly without resizing
                Storage::disk($this->disk)->putFileAs($folder, $file, $filename);
            }

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk($this->disk)->url($path),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload an audio file.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return array
     */
    public function uploadAudio(UploadedFile $file, string $folder = 'posts'): array
    {
        // Validate file type
        if (!in_array($file->getMimeType(), $this->allowedAudioTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid audio type. Allowed: MP3, WAV, OGG',
            ];
        }

        // Validate file size
        if ($file->getSize() > $this->maxAudioSize * 1024) {
            return [
                'success' => false,
                'message' => "Audio size exceeds maximum allowed size of {$this->maxAudioSize}KB",
            ];
        }

        try {
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "{$folder}/{$filename}";

            // Store file
            Storage::disk($this->disk)->putFileAs($folder, $file, $filename);

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk($this->disk)->url($path),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload audio: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload profile image.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function uploadProfileImage(UploadedFile $file): array
    {
        return $this->uploadImage($file, 'profiles');
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($path)) {
                return Storage::disk($this->disk)->delete($path);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

