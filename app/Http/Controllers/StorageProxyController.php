<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageProxyController extends Controller
{
    /**
     * Proxy files from the "public" storage disk through the application.
     *
     * When PUBLIC_S3_PROXY is enabled, this serves files that would normally
     * be accessed directly from S3 (images, logos, avatars, etc.), allowing
     * a fully private S3 bucket setup.
     */
    public function show(string $path): Response|StreamedResponse
    {
        if (! config('filesystems.public_proxy') || $this->hasPathTraversalSegments($path)) {
            abort(404);
        }

        if ($this->overlapsPrivateStorage()) {
            abort(404);
        }

        $disk = Storage::disk('public');

        // The S3 adapter includes the disk's root prefix in generated URLs,
        // but Flysystem also prepends it internally on every operation.
        // Strip it here to avoid double-prefixing.
        $root = trim((string) config('filesystems.disks.public.root'), '/');
        if ($root !== '' && str_starts_with($path, $root.'/')) {
            $path = substr($path, strlen($root) + 1);
        }

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';
        $lastModified = $disk->lastModified($path);
        $etag = md5($path.$lastModified);
        $size = $disk->size($path);
        $headers = [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'ETag' => '"'.$etag.'"',
        ];

        if ($this->isNotModified($etag, $lastModified)) {
            return response('', 304)
                ->withHeaders($headers)
                ->header('Cache-Control', 'public, max-age=86400');
        }

        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp', 'image/avif', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon'], true)) {
            $mimeType = 'application/octet-stream';
            $headers['Content-Disposition'] = 'attachment';
        }

        return new StreamedResponse(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if (is_resource($stream)) {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            }
        }, 200, $headers + [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function overlapsPrivateStorage(): bool
    {
        $public = config('filesystems.disks.public');
        if (($public['driver'] ?? null) !== 's3') {
            return false;
        }

        $publicRoot = trim((string) ($public['root'] ?? ''), '/');
        foreach ([config('filesystems.default'), 'backup', 'backup_s3'] as $diskName) {
            $private = config('filesystems.disks.'.$diskName, []);
            if (($private['driver'] ?? null) !== 's3' || ($private['bucket'] ?? null) !== ($public['bucket'] ?? null)) {
                continue;
            }

            $privateRoot = trim((string) ($private['root'] ?? ''), '/');
            if ($publicRoot === '' || $privateRoot === '' || $publicRoot === $privateRoot
                || str_starts_with($publicRoot, $privateRoot.'/') || str_starts_with($privateRoot, $publicRoot.'/')) {
                return true;
            }
        }

        return false;
    }

    private function isNotModified(string $etag, int $lastModified): bool
    {
        $requestEtag = request()->header('If-None-Match');
        if ($requestEtag && $requestEtag === '"'.$etag.'"') {
            return true;
        }

        $ifModifiedSince = request()->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return true;
        }

        return false;
    }

    private function hasPathTraversalSegments(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        return str_contains($normalizedPath, "\0")
            || str_starts_with($normalizedPath, '/')
            || str_contains($normalizedPath, '../')
            || str_contains($normalizedPath, '/..')
            || str_ends_with($normalizedPath, '/..')
            || $normalizedPath === '..';
    }
}
