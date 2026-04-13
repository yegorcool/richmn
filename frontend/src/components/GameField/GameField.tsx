import { useEffect, useRef, useState, useCallback } from 'react';
import { Application, Container, Graphics, Text, TextStyle, FederatedPointerEvent, Sprite, Texture, Assets } from 'pixi.js';
import { useGame } from '@/contexts/GameContext';
import { usePlatform } from '@/contexts/PlatformContext';
import { gameApi } from '@/services/GameApi';
import { GRID_WIDTH, GRID_HEIGHT, CELL_SIZE, CELL_GAP } from '@/config/constants';
import type { GameItem, Generator } from '@/types/game';

const FIELD_WIDTH = GRID_WIDTH * (CELL_SIZE + CELL_GAP) + CELL_GAP;
const FIELD_HEIGHT = GRID_HEIGHT * (CELL_SIZE + CELL_GAP) + CELL_GAP;

const textureCache = new Map<string, Texture>();

export function GameField() {
  const canvasRef = useRef<HTMLDivElement>(null);
  const appRef = useRef<Application | null>(null);
  const itemSpritesRef = useRef<Map<number, Container>>(new Map());
  const generatorSpritesRef = useRef<Map<number, Container>>(new Map());
  const dragRef = useRef<{ itemId: number; startX: number; startY: number; sprite: Container } | null>(null);
  const animLayerRef = useRef<Container | null>(null);
  const [appReady, setAppReady] = useState(false);
  const { items, generators, energy, setEnergy, refreshState } = useGame();
  const platform = usePlatform();
  const itemsRef = useRef(items);
  const generatorsRef = useRef(generators);
  const energyRef = useRef(energy);

  useEffect(() => { itemsRef.current = items; }, [items]);
  useEffect(() => { generatorsRef.current = generators; }, [generators]);
  useEffect(() => { energyRef.current = energy; }, [energy]);

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
        backgroundColor: 0xFFF5E6,
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

  const playSpawnAnimation = async (container: Container, fromX: number, fromY: number, toX: number, toY: number) => {
    container.x = fromX;
    container.y = fromY;
    container.scale.set(0);
    container.alpha = 1;

    await animateTween(container, { scaleX: 1.25, scaleY: 1.25 }, 200, easeOutBack);
    await animateTween(container, { scaleX: 1, scaleY: 1, x: toX, y: toY }, 200, easeOutQuad);

    spawnParticles(fromX, fromY, 0x98D8C8);
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

  // ── Texture Loading ───────────────────────────────────────

  const loadItemTexture = async (imageUrl: string): Promise<Texture | null> => {
    if (textureCache.has(imageUrl)) {
      return textureCache.get(imageUrl)!;
    }
    try {
      const texture = await Assets.load(imageUrl);
      textureCache.set(imageUrl, texture);
      return texture;
    } catch {
      return null;
    }
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

    const levelColors = [
      0xFFB7B2, 0xA8E6CF, 0xD4A5FF, 0xFFE156, 0x87CEEB,
      0xFF9A76, 0xC9B1FF, 0x98D8C8, 0xF7DC6F, 0xFFD700,
    ];

    const bg = new Graphics();
    const color = levelColors[(item.item_level - 1) % levelColors.length];
    bg.roundRect(-CELL_SIZE / 2, -CELL_SIZE / 2, CELL_SIZE, CELL_SIZE, 10);
    bg.fill({ color });
    if (item.item_level >= 8) {
      bg.stroke({ color: 0xFFD700, width: 2 });
    }
    container.addChild(bg);

    if (item.image_url) {
      loadItemTexture(item.image_url).then((texture) => {
        if (texture && !container.destroyed) {
          const sprite = new Sprite(texture);
          const iconSize = CELL_SIZE * 0.65;
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.y = -2;
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

    const style = new TextStyle({
      fontSize: 10,
      fontFamily: 'Arial',
      fill: 0x4A4A4A,
      align: 'center',
    });
    const label = new Text({ text: `Lv${item.item_level}`, style });
    label.anchor.set(0.5);
    label.y = CELL_SIZE / 2 - 10;
    container.addChild(label);

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

          playChainCombo(targetPos.x, targetPos.y, result.chain_length);

          await refreshState();

          if (result.character_line) {
            window.dispatchEvent(new CustomEvent('character-line', { detail: result.character_line }));
          }
        } catch {
          container.x = drag.startX;
          container.y = drag.startY;
          await refreshState();
        }
      } else {
        try {
          await gameApi.moveItem(item.id, gx, gy);
          await refreshState();
        } catch {
          container.x = drag.startX;
          container.y = drag.startY;
        }
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
    bg.fill({ color: gen.type === 'chargeable' ? 0xFFE4B5 : 0xB5E4FF });
    bg.stroke({ color: 0xCCBBAA, width: 2 });
    container.addChild(bg);

    const slug = gen.theme?.slug;
    const imageUrl = slug ? getGeneratorImageUrl(slug) : null;

    const chargeText = new Text({
      text: gen.type === 'chargeable' ? `${gen.charges_left}/${gen.max_charges}` : (gen.cooldown_until ? '⏳' : '✓'),
      style: new TextStyle({ fontSize: 10, fill: 0x666666 }),
    });
    chargeText.anchor.set(0.5);
    chargeText.y = 14;
    container.addChild(chargeText);

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
      loadItemTexture(imageUrl).then((texture) => {
        if (container.destroyed) return;
        if (texture) {
          const sprite = new Sprite(texture);
          const iconSize = CELL_SIZE * 0.55;
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.y = -4;
          insertGeneratorIcon(sprite);
        } else {
          addEmojiIcon();
        }
      });
    } else {
      addEmojiIcon();
    }

    container.on('pointertap', async () => {
      if (energyRef.current <= 0) {
        platform.hapticFeedback('notification');
        shakeSprite(container);
        window.dispatchEvent(new CustomEvent('no-energy'));
        return;
      }

      try {
        platform.hapticFeedback('selection');
        const result = await gameApi.tapGenerator(gen.id);
        setEnergy(result.energy);

        const genPos = gridToPixel(gen.grid_x, gen.grid_y);
        const itemPos = gridToPixel(result.item.grid_x, result.item.grid_y);

        await refreshState();

        const newSprite = itemSpritesRef.current.get(result.item.id);
        if (newSprite) {
          await playSpawnAnimation(newSprite, genPos.x, genPos.y, itemPos.x, itemPos.y);
        }
      } catch (err: any) {
        if (err?.response?.status === 403) {
          platform.hapticFeedback('notification');
          shakeSprite(container);
          window.dispatchEvent(new CustomEvent('no-energy'));
        } else {
          console.error('Generator tap failed', err);
        }
      }
    });

    return container;
  };

  return (
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
  );
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
