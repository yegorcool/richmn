<?php

namespace App\Services;

use App\Models\ItemDefinition;
use App\Models\Theme;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class IconGeneratorService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private string $size;
    private string $quality;
    private int $iconSize;
    private string $referencesPath;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key', '');
        $this->baseUrl = rtrim(config('imagegen.base_url', 'https://api.openai.com'), '/');
        $this->model = config('imagegen.model', 'gpt-image-1');
        $this->size = config('imagegen.size', '1024x1024');
        $this->quality = config('imagegen.quality', 'medium');
        $this->iconSize = (int) config('imagegen.icon_size', 256);
        $this->referencesPath = config('imagegen.references_path', storage_path('app/icon-references'));
    }

    public function generateItemIcon(ItemDefinition $item, Theme $theme): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured');
        }

        $prompt = $this->buildPrompt($item->name, $item->level, $theme->name);
        $referenceFiles = $this->getReferenceFiles();

        if (empty($referenceFiles)) {
            $imageBase64 = $this->generateWithoutReferences($prompt);
        } else {
            $imageBase64 = $this->generateWithReferences($prompt, $referenceFiles);
        }

        $resizedPng = $this->resizeImage($imageBase64, $this->iconSize);

        return $this->saveIcon($resizedPng, $theme->slug, $item->level);
    }

    private function generateWithReferences(string $prompt, array $referenceFiles): string
    {
        $multipart = [
            ['name' => 'model', 'contents' => $this->model],
            ['name' => 'prompt', 'contents' => $prompt],
            ['name' => 'n', 'contents' => '1'],
            ['name' => 'size', 'contents' => $this->size],
            ['name' => 'quality', 'contents' => $this->quality],
            ['name' => 'background', 'contents' => 'transparent'],
        ];

        foreach ($referenceFiles as $filePath) {
            $multipart[] = [
                'name' => 'image[]',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ];
        }

        $response = Http::timeout(120)
            ->withToken($this->apiKey)
            ->asMultipart()
            ->post("{$this->baseUrl}/v1/images/edits", $multipart);

        if (!$response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new RuntimeException("OpenAI Edit API error: {$error}");
        }

        $b64 = $response->json('data.0.b64_json');
        if (empty($b64)) {
            throw new RuntimeException('OpenAI returned empty image data');
        }

        return $b64;
    }

    private function generateWithoutReferences(string $prompt): string
    {
        $response = Http::timeout(120)
            ->withToken($this->apiKey)
            ->post("{$this->baseUrl}/v1/images/generations", [
                'model' => $this->model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $this->size,
                'quality' => $this->quality,
                'background' => 'transparent',
            ]);

        if (!$response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new RuntimeException("OpenAI Generations API error: {$error}");
        }

        $b64 = $response->json('data.0.b64_json');
        if (empty($b64)) {
            throw new RuntimeException('OpenAI returned empty image data');
        }

        return $b64;
    }

    private function buildPrompt(string $itemName, int $level, string $themeName): string
    {
        $levelDescription = match (true) {
            $level <= 3 => 'Simple, small, basic everyday item.',
            $level <= 6 => 'Moderately detailed, more refined and colorful.',
            $level <= 9 => 'Elaborate, richly detailed, vibrant and eye-catching.',
            default => 'Premium luxurious item with golden accents and a glowing effect.',
        };

        return implode(' ', [
            "A single game icon of \"{$itemName}\" for a cozy merge-2 mobile game.",
            "{$themeName} collection, level {$level} of 10.",
            $levelDescription,
            'Match the visual style of the reference images exactly: warm colors,',
            'soft 3D shading, thick dark outlines, cartoon casual-game aesthetic.',
            'Single centered object, no text, no letters, no background elements.',
        ]);
    }

    private function getReferenceFiles(): array
    {
        if (!is_dir($this->referencesPath)) {
            return [];
        }

        $files = glob($this->referencesPath . '/*.{png,jpg,jpeg,webp}', GLOB_BRACE);

        return $files ?: [];
    }

    /**
     * Decode base64 PNG, resize to target dimensions preserving alpha.
     */
    private function resizeImage(string $base64, int $targetSize): string
    {
        $raw = base64_decode($base64, true);
        if ($raw === false) {
            throw new RuntimeException('Failed to decode base64 image data');
        }

        $src = imagecreatefromstring($raw);
        if ($src === false) {
            throw new RuntimeException('Failed to create image from decoded data');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $dst = imagecreatetruecolor($targetSize, $targetSize);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetSize, $targetSize, $srcW, $srcH);
        imagedestroy($src);

        ob_start();
        imagepng($dst, null, 9);
        $pngData = ob_get_clean();
        imagedestroy($dst);

        if ($pngData === false || $pngData === '') {
            throw new RuntimeException('Failed to encode resized image as PNG');
        }

        return $pngData;
    }

    private function saveIcon(string $pngData, string $themeSlug, int $level): string
    {
        $relativePath = "items/{$themeSlug}/lv{$level}.png";

        Storage::disk('public')->put($relativePath, $pngData);

        return $relativePath;
    }
}
