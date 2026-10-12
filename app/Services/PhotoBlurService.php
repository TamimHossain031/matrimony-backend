<?php

namespace App\Services;

use App\Models\ProfilePhoto;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Builds the blurred derivative of a photo (blueprint §12). The blur is done
 * server-side so the sharp original never has to leave the server for viewers
 * who aren't allowed to see it.
 *
 * The image is shrunk to a tiny size first — that throws the facial detail away
 * for good, so the result can't be "deblurred" — then enlarged and smoothed so
 * it looks like a soft blur rather than big blocks.
 */
class PhotoBlurService
{
    private const SAMPLE_WIDTH = 24;

    private const OUTPUT_WIDTH = 480;

    /** Generate and store the blurred copy, returning its storage path. */
    public function generate(ProfilePhoto $photo): string
    {
        $disk = Storage::disk($photo->disk);
        $source = @imagecreatefromstring((string) $disk->get($photo->path));

        if ($source === false) {
            throw new RuntimeException("Photo {$photo->id} could not be decoded.");
        }

        $source = $this->normalizeOrientation($source, (string) $disk->path($photo->path));
        $blurred = $this->blur($source);

        ob_start();
        imagejpeg($blurred, null, 60); // re-encoding also strips EXIF/GPS metadata
        $jpeg = (string) ob_get_clean();

        $blurredPath = 'profile-photos/'.$photo->profile_id.'/blurred/'.pathinfo($photo->path, PATHINFO_FILENAME).'.jpg';
        $disk->put($blurredPath, $jpeg);

        return $blurredPath;
    }

    private function blur(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $smallHeight = max(1, (int) round($height * self::SAMPLE_WIDTH / $width));
        $small = imagecreatetruecolor(self::SAMPLE_WIDTH, $smallHeight);
        imagecopyresampled($small, $source, 0, 0, 0, 0, self::SAMPLE_WIDTH, $smallHeight, $width, $height);

        $outHeight = max(1, (int) round($height * self::OUTPUT_WIDTH / $width));
        $output = imagecreatetruecolor(self::OUTPUT_WIDTH, $outHeight);
        imagecopyresampled($output, $small, 0, 0, 0, 0, self::OUTPUT_WIDTH, $outHeight, self::SAMPLE_WIDTH, $smallHeight);

        for ($i = 0; $i < 6; $i++) {
            imagefilter($output, IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $output;
    }

    /** Honour the EXIF rotation of phone photos so the blur has the right orientation. */
    private function normalizeOrientation(\GdImage $image, string $absolutePath): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($absolutePath)['Orientation'] ?? 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
