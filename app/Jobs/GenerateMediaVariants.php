<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Media;
use App\Models\MediaVariant;
use App\Support\Hooks\Hooks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generate configured image variants. Requires GD; no-ops for non-images.
 */
class GenerateMediaVariants implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = Media::query()->find($this->mediaId);
        if ($media === null) {
            return;
        }

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        if (! function_exists('imagecreatefromstring')) {
            Log::warning('GD not available; skipping media variants.', ['media_id' => $media->id]);

            return;
        }

        $binary = Storage::disk($media->disk)->get($media->path);
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        foreach (config('cms.media_variants', []) as $name => $config) {
            $targetW = (int) ($config['width'] ?? $srcW);
            $targetH = (int) ($config['height'] ?? $srcH);
            $crop = (bool) ($config['crop'] ?? false);

            [$dstW, $dstH, $srcX, $srcY, $cropW, $cropH] = $this->dimensions(
                $srcW, $srcH, $targetW, $targetH, $crop
            );

            $canvas = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);

            ob_start();
            imagejpeg($canvas, null, 85);
            $out = ob_get_clean();
            imagedestroy($canvas);

            $variantPath = 'variants/'.$media->id.'/'.$name.'.jpg';
            Storage::disk($media->disk)->put($variantPath, $out);

            MediaVariant::query()->updateOrCreate(
                ['media_id' => $media->id, 'variant' => $name],
                [
                    'disk' => $media->disk,
                    'path' => $variantPath,
                    'mime_type' => 'image/jpeg',
                    'width' => $dstW,
                    'height' => $dstH,
                    'size' => strlen($out),
                ],
            );
        }

        imagedestroy($source);
        Hooks::action('media.variants.generated', $media);
    }

    /**
     * @return array{0:int,1:int,2:int,3:int,4:int,5:int}
     */
    private function dimensions(int $srcW, int $srcH, int $targetW, int $targetH, bool $crop): array
    {
        if ($crop) {
            $scale = max($targetW / max($srcW, 1), $targetH / max($srcH, 1));
            $cropW = (int) round($targetW / $scale);
            $cropH = (int) round($targetH / $scale);
            $srcX = (int) max(0, ($srcW - $cropW) / 2);
            $srcY = (int) max(0, ($srcH - $cropH) / 2);

            return [$targetW, $targetH, $srcX, $srcY, $cropW, $cropH];
        }

        $ratio = min($targetW / max($srcW, 1), $targetH / max($srcH, 1), 1.0);
        $dstW = max(1, (int) round($srcW * $ratio));
        $dstH = max(1, (int) round($srcH * $ratio));

        return [$dstW, $dstH, 0, 0, $srcW, $srcH];
    }
}
