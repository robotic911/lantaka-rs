<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('media_disk')) {
    /**
     * Return the name of the disk used for all user-uploaded media.
     * Locally:     'public'  (files served via storage symlink)
     * Production:  's3'      (Laravel Cloud / S3-compatible object storage)
     *
     * Controlled by the MEDIA_DISK environment variable.
     */
    function media_disk(): string
    {
        return config('filesystems.media_disk', 'public');
    }
}

if (! function_exists('media_url')) {
    /**
     * Generate a public URL for a stored media file.
     *
     * Usage in Blade:  {{ media_url($room->Room_Image) }}
     * Replaces:        asset('storage/' . $room->Room_Image)
     *
     * On the 'public' disk this returns:  APP_URL/storage/path/to/file
     * On the 's3'     disk this returns:  https://bucket.s3.region.amazonaws.com/path/to/file
     */
    function media_url(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return Storage::disk(media_disk())->url($path);
    }
}
