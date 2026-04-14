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

    protected $description = 'Сопоставить файлы в storage/app/public/items/{theme}/ с записями item_definitions (image_url)';

    private const EXT = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

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

        $updated = 0;
        $skipped = 0;
        $noFile = 0;

        foreach ($themes as $theme) {
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
                    $noFile++;
                    $this->line("  [—] {$theme->slug} L{$def->level} {$def->name}: файла нет");
                    continue;
                }

                if (!$this->shouldUpdate($def, $path, $force)) {
                    $skipped++;
                    $this->line("  [skip] {$theme->slug} L{$def->level} {$def->name}: уже задано");
                    continue;
                }

                if ($dry) {
                    $this->line("  [dry] {$theme->slug} L{$def->level} {$def->name} → {$path}");
                    $updated++;
                    continue;
                }

                $def->update(['image_url' => $path]);
                $updated++;
                $this->info("  [ok] {$theme->slug} L{$def->level} {$def->name} → {$path}");
            }
        }

        $this->newLine();
        $this->line(
            ($dry ? '[dry-run] ' : '')
            . "Обновлено: {$updated}, пропущено: {$skipped}, без файла на диске: {$noFile}"
        );

        return self::SUCCESS;
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

    private function shouldUpdate(ItemDefinition $def, string $newPath, bool $force): bool
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
