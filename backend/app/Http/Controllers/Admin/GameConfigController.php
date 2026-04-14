<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterLine;
use App\Models\ItemDefinition;
use App\Models\Theme;
use App\Services\IconGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GameConfigController extends Controller
{
    // ── Themes ──────────────────────────────────────────────

    public function themes()
    {
        $themes = Theme::withCount('items', 'generators', 'itemDefinitions')->get();
        return view('admin.themes', compact('themes'));
    }

    public function createTheme()
    {
        return view('admin.theme-form', ['theme' => null]);
    }

    public function storeTheme(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:themes,slug',
            'accent_color' => ['required', 'string', Rule::in(Theme::accentColorKeys())],
            'generator_name' => 'required|string|max:255',
            'unlock_level' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'generator_energy_cost' => 'required|integer|min:0',
            'generator_generation_limit' => 'required|integer|min:1',
            'generator_generation_timeout' => 'required|integer|min:0',
            'generator_image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'generator_image_url_external' => 'nullable|url|max:500',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['chain_config'] = [];

        $generatorImageUrl = null;
        if ($request->hasFile('generator_image')) {
            $ext = $request->file('generator_image')->getClientOriginalExtension() ?: 'png';
            $generatorImageUrl = $request->file('generator_image')->storeAs(
                'generators',
                $validated['slug'] . '.' . $ext,
                'public'
            );
        } elseif (!empty($validated['generator_image_url_external'])) {
            $generatorImageUrl = $validated['generator_image_url_external'];
        }

        Theme::create(array_merge(
            collect($validated)->except(['generator_image', 'generator_image_url_external'])->toArray(),
            ['generator_image_url' => $generatorImageUrl]
        ));

        return redirect()->route('admin.themes')->with('success', 'Тематика создана');
    }

    public function editTheme(Theme $theme)
    {
        return view('admin.theme-form', compact('theme'));
    }

    public function updateTheme(Request $request, Theme $theme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:themes,slug,' . $theme->id,
            'accent_color' => ['required', 'string', Rule::in(Theme::accentColorKeys())],
            'generator_name' => 'required|string|max:255',
            'unlock_level' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'generator_energy_cost' => 'required|integer|min:0',
            'generator_generation_limit' => 'required|integer|min:1',
            'generator_generation_timeout' => 'required|integer|min:0',
            'generator_image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'generator_image_url_external' => 'nullable|url|max:500',
            'remove_generator_image' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $oldSlug = $theme->slug;
        $generatorImageUrl = $theme->generator_image_url;

        if ($request->boolean('remove_generator_image')) {
            if ($generatorImageUrl && !str_starts_with($generatorImageUrl, 'http')) {
                Storage::disk('public')->delete($generatorImageUrl);
            }
            $generatorImageUrl = null;
        }

        if ($request->hasFile('generator_image')) {
            if ($theme->generator_image_url && !str_starts_with($theme->generator_image_url, 'http')) {
                Storage::disk('public')->delete($theme->generator_image_url);
            }
            $ext = $request->file('generator_image')->getClientOriginalExtension() ?: 'png';
            $generatorImageUrl = $request->file('generator_image')->storeAs(
                'generators',
                $validated['slug'] . '.' . $ext,
                'public'
            );
        } elseif (!empty($validated['generator_image_url_external'])) {
            if ($generatorImageUrl && !str_starts_with($generatorImageUrl, 'http')) {
                Storage::disk('public')->delete($generatorImageUrl);
            }
            $generatorImageUrl = $validated['generator_image_url_external'];
        }

        if ($oldSlug !== $validated['slug']
            && $generatorImageUrl
            && !str_starts_with($generatorImageUrl, 'http')) {
            $basename = basename($generatorImageUrl);
            if (str_starts_with($basename, $oldSlug . '.')) {
                $ext = pathinfo($basename, PATHINFO_EXTENSION) ?: 'png';
                $newPath = 'generators/' . $validated['slug'] . '.' . $ext;
                if (Storage::disk('public')->exists($generatorImageUrl)) {
                    Storage::disk('public')->move($generatorImageUrl, $newPath);
                    $generatorImageUrl = $newPath;
                }
            }
        }

        $theme->update(array_merge(
            collect($validated)->except(['generator_image', 'generator_image_url_external', 'remove_generator_image'])->toArray(),
            ['generator_image_url' => $generatorImageUrl]
        ));

        return redirect()->route('admin.themes')->with('success', 'Тематика обновлена');
    }

    public function deleteTheme(Theme $theme)
    {
        $theme->itemDefinitions()->each(function ($def) {
            if ($def->image_url && !str_starts_with($def->image_url, 'http')) {
                Storage::disk('public')->delete($def->image_url);
            }
        });
        if ($theme->generator_image_url && !str_starts_with($theme->generator_image_url, 'http')) {
            Storage::disk('public')->delete($theme->generator_image_url);
        }
        $theme->delete();

        return redirect()->route('admin.themes')->with('success', 'Тематика удалена');
    }

    // ── Item Definitions ────────────────────────────────────

    public function itemDefinitions(Theme $theme)
    {
        $items = $theme->itemDefinitions()->orderBy('level')->get();
        return view('admin.item-definitions', compact('theme', 'items'));
    }

    public function createItemDefinition(Theme $theme)
    {
        $nextLevel = ($theme->itemDefinitions()->max('level') ?? 0) + 1;
        return view('admin.item-definition-form', [
            'theme' => $theme,
            'itemDefinition' => null,
            'nextLevel' => $nextLevel,
        ]);
    }

    public function storeItemDefinition(Request $request, Theme $theme)
    {
        $validated = $request->validate([
            'level' => 'required|integer|min:1|unique:item_definitions,level,NULL,id,theme_id,' . $theme->id,
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'image_url_external' => 'nullable|url|max:500',
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['name']);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('items/' . $theme->slug, 'public');
        } elseif (!empty($validated['image_url_external'])) {
            $imageUrl = $validated['image_url_external'];
        }

        $theme->itemDefinitions()->create([
            'level' => $validated['level'],
            'name' => $validated['name'],
            'slug' => $slug,
            'image_url' => $imageUrl,
        ]);

        $this->syncChainConfig($theme);

        return redirect()->route('admin.item-definitions', $theme)->with('success', 'Предмет добавлен');
    }

    public function editItemDefinition(Theme $theme, ItemDefinition $itemDefinition)
    {
        return view('admin.item-definition-form', [
            'theme' => $theme,
            'itemDefinition' => $itemDefinition,
            'nextLevel' => null,
        ]);
    }

    public function updateItemDefinition(Request $request, Theme $theme, ItemDefinition $itemDefinition)
    {
        $validated = $request->validate([
            'level' => 'required|integer|min:1|unique:item_definitions,level,' . $itemDefinition->id . ',id,theme_id,' . $theme->id,
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'image_url_external' => 'nullable|url|max:500',
            'remove_image' => 'boolean',
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['name']);

        $imageUrl = $itemDefinition->image_url;

        if ($request->boolean('remove_image')) {
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                Storage::disk('public')->delete($imageUrl);
            }
            $imageUrl = null;
        }

        if ($request->hasFile('image')) {
            if ($itemDefinition->image_url && !str_starts_with($itemDefinition->image_url, 'http')) {
                Storage::disk('public')->delete($itemDefinition->image_url);
            }
            $imageUrl = $request->file('image')->store('items/' . $theme->slug, 'public');
        } elseif (!empty($validated['image_url_external'])) {
            $imageUrl = $validated['image_url_external'];
        }

        $itemDefinition->update([
            'level' => $validated['level'],
            'name' => $validated['name'],
            'slug' => $slug,
            'image_url' => $imageUrl,
        ]);

        $this->syncChainConfig($theme);

        return redirect()->route('admin.item-definitions', $theme)->with('success', 'Предмет обновлён');
    }

    public function deleteItemDefinition(Theme $theme, ItemDefinition $itemDefinition)
    {
        if ($itemDefinition->image_url && !str_starts_with($itemDefinition->image_url, 'http')) {
            Storage::disk('public')->delete($itemDefinition->image_url);
        }
        $itemDefinition->delete();

        $this->syncChainConfig($theme);

        return redirect()->route('admin.item-definitions', $theme)->with('success', 'Предмет удалён');
    }

    public function generateIcon(Theme $theme, ItemDefinition $itemDefinition, IconGeneratorService $service)
    {
        try {
            if ($itemDefinition->image_url && !str_starts_with($itemDefinition->image_url, 'http')) {
                Storage::disk('public')->delete($itemDefinition->image_url);
            }

            $relativePath = $service->generateItemIcon($itemDefinition, $theme);

            $itemDefinition->update(['image_url' => $relativePath]);
            $this->syncChainConfig($theme);

            return response()->json([
                'success' => true,
                'image_url' => '/storage/' . $relativePath,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function generateGeneratorIcon(Theme $theme, IconGeneratorService $service)
    {
        try {
            if ($theme->generator_image_url && !str_starts_with($theme->generator_image_url, 'http')) {
                Storage::disk('public')->delete($theme->generator_image_url);
            }

            $relativePath = $service->generateGeneratorIcon($theme);
            $theme->update(['generator_image_url' => $relativePath]);

            return response()->json([
                'success' => true,
                'image_url' => '/storage/' . $relativePath,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function syncChainConfig(Theme $theme): void
    {
        $definitions = $theme->itemDefinitions()->orderBy('level')->get();
        $chainConfig = $definitions->map(fn($d) => [
            'level' => $d->level,
            'name' => $d->name,
            'sprite_key' => $d->slug,
        ])->values()->toArray();

        $theme->update(['chain_config' => $chainConfig]);
    }

    // ── Characters ──────────────────────────────────────────

    public function characters()
    {
        $characters = Character::with('theme')->withCount('lines')->get();
        return view('admin.characters', compact('characters'));
    }

    public function characterLines(Character $character, Request $request)
    {
        $query = $character->lines();
        if ($trigger = $request->get('trigger')) {
            $query->where('trigger', $trigger);
        }
        $lines = $query->orderBy('trigger')->orderByDesc('priority')->paginate(50);
        $triggers = CharacterLine::where('character_id', $character->id)->distinct('trigger')->pluck('trigger');

        return view('admin.character-lines', compact('character', 'lines', 'triggers'));
    }

    public function storeLine(Request $request, Character $character)
    {
        $validated = $request->validate([
            'trigger' => 'required|string',
            'text' => 'required|string',
            'priority' => 'required|integer|min:1|max:100',
            'conditions' => 'nullable|json',
            'max_shows' => 'required|integer|min:1',
            'cooldown_hours' => 'required|integer|min:0',
        ]);

        $validated['conditions'] = json_decode($validated['conditions'] ?? '{}', true) ?? [];

        $character->lines()->create($validated);
        return back()->with('success', 'Реплика добавлена');
    }

    public function deleteLine(CharacterLine $line)
    {
        $line->delete();
        return back()->with('success', 'Реплика удалена');
    }
}
