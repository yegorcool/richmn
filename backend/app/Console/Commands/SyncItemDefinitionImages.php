<?php

namespace App\Console\Commands;

use App\Models\ItemDefinition;
use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncItemDefinitionImages extends Command
{
    protected $signature = 'item-definitions:sync-images
                            {--theme= : Slug темы (только каталог items/<slug> на public-диске)}
                            {--dry-run : Показать изменения без записи в БД}
                            {--force : Перезаписать image_url, даже если локальный файл уже задан}';

    protected $description = 'Сопоставить файлы items/<slug>/ и generators/ с item_definitions.image_url и themes.generator_image_url';

    private const EXT = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

    /** При нескольких расширениях для одного slug генератора предпочитаем более «типичный» формат. */
    private const GENERATOR_EXT_PRIORITY = ['png' => 5, 'webp' => 4, 'jpg' => 3, 'jpeg' => 3, 'svg' => 2];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dry = $this->option('dry-run');
        $force = $this->option('force');
        $themeFilter = $this->option('theme');

        if ($themeFilter) {
            $themes = Theme::where('slug', $themeFilter)->get();
            if ($themes->isEmpty()) {
                $this->error("Тема со slug \"{$themeFilter}\" не найдена.");
                return self::FAILURE;
            }
        } else {
            $themes = Theme::orderBy('slug')->get();
        }

        $generatorBySlug = $disk->exists('generators')
            ? $this->indexGeneratorFiles($disk->files('generators'))
            : [];

        $genUpdated = 0;
        $genSkipped = 0;
        $genNoFile = 0;
        $itemUpdated = 0;
        $itemSkipped = 0;
        $itemNoFile = 0;

        foreach ($themes as $theme) {
            $genPath = $generatorBySlug[$theme->slug] ?? null;

            if ($genPath === null) {
                $genNoFile++;
            } elseif (!$this->shouldUpdateThemeGenerator($theme, $genPath, $force)) {
                $genSkipped++;
                $this->line("  [skip] генератор {$theme->slug}: уже задано");
            } elseif ($dry) {
                $this->line("  [dry] генератор {$theme->slug} → {$genPath}");
                $genUpdated++;
            } else {
                $theme->update(['generator_image_url' => $genPath]);
                $genUpdated++;
                $this->info("  [ok] генератор {$theme->slug} → {$genPath}");
            }

            $prefix = 'items/' . $theme->slug;
            if (!$disk->exists($prefix)) {
                continue;
            }

            $files = $disk->files($prefix);
            [$byLevel, $bySlug] = $this->indexFiles($files);

            $definitions = ItemDefinition::where('theme_id', $theme->id)->orderBy('level')->get();

            foreach ($definitions as $def) {
                $path = $this->resolvePathForDefinition($def, $byLevel, $bySlug);

                if ($path === null) {
                    $itemNoFile++;
                    $this->line("  [—] предмет {$theme->slug} L{$def->level} {$def->name}: файла нет");
                    continue;
                }

                if (!$this->shouldUpdateItemDefinition($def, $path, $force)) {
                    $itemSkipped++;
                    $this->line("  [skip] предмет {$theme->slug} L{$def->level} {$def->name}: уже задано");
                    continue;
                }

                if ($dry) {
                    $this->line("  [dry] предмет {$theme->slug} L{$def->level} {$def->name} → {$path}");
                    $itemUpdated++;
                    continue;
                }

                $def->update(['image_url' => $path]);
                $itemUpdated++;
                $this->info("  [ok] предмет {$theme->slug} L{$def->level} {$def->name} → {$path}");
            }
        }

        $this->newLine();
        $prefixLabel = $dry ? '[dry-run] ' : '';
        $this->line("{$prefixLabel}Генераторы — обновлено: {$genUpdated}, пропущено: {$genSkipped}, без файла: {$genNoFile}");
        $this->line("{$prefixLabel}Предметы — обновлено: {$itemUpdated}, пропущено: {$itemSkipped}, без файла: {$itemNoFile}");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $files  Пути вида generators/coffee.png
     * @return array<string, string>  slug темы → относительный путь на диске
     */
    private function indexGeneratorFiles(array $files): array
    {
        /** @var array<string, array{path: string, priority: int}> $candidates */
        $candidates = [];

        foreach ($files as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
            if (!in_array($ext, self::EXT, true)) {
                continue;
            }

            $base = pathinfo($path, PATHINFO_FILENAME);
            if ($base === '') {
                continue;
            }

            $priority = self::GENERATOR_EXT_PRIORITY[$ext] ?? 0;
            if (!isset($candidates[$base]) || $candidates[$base]['priority'] < $priority) {
                $candidates[$base] = ['path' => $path, 'priority' => $priority];
            }
        }

        $bySlug = [];
        foreach ($candidates as $slug => $row) {
            $bySlug[$slug] = $row['path'];
        }

        return $bySlug;
    }

    private function shouldUpdateThemeGenerator(Theme $theme, string $newPath, bool $force): bool
    {
        if ($force) {
            return $theme->generator_image_url !== $newPath;
        }

        $current = $theme->generator_image_url;
        if ($current === null || $current === '') {
            return true;
        }

        if (str_starts_with($current, 'http://') || str_starts_with($current, 'https://')) {
            return true;
        }

        return !Storage::disk('public')->exists($current);
    }

    /**
     * @param  list<string>  $files  Relative paths on public disk (e.g. items/coffee/lv1.png)
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    private function indexFiles(array $files): array
    {
        /** @var array<int, array{path: string, priority: int}> $levelCandidates */
        $levelCandidates = [];
        $bySlug = [];

        foreach ($files as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
            if (!in_array($ext, self::EXT, true)) {
                continue;
            }

            $base = pathinfo($path, PATHINFO_FILENAME);
            if ($base === '') {
                continue;
            }

            if (preg_match('/^lv(\d+)$/i', $base, $m)) {
                $level = (int) $m[1];
                $this->mergeLevelCandidate($levelCandidates, $level, $path, 2);
            } elseif (preg_match('/^(\d+)$/', $base, $m)) {
                $level = (int) $m[1];
                $this->mergeLevelCandidate($levelCandidates, $level, $path, 1);
            } else {
                $bySlug[$base] = $path;
            }
        }

        $byLevel = [];
        foreach ($levelCandidates as $level => $row) {
            $byLevel[$level] = $row['path'];
        }

        return [$byLevel, $bySlug];
    }

    /**
     * @param  array<int, array{path: string, priority: int}>  $levelCandidates
     */
    private function mergeLevelCandidate(array &$levelCandidates, int $level, string $path, int $priority): void
    {
        if (!isset($levelCandidates[$level]) || $levelCandidates[$level]['priority'] < $priority) {
            $levelCandidates[$level] = ['path' => $path, 'priority' => $priority];
        }
    }

    /**
     * @param  array<int, string>  $byLevel
     * @param  array<string, string>  $bySlug
     */
    private function resolvePathForDefinition(ItemDefinition $def, array $byLevel, array $bySlug): ?string
    {
        if (isset($byLevel[$def->level])) {
            return $byLevel[$def->level];
        }

        return $bySlug[$def->slug] ?? null;
    }

    private function shouldUpdateItemDefinition(ItemDefinition $def, string $newPath, bool $force): bool
    {
        if ($force) {
            return $def->image_url !== $newPath;
        }

        $current = $def->image_url;
        if ($current === null || $current === '') {
            return true;
        }

        if (str_starts_with($current, 'http://') || str_starts_with($current, 'https://')) {
            return true;
        }

        return !Storage::disk('public')->exists($current);
    }
}
