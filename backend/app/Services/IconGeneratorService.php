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
            'Looks like a playful appliance or station that produces items, not a single product; no unrelated food props or clutter around it unless the machine name clearly implies them.',
            "Theme signature color — use as a strong accent, not a flat recolor of everything: {$accent}",
            'It may dominate large panels, lights, trim, or energy effects, or stay as a clear accent alongside believable materials (metal, glass, wood, ceramic, plastic) when those fit the theme.',
            'Moderate, pleasant saturation — readable on screen but not neon or acid; no fluorescent candy colors; avoid muddy gray mush overall.',
            'Glossy enamel or plastic where appropriate, soft specular highlights and gentle sheen on accents, subtle glow,',
            'polished casual-game look with restrained color intensity.',
            'Thin uniform black outline (hairline to fine stroke) around the entire outer silhouette; crisp cartoon stroke, not thick or heavy.',
            'Soft 3D form and cartoon proportions; if reference images exist, match their form language only.',
            'Fully transparent background — alpha clear everywhere except the drawn pixels of the machine; no solid fill, gradient slab, vignette, or color card behind it.',
            'No ground plane, mat, pedestal, platform, or decorative base as a separate backdrop — only the generator graphic itself.',
            'Single centered object, no text, no letters, no extra scenery.',
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
            $level <= 3 => 'Simple, small, crisp silhouette; clear readable colors, slightly softened saturation.',
            $level <= 6 => 'Moderately detailed, richer but still natural-looking hues and cleaner shapes.',
            $level <= 9 => 'Elaborate, richly detailed; accents and contrast where appropriate, still avoiding neon or acid tones.',
            default => 'Premium showpiece: refined highlights, soft gloss, subtle sparkle — elegant, not loud.',
        };

        return implode(' ', [
            "A single game icon of \"{$itemName}\" for a merge-2 mobile game with a warm, approachable palette.",
            "{$themeName} collection, level {$level} of 10.",
            $levelDescription,
            'Keep each material\'s believable hue (metal silvery, ceramic white or glazed, foliage green, citrus peel orange-yellow); do not dye the whole object into the theme accent if that would look wrong.',
            'Depict ONLY what the quoted item name describes — one clear subject. Do not add unrelated edibles, liquids, crumbs, or filler props the name does not mention; plain tableware and empty vessels stay simple with no surprise contents or random garnishes.',
            "Theme signature color — optional tie-in, not a forced recolor: {$accent}",
            'Use it as a clear accent (rim glow, glaze, particles, trim, packaging stripe) or let it cover larger areas only where it still reads as that object.',
            'Overall palette: pleasant, balanced saturation — not fluorescent, not oversaturated candy; still avoid dull muddy gray-only looks.',
            'Glossy surfaces where fitting, soft specular highlights, gentle inner glow, subtle sheen on materials,',
            'polished casual-game rendering with restrained color intensity.',
            'Thin uniform black outline (hairline to fine stroke) around the entire outer silhouette; crisp cartoon stroke, not thick or heavy.',
            'Soft 3D shading and cartoon proportions; if reference images exist, match their form language only.',
            'Fully transparent background — alpha clear everywhere except the drawn pixels of the item; no solid fill, gradient slab, vignette, or color card behind it.',
            'No ground plane, mat, pedestal, dish, circular badge, or square tile under the subject — composition is only the item itself, floating.',
            'Single centered object, no text, no letters, no extra scenery.',
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
