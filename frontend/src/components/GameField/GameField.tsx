import { useEffect, useRef, useState, useCallback } from 'react';
import { Application, Container, Graphics, Text, TextStyle, FederatedPointerEvent, Sprite, Texture, Assets } from 'pixi.js';
import { useGame } from '@/contexts/GameContext';
import { usePlatform } from '@/contexts/PlatformContext';
import { gameApi } from '@/services/GameApi';
import { GRID_WIDTH, GRID_HEIGHT, CELL_SIZE, CELL_GAP } from '@/config/constants';
import type { GameItem, Generator } from '@/types/game';

const FIELD_WIDTH = GRID_WIDTH * (CELL_SIZE + CELL_GAP) + CELL_GAP;
const FIELD_HEIGHT = GRID_HEIGHT * (CELL_SIZE + CELL_GAP) + CELL_GAP;

/** Pixels (screen) before a press on a generator counts as a drag instead of a tap. */
const GENERATOR_DRAG_THRESHOLD_PX = 12;

const textureCache = new Map<string, Texture>();

/** One background per theme — same `theme_id` always maps to the same color. */
const THEME_CELL_BG_COLORS = [
  0xFFB7B2, 0xA8E6CF, 0xD4A5FF, 0xFFE156, 0x87CEEB,
  0xFF9A76, 0xC9B1FF, 0x98D8C8, 0xF7DC6F, 0xE8B4BC,
];

function themeCellBgColor(themeId: number): number {
  const n = THEME_CELL_BG_COLORS.length;
  const idx = ((themeId % n) + n) % n;
  return THEME_CELL_BG_COLORS[idx];
}

/** Iconify SVGs default to a small intrinsic size; rasterize at ~display×DPR so Pixi scaling stays sharp. */
function resolveTextureUrl(imageUrl: string, displayLogicalPx: number): string {
  if (!imageUrl.includes('api.iconify.design') || !/\.svg(\?|$)/i.test(imageUrl)) {
    return imageUrl;
  }
  const dpr = typeof window !== 'undefined' ? window.devicePixelRatio || 1 : 1;
  const target = Math.min(256, Math.max(96, Math.ceil(displayLogicalPx * dpr)));
  const join = imageUrl.includes('?') ? '&' : '?';
  return `${imageUrl}${join}width=${target}&height=${target}`;
}

async function loadItemTexture(imageUrl: string, displayLogicalPx: number): Promise<Texture | null> {
  const resolved = resolveTextureUrl(imageUrl, displayLogicalPx);
  if (textureCache.has(resolved)) {
    return textureCache.get(resolved)!;
  }
  try {
    const texture = await Assets.load(resolved);
    textureCache.set(resolved, texture);
    return texture;
  } catch {
    return null;
  }
}

export function GameField() {
  const canvasRef = useRef<HTMLDivElement>(null);
  const appRef = useRef<Application | null>(null);
  const itemSpritesRef = useRef<Map<number, Container>>(new Map());
  const generatorSpritesRef = useRef<Map<number, Container>>(new Map());
  const dragRef = useRef<{ itemId: number; startX: number; startY: number; sprite: Container } | null>(null);
  const generatorPointerRef = useRef<{
    generatorId: number;
    startSpriteX: number;
    startSpriteY: number;
    startGridX: number;
    startGridY: number;
    pointerStartX: number;
    pointerStartY: number;
    dragging: boolean;
  } | null>(null);
  const animLayerRef = useRef<Container | null>(null);
  const tapQueueRef = useRef<number[]>([]);
  const tapProcessingRef = useRef(false);
  const generatorTooltipDismissRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const [generatorTooltip, setGeneratorTooltip] = useState<{
    message: string;
    anchorX: number;
    anchorY: number;
  } | null>(null);
  const [appReady, setAppReady] = useState(false);
  const {
    items, generators, energy, setEnergy,
    addItem, removeItems, updateItemPosition,
    replaceGenerator, updateGeneratorPosition,
  } = useGame();
  const platform = usePlatform();
  const itemsRef = useRef(items);
  const generatorsRef = useRef(generators);
  const energyRef = useRef(energy);

  useEffect(() => { itemsRef.current = items; }, [items]);
  useEffect(() => { generatorsRef.current = generators; }, [generators]);
  useEffect(() => {
    if (!tapProcessingRef.current && tapQueueRef.current.length === 0) {
      energyRef.current = energy;
    }
  }, [energy]);

  const gridToPixel = useCallback((gx: number, gy: number) => ({
    x: CELL_GAP + gx * (CELL_SIZE + CELL_GAP) + CELL_SIZE / 2,
    y: CELL_GAP + gy * (CELL_SIZE + CELL_GAP) + CELL_SIZE / 2,
  }), []);

  const pixelToGrid = useCallback((px: number, py: number) => ({
    gx: Math.floor((px - CELL_GAP) / (CELL_SIZE + CELL_GAP)),
    gy: Math.floor((py - CELL_GAP) / (CELL_SIZE + CELL_GAP)),
  }), []);

  useEffect(() => {
    if (!canvasRef.current || appRef.current) return;

    let cancelled = false;

    const initApp = async () => {
      const app = new Application();
      await app.init({
        width: FIELD_WIDTH,
        height: FIELD_HEIGHT,
        backgroundAlpha: 0,
        antialias: true,
        resolution: window.devicePixelRatio || 1,
        autoDensity: true,
      });

      if (cancelled) {
        app.destroy(true);
        return;
      }

      const host = canvasRef.current;
      if (!host) {
        app.destroy(true);
        return;
      }

      host.appendChild(app.canvas as HTMLCanvasElement);
      appRef.current = app;

      drawGrid(app);

      const animLayer = new Container();
      animLayer.zIndex = 9999;
      app.stage.addChild(animLayer);
      animLayerRef.current = animLayer;
      app.stage.sortableChildren = true;

      if (!cancelled) {
        setAppReady(true);
      }
    };

    initApp();

    return () => {
      cancelled = true;
      setAppReady(false);
      animLayerRef.current = null;
      if (generatorTooltipDismissRef.current) {
        clearTimeout(generatorTooltipDismissRef.current);
        generatorTooltipDismissRef.current = null;
      }
      if (appRef.current) {
        appRef.current.destroy(true);
        appRef.current = null;
      }
    };
  }, []);

  useEffect(() => {
    if (appReady && appRef.current) {
      renderItems(appRef.current);
    }
  }, [items, generators, appReady]);

  useEffect(() => {
    const app = appRef.current;
    if (!appReady || !app) return;

    const anyRecharging = generators.some(generatorHasActiveRechargeCooldown);
    if (!anyRecharging) return;

    const tick = () => {
      for (const gen of generatorsRef.current) {
        const container = generatorSpritesRef.current.get(gen.id);
        const chargeText = container?.userData?.chargeText as Text | undefined;
        if (!chargeText) continue;
        chargeText.text = generatorUnderIconLabel(gen);
      }
    };

    app.ticker.add(tick);
    tick();
    return () => {
      app.ticker.remove(tick);
    };
  }, [generators, appReady]);

  const drawGrid = (app: Application) => {
    const grid = new Graphics();

    for (let y = 0; y < GRID_HEIGHT; y++) {
      for (let x = 0; x < GRID_WIDTH; x++) {
        const px = CELL_GAP + x * (CELL_SIZE + CELL_GAP);
        const py = CELL_GAP + y * (CELL_SIZE + CELL_GAP);

        grid.roundRect(px, py, CELL_SIZE, CELL_SIZE, 8);
        grid.fill({ color: 0xF0E6D6, alpha: 0.5 });
        grid.stroke({ color: 0xE0D6C6, width: 1 });
      }
    }

    app.stage.addChild(grid);
  };

  // ── Animation Helpers ─────────────────────────────────────

  const animateTween = (
    target: Container,
    props: Partial<{ x: number; y: number; scaleX: number; scaleY: number; alpha: number }>,
    duration: number,
    easing: (t: number) => number = easeOutBack,
  ): Promise<void> => {
    return new Promise((resolve) => {
      const app = appRef.current;
      if (!app) { resolve(); return; }

      const start = {
        x: target.x,
        y: target.y,
        scaleX: target.scale.x,
        scaleY: target.scale.y,
        alpha: target.alpha,
      };

      let elapsed = 0;

      const tick = (dt: any) => {
        elapsed += dt.deltaMS ?? 16.67;
        const t = Math.min(elapsed / duration, 1);
        const e = easing(t);

        if (props.x !== undefined) target.x = start.x + (props.x - start.x) * e;
        if (props.y !== undefined) target.y = start.y + (props.y - start.y) * e;
        if (props.scaleX !== undefined) target.scale.x = start.scaleX + (props.scaleX - start.scaleX) * e;
        if (props.scaleY !== undefined) target.scale.y = start.scaleY + (props.scaleY - start.scaleY) * e;
        if (props.alpha !== undefined) target.alpha = start.alpha + (props.alpha - start.alpha) * e;

        if (t >= 1) {
          app.ticker.remove(tick);
          resolve();
        }
      };

      app.ticker.add(tick);
    });
  };

  const playOverlaySpawnAnimation = (
    themeId: number,
    imageUrl: string | null | undefined,
    themeSlug: string | undefined,
    fromX: number,
    fromY: number,
    toX: number,
    toY: number,
  ) => {
    const layer = animLayerRef.current;
    if (!layer) return;

    const temp = new Container();
    temp.x = fromX;
    temp.y = fromY;
    temp.scale.set(0);

    const bg = new Graphics();
    bg.roundRect(-CELL_SIZE / 2, -CELL_SIZE / 2, CELL_SIZE, CELL_SIZE, 10);
    bg.fill({ color: themeCellBgColor(themeId) });
    temp.addChild(bg);

    const emoji = new Text({
      text: getThemeEmoji(themeSlug ?? ''),
      style: new TextStyle({ fontSize: 20 }),
    });
    emoji.anchor.set(0.5);
    emoji.y = -8;
    temp.addChild(emoji);

    if (imageUrl) {
      const iconSize = CELL_SIZE * 0.65;
      loadItemTexture(imageUrl, iconSize).then((texture) => {
        if (texture && !temp.destroyed) {
          const sprite = new Sprite(texture);
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.y = -2;
          sprite.roundPixels = true;
          temp.removeChild(emoji);
          emoji.destroy();
          temp.addChild(sprite);
        }
      });
    }

    layer.addChild(temp);

    animateTween(temp, { scaleX: 1.25, scaleY: 1.25 }, 150, easeOutBack)
      .then(() => animateTween(temp, { scaleX: 1, scaleY: 1, x: toX, y: toY }, 150, easeOutQuad))
      .then(() => {
        spawnParticles(fromX, fromY, 0x98D8C8);
        animateTween(temp, { alpha: 0 }, 100, easeOutQuad).then(() => {
          temp.destroy();
        });
      });
  };

  const playMergeAnimation = async (
    sprite1: Container,
    sprite2: Container,
    targetX: number,
    targetY: number,
  ): Promise<void> => {
    await Promise.all([
      animateTween(sprite1, { scaleX: 0, scaleY: 0, alpha: 0, x: targetX, y: targetY }, 200, easeInQuad),
      animateTween(sprite2, { scaleX: 0, scaleY: 0, alpha: 0 }, 200, easeInQuad),
    ]);

    const flash = new Graphics();
    flash.circle(0, 0, CELL_SIZE * 0.6);
    flash.fill({ color: 0xFFFFFF, alpha: 0.9 });
    flash.x = targetX;
    flash.y = targetY;
    flash.scale.set(0);
    animLayerRef.current?.addChild(flash);

    await animateTween(flash, { scaleX: 1.5, scaleY: 1.5, alpha: 0 }, 250, easeOutQuad);
    flash.destroy();

    spawnParticles(targetX, targetY, 0xFFD700);
  };

  const playChainCombo = (x: number, y: number, chainLength: number) => {
    if (chainLength <= 1) return;
    const layer = animLayerRef.current;
    if (!layer) return;

    const comboText = new Text({
      text: `x${chainLength}!`,
      style: new TextStyle({ fontSize: 22, fontWeight: 'bold', fill: 0xFF6B6B, stroke: { color: 0xFFFFFF, width: 3 } }),
    });
    comboText.anchor.set(0.5);
    comboText.x = x;
    comboText.y = y - CELL_SIZE / 2;
    layer.addChild(comboText);

    animateTween(comboText, { y: y - CELL_SIZE * 1.5, alpha: 0 }, 800, easeOutQuad).then(() => {
      comboText.destroy();
    });
  };

  const spawnParticles = (cx: number, cy: number, color: number) => {
    const layer = animLayerRef.current;
    if (!layer) return;

    for (let i = 0; i < 6; i++) {
      const p = new Graphics();
      const size = 3 + Math.random() * 4;
      p.circle(0, 0, size);
      p.fill({ color, alpha: 0.9 });
      p.x = cx;
      p.y = cy;
      layer.addChild(p);

      const angle = (Math.PI * 2 * i) / 6 + Math.random() * 0.5;
      const dist = 20 + Math.random() * 25;
      const tx = cx + Math.cos(angle) * dist;
      const ty = cy + Math.sin(angle) * dist;

      animateTween(p, { x: tx, y: ty, alpha: 0, scaleX: 0, scaleY: 0 }, 400 + Math.random() * 200, easeOutQuad).then(() => {
        p.destroy();
      });
    }
  };

  const shakeSprite = async (container: Container) => {
    const origX = container.x;
    for (let i = 0; i < 3; i++) {
      container.x = origX + 4;
      await new Promise((r) => setTimeout(r, 50));
      container.x = origX - 4;
      await new Promise((r) => setTimeout(r, 50));
    }
    container.x = origX;
  };

  // ── Tap Queue ────────────────────────────────────────────

  const showGeneratorNotReadyTooltip = (generatorId: number, cooldownUntil: string | null | undefined) => {
    if (generatorTooltipDismissRef.current) {
      clearTimeout(generatorTooltipDismissRef.current);
    }
    const gen = generatorsRef.current.find((g) => g.id === generatorId);
    if (!gen) return;
    const msg = formatGeneratorNotReadyMessage(cooldownUntil);
    const host = canvasRef.current;
    let anchorX = window.innerWidth / 2;
    let anchorY = window.innerHeight / 2;
    if (host) {
      const r = host.getBoundingClientRect();
      const sx = r.width / FIELD_WIDTH;
      const sy = r.height / FIELD_HEIGHT;
      const centerX = CELL_GAP + gen.grid_x * (CELL_SIZE + CELL_GAP) + CELL_SIZE / 2;
      const cellTop = CELL_GAP + gen.grid_y * (CELL_SIZE + CELL_GAP);
      anchorX = r.left + centerX * sx;
      anchorY = r.top + cellTop * sy;
    }
    setGeneratorTooltip({ message: msg, anchorX, anchorY });
    generatorTooltipDismissRef.current = setTimeout(() => {
      setGeneratorTooltip(null);
      generatorTooltipDismissRef.current = null;
    }, 4000);
  };

  const processTapQueue = async () => {
    if (tapProcessingRef.current) return;
    tapProcessingRef.current = true;

    while (tapQueueRef.current.length > 0) {
      const generatorId = tapQueueRef.current.shift()!;

      try {
        const result = await gameApi.tapGenerator(generatorId);

        if (!result.success) {
          const restoreCount = tapQueueRef.current.length + 1;
          energyRef.current += restoreCount;
          setEnergy(energyRef.current);
          tapQueueRef.current.length = 0;

          const sprite = generatorSpritesRef.current.get(generatorId);
          if (sprite) {
            platform.hapticFeedback('notification');
            shakeSprite(sprite);
          }

          if (result.error === 'Generator not ready') {
            showGeneratorNotReadyTooltip(generatorId, result.cooldown_until);
          } else if (result.error === 'No empty slots') {
            // field is full, nothing else to do
          }
          break;
        }

        if (result.generator) replaceGenerator(result.generator);
        addItem(result.item);

        const pending = tapQueueRef.current.length;
        energyRef.current = result.energy - pending;
        setEnergy(energyRef.current);

        const gen = generatorsRef.current.find((g) => g.id === generatorId);
        const genPos = gen ? gridToPixel(gen.grid_x, gen.grid_y) : gridToPixel(0, 0);
        const itemPos = gridToPixel(result.item.grid_x, result.item.grid_y);

        playOverlaySpawnAnimation(
          result.item.theme_id,
          result.item.image_url,
          result.item.theme_slug,
          genPos.x, genPos.y,
          itemPos.x, itemPos.y,
        );
      } catch (err: unknown) {
        const is403 =
          err && typeof err === 'object' && 'response' in err &&
          (err as { response?: { status?: number } }).response?.status === 403;

        const restoreCount = tapQueueRef.current.length + 1;
        energyRef.current += restoreCount;
        setEnergy(energyRef.current);
        tapQueueRef.current.length = 0;

        if (is403) {
          platform.hapticFeedback('notification');
          const sprite = generatorSpritesRef.current.get(generatorId);
          if (sprite) shakeSprite(sprite);
          window.dispatchEvent(new CustomEvent('no-energy'));
        } else {
          console.error('Generator tap failed', err);
        }
        break;
      }
    }

    tapProcessingRef.current = false;
  };

  const enqueueGeneratorTap = (generatorId: number) => {
    if (energyRef.current <= 0) {
      platform.hapticFeedback('notification');
      const sprite = generatorSpritesRef.current.get(generatorId);
      if (sprite) shakeSprite(sprite);
      window.dispatchEvent(new CustomEvent('no-energy'));
      return;
    }

    energyRef.current -= 1;
    setEnergy(energyRef.current);
    platform.hapticFeedback('selection');

    tapQueueRef.current.push(generatorId);
    processTapQueue();
  };

  // ── Render ────────────────────────────────────────────────

  const renderItems = (app: Application) => {
    itemSpritesRef.current.forEach((sprite) => {
      app.stage.removeChild(sprite);
      sprite.destroy();
    });
    itemSpritesRef.current.clear();

    generatorSpritesRef.current.forEach((sprite) => {
      app.stage.removeChild(sprite);
      sprite.destroy();
    });
    generatorSpritesRef.current.clear();

    const currentItems = itemsRef.current;
    const currentGenerators = generatorsRef.current;

    currentItems.forEach((item) => {
      const container = createItemSprite(item);
      const pos = gridToPixel(item.grid_x, item.grid_y);
      container.x = pos.x;
      container.y = pos.y;
      app.stage.addChild(container);
      itemSpritesRef.current.set(item.id, container);
    });

    currentGenerators.forEach((gen) => {
      const container = createGeneratorSprite(gen);
      const pos = gridToPixel(gen.grid_x, gen.grid_y);
      container.x = pos.x;
      container.y = pos.y;
      app.stage.addChild(container);
      generatorSpritesRef.current.set(gen.id, container);
    });
  };

  const createItemSprite = (item: GameItem): Container => {
    const container = new Container();
    container.pivot.set(0, 0);
    container.eventMode = 'static';
    container.cursor = 'pointer';

    const bg = new Graphics();
    const color = themeCellBgColor(item.theme_id);
    bg.roundRect(-CELL_SIZE / 2, -CELL_SIZE / 2, CELL_SIZE, CELL_SIZE, 10);
    bg.fill({ color });
    if (item.item_level >= 8) {
      bg.stroke({ color: 0xFFD700, width: 2 });
    }
    container.addChild(bg);

    if (item.image_url) {
      const iconSize = CELL_SIZE * 0.65;
      loadItemTexture(item.image_url, iconSize).then((texture) => {
        if (texture && !container.destroyed) {
          const sprite = new Sprite(texture);
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.y = -2;
          sprite.roundPixels = true;
          container.addChild(sprite);
        }
      });
    } else {
      const themeIcon = new Text({
        text: getThemeEmoji(item.theme_slug),
        style: new TextStyle({ fontSize: 20 }),
      });
      themeIcon.anchor.set(0.5);
      themeIcon.y = -8;
      container.addChild(themeIcon);
    }

    // ── Drag & Drop ──

    container.on('pointerdown', (_e: FederatedPointerEvent) => {
      dragRef.current = {
        itemId: item.id,
        startX: container.x,
        startY: container.y,
        sprite: container,
      };
      container.alpha = 0.8;
      container.scale.set(1.1);
      container.zIndex = 1000;
    });

    container.on('globalpointermove', (e: FederatedPointerEvent) => {
      if (dragRef.current?.itemId === item.id && container.parent) {
        const pos = e.getLocalPosition(container.parent);
        container.x = pos.x;
        container.y = pos.y;
      }
    });

    container.on('pointerup', async (e: FederatedPointerEvent) => {
      if (!dragRef.current || dragRef.current.itemId !== item.id || !container.parent) return;

      const pos = e.getLocalPosition(container.parent);
      const { gx, gy } = pixelToGrid(pos.x, pos.y);
      const drag = dragRef.current;
      dragRef.current = null;

      container.alpha = 1;
      container.scale.set(1);
      container.zIndex = 0;

      if (gx < 0 || gx >= GRID_WIDTH || gy < 0 || gy >= GRID_HEIGHT) {
        container.x = drag.startX;
        container.y = drag.startY;
        return;
      }

      const targetItem = itemsRef.current.find(
        (i) => i.grid_x === gx && i.grid_y === gy && i.id !== item.id
      );

      if (targetItem && targetItem.theme_slug === item.theme_slug && targetItem.item_level === item.item_level) {
        try {
          platform.hapticFeedback('impact');

          const targetSprite = itemSpritesRef.current.get(targetItem.id);
          const targetPos = gridToPixel(gx, gy);

          if (targetSprite) {
            await playMergeAnimation(container, targetSprite, targetPos.x, targetPos.y);
          }

          const result = await gameApi.merge(item.id, targetItem.id);
          setEnergy(result.energy);
          removeItems([item.id, targetItem.id]);
          addItem(result.new_item);

          playChainCombo(targetPos.x, targetPos.y, result.chain_length);

          if (result.character_line) {
            window.dispatchEvent(new CustomEvent('character-line', { detail: result.character_line }));
          }
        } catch {
          container.x = drag.startX;
          container.y = drag.startY;
        }
      } else {
        updateItemPosition(item.id, gx, gy);
        gameApi.moveItem(item.id, gx, gy).catch(() => {
          updateItemPosition(item.id, pixelToGrid(drag.startX, drag.startY).gx, pixelToGrid(drag.startX, drag.startY).gy);
        });
      }
    });

    container.on('pointerupoutside', () => {
      if (dragRef.current?.itemId === item.id) {
        container.x = dragRef.current.startX;
        container.y = dragRef.current.startY;
        container.alpha = 1;
        container.scale.set(1);
        container.zIndex = 0;
        dragRef.current = null;
      }
    });

    return container;
  };

  const createGeneratorSprite = (gen: Generator): Container => {
    const container = new Container();
    container.eventMode = 'static';
    container.cursor = 'pointer';

    const bg = new Graphics();
    bg.roundRect(-CELL_SIZE / 2, -CELL_SIZE / 2, CELL_SIZE, CELL_SIZE, 10);
    bg.fill({ color: themeCellBgColor(gen.theme_id) });
    container.addChild(bg);

    const slug = gen.theme?.slug;
    const imageUrl = slug ? getGeneratorImageUrl(slug) : null;

    const chargeText = new Text({
      text: generatorUnderIconLabel(gen),
      style: new TextStyle({ fontSize: 10, fill: 0x666666 }),
    });
    chargeText.anchor.set(0.5);
    chargeText.y = 14;
    container.addChild(chargeText);
    container.userData = { ...(container.userData ?? {}), chargeText };

    /** Icon between background (index 0) and charge label (top). */
    const insertGeneratorIcon = (icon: Container) => {
      container.addChildAt(icon, 1);
    };

    const addEmojiIcon = () => {
      const icon = new Text({
        text: getThemeEmoji(slug ?? ''),
        style: new TextStyle({ fontSize: 22 }),
      });
      icon.anchor.set(0.5);
      icon.y = -6;
      insertGeneratorIcon(icon);
    };

    if (imageUrl) {
      const iconSize = CELL_SIZE * 0.55;
      loadItemTexture(imageUrl, iconSize).then((texture) => {
        if (container.destroyed) return;
        if (texture) {
          const sprite = new Sprite(texture);
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.y = -4;
          sprite.roundPixels = true;
          insertGeneratorIcon(sprite);
        } else {
          addEmojiIcon();
        }
      });
    } else {
      addEmojiIcon();
    }

    const finishGeneratorPointer = async (e: FederatedPointerEvent, releasedOutside: boolean) => {
      const ptr = generatorPointerRef.current;
      if (!ptr || ptr.generatorId !== gen.id) return;

      if (ptr.dragging) {
        generatorPointerRef.current = null;
        container.alpha = 1;
        container.scale.set(1);
        container.zIndex = 0;

        if (!container.parent) return;
        const pos = e.getLocalPosition(container.parent);
        const { gx, gy } = pixelToGrid(pos.x, pos.y);

        if (gx < 0 || gx >= GRID_WIDTH || gy < 0 || gy >= GRID_HEIGHT) {
          container.x = ptr.startSpriteX;
          container.y = ptr.startSpriteY;
          return;
        }

        if (gx === ptr.startGridX && gy === ptr.startGridY) {
          container.x = ptr.startSpriteX;
          container.y = ptr.startSpriteY;
          return;
        }

        updateGeneratorPosition(gen.id, gx, gy);
        gameApi.moveGenerator(gen.id, gx, gy).catch(() => {
          updateGeneratorPosition(gen.id, ptr.startGridX, ptr.startGridY);
        });
        return;
      }

      generatorPointerRef.current = null;
      if (!releasedOutside) {
        enqueueGeneratorTap(gen.id);
      }
    };

    container.on('pointerdown', (e: FederatedPointerEvent) => {
      generatorPointerRef.current = {
        generatorId: gen.id,
        startSpriteX: container.x,
        startSpriteY: container.y,
        startGridX: gen.grid_x,
        startGridY: gen.grid_y,
        pointerStartX: e.global.x,
        pointerStartY: e.global.y,
        dragging: false,
      };
    });

    container.on('globalpointermove', (e: FederatedPointerEvent) => {
      const ptr = generatorPointerRef.current;
      if (!ptr || ptr.generatorId !== gen.id || !container.parent) return;
      const dist = Math.hypot(e.global.x - ptr.pointerStartX, e.global.y - ptr.pointerStartY);
      if (dist > GENERATOR_DRAG_THRESHOLD_PX) {
        if (!ptr.dragging) {
          ptr.dragging = true;
          container.alpha = 0.8;
          container.scale.set(1.1);
          container.zIndex = 1000;
        }
        const pos = e.getLocalPosition(container.parent);
        container.x = pos.x;
        container.y = pos.y;
      }
    });

    container.on('pointerup', (e: FederatedPointerEvent) => {
      void finishGeneratorPointer(e, false);
    });

    container.on('pointerupoutside', (e: FederatedPointerEvent) => {
      void finishGeneratorPointer(e, true);
    });

    return container;
  };

  return (
    <>
      <div
        ref={canvasRef}
        style={{
          width: FIELD_WIDTH,
          height: FIELD_HEIGHT,
          margin: '0 auto',
          borderRadius: 16,
          overflow: 'hidden',
          touchAction: 'none',
        }}
      />
      {generatorTooltip && (
        <div
          role="status"
          style={{
            position: 'fixed',
            left: generatorTooltip.anchorX,
            top: generatorTooltip.anchorY,
            transform: 'translate(-50%, calc(-100% - 8px))',
            maxWidth: 220,
            padding: '5px 7px',
            borderRadius: 8,
            background: 'rgba(44, 36, 28, 0.92)',
            color: '#f5f0e8',
            fontSize: 9,
            lineHeight: 1.25,
            textAlign: 'center',
            whiteSpace: 'pre-line',
            pointerEvents: 'none',
            boxShadow: '0 4px 14px rgba(0,0,0,0.2)',
            zIndex: 2500,
          }}
        >
          {generatorTooltip.message}
        </div>
      )}
    </>
  );
}

/** True while generator is empty and server-side cooldown is still in the future. */
function generatorHasActiveRechargeCooldown(gen: Generator): boolean {
  if (gen.charges_left > 0 || !gen.cooldown_until) return false;
  const end = new Date(gen.cooldown_until).getTime();
  return !Number.isNaN(end) && end > Date.now();
}

/** Under-icon line: nothing if charges remain or no active cooldown (no checkmark-style hint). */
function generatorUnderIconLabel(gen: Generator): string {
  if (!generatorHasActiveRechargeCooldown(gen)) return '';
  return formatGeneratorCooldownMmSs(gen.cooldown_until);
}

/** Countdown under generator icon while recharging (mm:ss, minutes unbounded). */
function formatGeneratorCooldownMmSs(cooldownUntil: string | null | undefined): string {
  if (!cooldownUntil) return '0:00';
  const end = new Date(cooldownUntil).getTime();
  const ms = end - Date.now();
  if (Number.isNaN(end) || ms <= 0) return '0:00';
  const totalSec = Math.ceil(ms / 1000);
  const m = Math.floor(totalSec / 60);
  const s = totalSec % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
}

/** User-facing copy when the server rejects a tap while the generator is on cooldown. */
function formatGeneratorNotReadyMessage(cooldownUntil: string | null | undefined): string {
  const lines = (third: string) => `Перезарядка.\nОсталось:\n${third}`;

  if (!cooldownUntil) {
    return lines('ждите ⏳');
  }
  const end = new Date(cooldownUntil).getTime();
  const ms = end - Date.now();
  if (Number.isNaN(end)) {
    return lines('скоро');
  }
  if (ms <= 0) {
    return lines('скоро');
  }
  const totalSec = Math.ceil(ms / 1000);
  const hours = Math.floor(totalSec / 3600);
  const mins = Math.floor((totalSec % 3600) / 60);
  const secs = totalSec % 60;
  let timePhrase: string;
  if (hours > 0) {
    timePhrase = `${hours}\u00a0ч ${mins}\u00a0мин`;
  } else if (mins > 0) {
    timePhrase = secs > 0 ? `${mins}\u00a0мин ${secs}\u00a0с` : `${mins}\u00a0мин`;
  } else {
    timePhrase = `${secs}\u00a0с`;
  }
  return lines(timePhrase);
}

// ── Easing Functions ──────────────────────────────────────

function easeOutBack(t: number): number {
  const c1 = 1.70158;
  const c3 = c1 + 1;
  return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
}

function easeOutQuad(t: number): number {
  return 1 - (1 - t) * (1 - t);
}

function easeInQuad(t: number): number {
  return t * t;
}

// ── Theme Emoji Map ───────────────────────────────────────

/** Thematic generator art (Iconify SVG), aligned with item icons in ItemDefinitionSeeder. */
function getGeneratorImageUrl(slug: string): string | null {
  const map: Record<string, string> = {
    coffee: 'https://api.iconify.design/twemoji/hot-beverage.svg',
    bakery: 'https://api.iconify.design/openmoji/department-store.svg',
    products: 'https://api.iconify.design/twemoji/seedling.svg',
    fabrics: 'https://api.iconify.design/noto/thread.svg',
    pottery: 'https://api.iconify.design/twemoji/amphora.svg',
  };
  return map[slug] ?? null;
}

function getThemeEmoji(slug: string): string {
  const map: Record<string, string> = {
    coffee: '☕',
    bakery: '🧁',
    products: '🥗',
    sweets: '🍬',
    flowers: '🌸',
    tools: '🔧',
    cosmetics: '💄',
    spices: '🌶️',
    fabrics: '🧵',
    pottery: '🏺',
  };
  return map[slug] ?? '📦';
}
