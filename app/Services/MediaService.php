<?php

namespace App\Services;

use App\Exceptions\UserFacingException;
use App\Models\MediaFile;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Models\ProfileVersion;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * All uploads go through here. Files are never stored as uploaded: the content
 * type is sniffed from the bytes, the image is decoded and re-encoded with GD
 * (dropping metadata and any appended payload) and written under a random name.
 */
class MediaService
{
    // Decompression-bomb guard: refuse images with more pixels than this before decoding.
    private const MAX_PIXELS = 40_000_000;

    public function __construct(private readonly Entitlements $entitlements)
    {
    }

    public function store(UploadedFile $file, ?User $owner, string $purpose): MediaFile
    {
        $targets = config('biofy.uploads.targets');

        if (! isset($targets[$purpose])) {
            throw new UserFacingException(__('media.invalid_purpose'));
        }

        if (! $file->isValid()) {
            throw new UserFacingException(__('media.upload_failed'));
        }

        $maxSize = (int) config('biofy.uploads.max_file_size');

        if ($file->getSize() > $maxSize) {
            throw new UserFacingException(__('media.too_large', ['max' => $this->humanSize($maxSize)]));
        }

        // Work on the bytes (not the temp path): the type is sniffed from content, never from the name.
        try {
            $raw = (string) $file->get();
        } catch (\Throwable) {
            throw new UserFacingException(__('media.upload_failed'));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($raw) ?: '';

        if (! in_array($mime, config('biofy.uploads.allowed_mimes'), true)) {
            throw new UserFacingException(__('media.invalid_type'));
        }

        $info = @getimagesizefromstring($raw);

        if ($info === false || $info[0] < 1 || $info[1] < 1 || ($info[0] * $info[1]) > self::MAX_PIXELS) {
            throw new UserFacingException(__('media.invalid_image'));
        }

        $source = @imagecreatefromstring($raw);
        unset($raw);

        if (! $source instanceof GdImage) {
            throw new UserFacingException(__('media.invalid_image'));
        }

        $image = $this->resize($source, $targets[$purpose]);
        [$bytes, $outMime, $ext] = $this->encode($image, $purpose);

        if ($owner !== null) {
            $this->assertQuota($owner, strlen($bytes));
        }

        $path = sprintf('%s/%s/%s.%s', $purpose, now()->format('Y/m'), Str::lower(Str::random(32)), $ext);
        Storage::disk('media')->put($path, $bytes);

        return MediaFile::query()->create([
            'user_id' => $owner?->id,
            'disk' => 'media',
            'path' => $path,
            'purpose' => $purpose,
            'mime' => $outMime,
            'size' => strlen($bytes),
            'width' => imagesx($image),
            'height' => imagesy($image),
            'original_name' => Str::limit(preg_replace('/[^\pL\pN._ -]+/u', '', $file->getClientOriginalName()) ?: 'image', 180, ''),
            'sha256' => hash('sha256', $bytes),
        ]);
    }

    public function delete(MediaFile $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    public function usedBytes(User $user): int
    {
        return (int) MediaFile::query()->where('user_id', $user->id)->sum('size');
    }

    public function quotaBytes(User $user): int
    {
        $mb = $this->entitlements->limit($user, Entitlements::STORAGE_QUOTA_MB);

        return $mb === Entitlements::UNLIMITED ? PHP_INT_MAX : $mb * 1024 * 1024;
    }

    public function assertQuota(User $user, int $additionalBytes): void
    {
        if ($this->usedBytes($user) + $additionalBytes > $this->quotaBytes($user)) {
            throw new UserFacingException(__('media.quota_exceeded'));
        }
    }

    /** Media IDs referenced by the user's drafts or published pages. */
    public function referencedIds(User $user): array
    {
        $profileIds = Profile::query()->where('user_id', $user->id)->pluck('id');
        $ids = [];

        foreach (Profile::query()->whereIn('id', $profileIds)->get(['avatar_media_id', 'banner_media_id', 'seo_image_media_id', 'published_version_id']) as $profile) {
            array_push($ids, $profile->avatar_media_id, $profile->banner_media_id, $profile->seo_image_media_id);

            if ($profile->published_version_id) {
                // The model casts snapshot to an array (Eloquent's value() returns the cast value too).
                $snapshot = ProfileVersion::query()->whereKey($profile->published_version_id)->first(['id', 'snapshot'])?->snapshot;
                $ids = array_merge($ids, (array) ($snapshot['media_ids'] ?? []));
            }
        }

        ProfileBlock::query()->whereIn('profile_id', $profileIds)->select(['id', 'data'])->chunkById(200, function ($blocks) use (&$ids) {
            foreach ($blocks as $block) {
                $ids = array_merge($ids, self::mediaIdsInBlockData($block->data ?? []));
            }
        });

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    public function isInUse(MediaFile $media): bool
    {
        if ($media->user_id === null) {
            return in_array($media->id, $this->platformReferencedIds(), true);
        }

        return in_array($media->id, $this->referencedIds($media->user), true);
    }

    public function platformReferencedIds(): array
    {
        $settings = app(Settings::class);

        return array_values(array_filter([
            $settings->get('site.logo_media_id'),
            $settings->get('site.favicon_media_id'),
            $settings->get('footer.logo_media_id'),
            $settings->get('seo.og_image_media_id'),
        ]));
    }

    public static function mediaIdsInBlockData(array $data): array
    {
        $ids = [];

        if (! empty($data['media_id'])) {
            $ids[] = (int) $data['media_id'];
        }

        foreach ($data['items'] ?? [] as $item) {
            if (is_array($item) && ! empty($item['media_id'])) {
                $ids[] = (int) $item['media_id'];
            }
        }

        return $ids;
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }

    private function resize(GdImage $source, array $target): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $tw = $target['width'];
        $th = $target['height'];

        if ($target['crop']) {
            // "Cover" crop: scale so the target box is filled, then take the centred region.
            $scale = max($tw / $w, $th / $h);
            $cropW = min($w, (int) round($tw / $scale));
            $cropH = min($h, (int) round($th / $scale));
            $srcX = (int) floor(($w - $cropW) / 2);
            $srcY = (int) floor(($h - $cropH) / 2);
            $canvas = $this->canvas($tw, $th);
            imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $tw, $th, $cropW, $cropH);

            return $canvas;
        }

        $scale = min(1.0, $tw / $w, $th / $h);
        $outW = max(1, (int) round($w * $scale));
        $outH = max(1, (int) round($h * $scale));
        $canvas = $this->canvas($outW, $outH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $outW, $outH, $w, $h);

        return $canvas;
    }

    private function canvas(int $w, int $h): GdImage
    {
        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    /** @return array{0: string, 1: string, 2: string} bytes, mime, extension */
    private function encode(GdImage $image, string $purpose): array
    {
        ob_start();

        if ($purpose !== 'favicon' && function_exists('imagewebp') && (imagetypes() & IMG_WEBP)) {
            imagewebp($image, null, 82);
            $result = [(string) ob_get_clean(), 'image/webp', 'webp'];
        } else {
            imagepng($image, null, 7);
            $result = [(string) ob_get_clean(), 'image/png', 'png'];
        }

        if ($result[0] === '') {
            throw new UserFacingException(__('media.upload_failed'));
        }

        return $result;
    }
}
