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

        $prompt = $this->buildPrompt(
            $item->name,
            $item->level,
            $theme->name,
            Theme::normalizeAccentColor($theme->accent_color)
        );
        $referenceFiles = $this->getReferenceFiles();

        if (empty($referenceFiles)) {
            $imageBase64 = $this->generateWithoutReferences($prompt);
        } else {
            $imageBase64 = $this->generateWithReferences($prompt, $referenceFiles);
        }

        $resizedPng = $this->resizeImage($imageBase64, $this->iconSize);

        return $this->saveIcon($resizedPng, $theme->slug, $item->level);
    }

    /**
     * Generate a merge-game generator machine icon for the theme; saves to storage/app/public/generators/{slug}.png.
     */
    public function generateGeneratorIcon(Theme $theme): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured');
        }

        $prompt = $this->buildGeneratorPrompt(
            $theme->generator_name,
            $theme->name,
            Theme::normalizeAccentColor($theme->accent_color)
        );
        $referenceFiles = $this->getReferenceFiles();

        if (empty($referenceFiles)) {
            $imageBase64 = $this->generateWithoutReferences($prompt);
        } else {
            $imageBase64 = $this->generateWithReferences($prompt, $referenceFiles);
        }

        $resizedPng = $this->resizeImage($imageBase64, $this->iconSize);

        return $this->saveGeneratorIcon($resizedPng, $theme->slug);
    }

    private function buildGeneratorPrompt(string $generatorName, string $themeName, string $accentColorKey): string
    {
        $accent = Theme::accentColorPromptFragment($accentColorKey);

        return implode(' ', [
            "A single game icon of a merge-2 generator machine: \"{$generatorName}\".",
            "Theme collection: {$themeName}.",
            'Looks like a playful appliance or station that produces items, not a single product.',
            "Theme signature color (must dominate): {$accent}",
            'Small secondary accents may use one contrasting candy color; high chroma;',
            'no brown, sepia, or dull earth tones.',
            'Glossy enamel or plastic finish, strong specular highlights, jewel shine, subtle glow,',
            'juicy polished mobile-game look.',
            'Match reference images for outline weight, soft 3D form, and cartoon proportions only.',
            'Single centered object, no text, no letters, no background elements.',
        ]);
    }

    private function saveGeneratorIcon(string $pngData, string $themeSlug): string
    {
        $relativePath = "generators/{$themeSlug}.png";

        Storage::disk('public')->put($relativePath, $pngData);

        return $relativePath;
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

    private function buildPrompt(string $itemName, int $level, string $themeName, string $accentColorKey): string
    {
        $accent = Theme::accentColorPromptFragment($accentColorKey);

        $levelDescription = match (true) {
            $level <= 3 => 'Simple, small, crisp silhouette with punchy color.',
            $level <= 6 => 'Moderately detailed, richer hues and cleaner shapes.',
            $level <= 9 => 'Elaborate, richly detailed, rainbow-bright accents and contrast.',
            default => 'Premium showpiece: prismatic highlights, extra gloss, starry sparkle hints.',
        };

        return implode(' ', [
            "A single game icon of \"{$itemName}\" for a colorful merge-2 mobile game.",
            "{$themeName} collection, level {$level} of 10.",
            $levelDescription,
            "Theme signature color (must dominate the icon): {$accent}",
            'Secondary details may use one small contrasting candy accent for pop;',
            'saturated and cheerful; no brown, sepia, or muddy neutrals.',
            'Glossy surfaces, bright specular hits, soft inner glow, jewel-like shine,',
            'juicy polished casual-game rendering.',
            'Match reference images for thick dark outlines, soft 3D shading, and cartoon proportions only.',
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
