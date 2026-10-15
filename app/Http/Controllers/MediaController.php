<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve a file from the "public" storage disk directly through Laravel,
     * instead of relying on the public/storage symlink. Some hosts (shared
     * plans without symlink support or SSH/Terminal access to re-create a
     * broken one) can never serve files through that symlink reliably, so
     * every avatar/logo/product image URL is generated to point here
     * instead — see config/filesystems.php's "public" disk "url".
     */
    public function show(string $path): StreamedResponse
    {
        // Flysystem already normalizes and rejects directory-traversal
        // segments, but reject obviously malicious input before even
        // touching the disk.
        abort_if(str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        // Filenames are random, content-addressed-ish hashes (see
        // ProfileService/ProductService/RestaurantSettingsService) — a new
        // upload always gets a new name, so caching a given path forever is safe.
        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
