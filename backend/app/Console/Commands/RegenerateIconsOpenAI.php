<?php

namespace App\Console\Commands;

use App\Models\ItemDefinition;
use App\Models\Theme;
use App\Services\IconGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegenerateIconsOpenAI extends Command
{
    protected $signature = 'icons:regenerate-openai
                            {--theme= : Только тема с этим slug}
                            {--without-items : Не генерировать иконки предметов}
                            {--without-generators : Не генерировать иконки генераторов}
                            {--delay=0 : Пауза в секундах между запросами к API (допускается дробное значение)}';

    protected $description = 'Заново сгенерировать иконки всех предметов и генераторов через OpenAI API';

    public function handle(IconGeneratorService $iconGenerator): int
    {
        if (empty(config('openai.api_key', ''))) {
            $this->error('Не задан OPENAI_API_KEY (или config/openai.php).');

            return self::FAILURE;
        }

        $withoutItems = (bool) $this->option('without-items');
        $withoutGenerators = (bool) $this->option('without-generators');

        if ($withoutItems && $withoutGenerators) {
            $this->error('Нельзя одновременно отключить и предметы, и генераторы.');

            return self::FAILURE;
        }

        $delaySec = (float) $this->option('delay');
        if ($delaySec < 0) {
            $this->error('Параметр --delay не может быть отрицательным.');

            return self::FAILURE;
        }

        $themeSlug = $this->option('theme');
        if ($themeSlug) {
            $themes = Theme::where('slug', $themeSlug)->orderBy('slug')->get();
            if ($themes->isEmpty()) {
                $this->error("Тема со slug \"{$themeSlug}\" не найдена.");

                return self::FAILURE;
            }
        } else {
            $themes = Theme::orderBy('slug')->get();
        }

        if ($themes->isEmpty()) {
            $this->warn('В базе нет тем.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($themes as $theme) {
            $this->line("Тема: {$theme->slug} ({$theme->name})");

            if (!$withoutGenerators) {
                try {
                    $this->regenerateGeneratorIcon($theme, $iconGenerator);
                    $this->info('  Генератор: OK');
                } catch (Throwable $e) {
                    $failures++;
                    $this->error('  Генератор: ' . $e->getMessage());
                }
                $this->pauseBetweenRequests($delaySec);
            }

            if (!$withoutItems) {
                $definitions = $theme->itemDefinitions()->orderBy('level')->get();
                foreach ($definitions as $def) {
                    try {
                        $this->regenerateItemIcon($theme, $def, $iconGenerator);
                        $this->info("  Предмет L{$def->level} {$def->name}: OK");
                    } catch (Throwable $e) {
                        $failures++;
                        $this->error("  Предмет L{$def->level} {$def->name}: " . $e->getMessage());
                    }
                    $this->pauseBetweenRequests($delaySec);
                }
                if ($definitions->isNotEmpty()) {
                    $this->syncChainConfig($theme);
                }
            }

            $this->newLine();
        }

        if ($failures > 0) {
            $this->error("Готово с ошибками: {$failures}.");

            return self::FAILURE;
        }

        $this->info('Все иконки успешно пересозданы.');

        return self::SUCCESS;
    }

    private function regenerateGeneratorIcon(Theme $theme, IconGeneratorService $iconGenerator): void
    {
        if ($theme->generator_image_url && !str_starts_with($theme->generator_image_url, 'http')) {
            Storage::disk('public')->delete($theme->generator_image_url);
        }

        $relativePath = $iconGenerator->generateGeneratorIcon($theme);
        $theme->update(['generator_image_url' => $relativePath]);
    }

    private function regenerateItemIcon(Theme $theme, ItemDefinition $itemDefinition, IconGeneratorService $iconGenerator): void
    {
        if ($itemDefinition->image_url && !str_starts_with($itemDefinition->image_url, 'http')) {
            Storage::disk('public')->delete($itemDefinition->image_url);
        }

        $relativePath = $iconGenerator->generateItemIcon($itemDefinition, $theme);
        $itemDefinition->update(['image_url' => $relativePath]);
    }

    private function syncChainConfig(Theme $theme): void
    {
        $definitions = $theme->itemDefinitions()->orderBy('level')->get();
        $chainConfig = $definitions->map(fn ($d) => [
            'level' => $d->level,
            'name' => $d->name,
            'sprite_key' => $d->slug,
        ])->values()->toArray();

        $theme->update(['chain_config' => $chainConfig]);
    }

    private function pauseBetweenRequests(float $delaySec): void
    {
        if ($delaySec <= 0) {
            return;
        }

        usleep((int) round($delaySec * 1_000_000));
    }
}
