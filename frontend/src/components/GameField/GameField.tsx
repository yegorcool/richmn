import { useEffect, useRef, useState, useCallback } from 'react';
import { Application, Container, Graphics, Text, TextStyle, FederatedPointerEvent, Sprite, Texture, Assets, Ticker } from 'pixi.js';
import { useGame } from '@/contexts/GameContext';
import { usePlatform } from '@/contexts/PlatformContext';
import { gameApi } from '@/services/GameApi';
import { GRID_WIDTH, GRID_HEIGHT, CELL_SIZE, CELL_GAP } from '@/config/constants';
import type { GameItem, Generator, ItemDefinitionMap } from '@/types/game';

const FIELD_WIDTH = GRID_WIDTH * (CELL_SIZE + CELL_GAP) + CELL_GAP;
const FIELD_HEIGHT = GRID_HEIGHT * (CELL_SIZE + CELL_GAP) + CELL_GAP;

/** Pixels (screen) before a press on a generator counts as a drag instead of a tap. */
const GENERATOR_DRAG_THRESHOLD_PX = 12;

/** Idle “breathing” scale pulse on generators: full up+down over this many ms, every period. */
const GENERATOR_IDLE_PULSE_MS = 2000;
const GENERATOR_IDLE_PULSE_PERIOD_MS = 10000;
const GENERATOR_IDLE_PULSE_MAX_SCALE = 1.1;

const textureCache = new Map<string, Texture>();

const ITEM_AND_GENERATOR_IMAGE_VERSION = '4';

/** Cache-bust CDN/static item and generator art (Pixi `Assets.load`). */
function appendItemImageVersion(url: string): string {
  const hashIdx = url.indexOf('#');
  const base = hashIdx >= 0 ? url.slice(0, hashIdx) : url;
  const hash = hashIdx >= 0 ? url.slice(hashIdx) : '';
  const join = base.includes('?') ? '&' : '?';
  return `${base}${join}v=${ITEM_AND_GENERATOR_IMAGE_VERSION}${hash}`;
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
  const resolved = appendItemImageVersion(resolveTextureUrl(imageUrl, displayLogicalPx));
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
  const generatorPulseLayerRef = useRef<Map<number, Container>>(new Map());
  const generatorChargeTextRef = useRef<Map<number, Text>>(new Map());
  const generatorPulseAnchorRef = useRef<number>(Date.now());
  const generatorPulseTickerBindingRef = useRef<{
    app: Application;
    fn: (ticker: Ticker) => void;
  } | null>(null);
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
  const tapQueueRef = useRef<{ generatorId: number; tempId: number; cellKey: string }[]>([]);
  /** Item ids (optimistic temp) that should fly from generator cell with scale-up on first draw. */
  const pendingGeneratorSpawnRef = useRef<Map<number, { fromGx: number; fromGy: number }>>(new Map());
  const tapProcessingRef = useRef(false);
  const tapFlushTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const reservedCellsRef = useRef<Set<string>>(new Set());
  const moveFlushTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
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
    pendingMoves: pendingMovesRef, flushPendingMoves,
    itemsRef, generatorsRef, itemDefinitionsRef,
  } = useGame();
  const platform = usePlatform();
  const energyRef = useRef(energy);

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
      if (moveFlushTimerRef.current) {
        clearTimeout(moveFlushTimerRef.current);
        moveFlushTimerRef.current = null;
      }
      if (tapFlushTimerRef.current) {
        clearTimeout(tapFlushTimerRef.current);
        tapFlushTimerRef.current = null;
      }
      if (generatorTooltipDismissRef.current) {
        clearTimeout(generatorTooltipDismissRef.current);
        generatorTooltipDismissRef.current = null;
      }
      const pulseBind = generatorPulseTickerBindingRef.current;
      if (pulseBind) {
        try {
          pulseBind.app.ticker.remove(pulseBind.fn);
        } catch {
          /* app/ticker may already be tearing down */
        }
        generatorPulseTickerBindingRef.current = null;
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
        const chargeText = generatorChargeTextRef.current.get(gen.id);
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

    /** Checkerboard: light #d0bb99, dark #cbad87 (darker — with slight corner radius). */
    const LIGHT = 0xd0bb99;
    const DARK = 0xcbad87;
    const darkCornerRadius = 4;

    grid.rect(0, 0, FIELD_WIDTH, FIELD_HEIGHT);
    grid.fill({ color: LIGHT });

    for (let y = 0; y < GRID_HEIGHT; y++) {
      for (let x = 0; x < GRID_WIDTH; x++) {
        const px = CELL_GAP + x * (CELL_SIZE + CELL_GAP);
        const py = CELL_GAP + y * (CELL_SIZE + CELL_GAP);
        if ((x + y) % 2 === 1) {
          grid.roundRect(px, py, CELL_SIZE, CELL_SIZE, darkCornerRadius);
          grid.fill({ color: DARK });
        }
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
        if (target.destroyed) {
          app.ticker.remove(tick);
          resolve();
          return;
        }

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

  // ── Move Batching ───────────────────────────────────────

  const flushMoves = useCallback(() => {
    moveFlushTimerRef.current = null;
    flushPendingMoves();
  }, [flushPendingMoves]);

  const scheduleMoveFlush = useCallback(() => {
    if (moveFlushTimerRef.current) return;
    moveFlushTimerRef.current = setTimeout(flushMoves, 250);
  }, [flushMoves]);

  // ── Tap Queue ────────────────────────────────────────────

  const findLocalEmptySlot = (genX: number, genY: number): { x: number; y: number } | null => {
    const occupied = new Set<string>();
    for (const it of itemsRef.current) occupied.add(`${it.grid_x},${it.grid_y}`);
    for (const g of generatorsRef.current) occupied.add(`${g.grid_x},${g.grid_y}`);
    for (const key of reservedCellsRef.current) occupied.add(key);

    const maxRadius = Math.max(GRID_WIDTH, GRID_HEIGHT);
    for (let r = 1; r <= maxRadius; r++) {
      for (let dx = -r; dx <= r; dx++) {
        for (let dy = -r; dy <= r; dy++) {
          if (Math.max(Math.abs(dx), Math.abs(dy)) !== r) continue;
          const x = genX + dx;
          const y = genY + dy;
          if (x >= 0 && x < GRID_WIDTH && y >= 0 && y < GRID_HEIGHT && !occupied.has(`${x},${y}`)) {
            return { x, y };
          }
        }
      }
    }

    return null;
  };

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

  const TAP_BATCH_DELAY_MS = 80;
  const GENERATOR_SPAWN_MS = 380;
  const GENERATOR_SPAWN_START_SCALE = 0.22;

  const flushTapQueue = async () => {
    tapFlushTimerRef.current = null;
    if (tapProcessingRef.current || tapQueueRef.current.length === 0) return;
    tapProcessingRef.current = true;

    const batch = tapQueueRef.current.splice(0);

    type GroupEntry = { generatorId: number; entries: { tempId: number; cellKey: string }[] };
    const groups: GroupEntry[] = [];
    for (const entry of batch) {
      const last = groups[groups.length - 1];
      if (last && last.generatorId === entry.generatorId) {
        last.entries.push({ tempId: entry.tempId, cellKey: entry.cellKey });
      } else {
        groups.push({ generatorId: entry.generatorId, entries: [{ tempId: entry.tempId, cellKey: entry.cellKey }] });
      }
    }

    for (const group of groups) {
      const { generatorId, entries } = group;
      const count = entries.length;
      const tempIds = entries.map((e) => e.tempId);
      const cellKeys = entries.map((e) => e.cellKey);

      try {
        const result = await gameApi.tapGeneratorBatch(generatorId, count);

        if (!result.success) {
          removeItems(tempIds);
          cellKeys.forEach((k) => reservedCellsRef.current.delete(k));

          const remainingEntries = tapQueueRef.current.splice(0);
          removeItems(remainingEntries.map((e) => e.tempId));
          remainingEntries.forEach((e) => reservedCellsRef.current.delete(e.cellKey));

          const restoreCount = count + remainingEntries.length;
          energyRef.current += restoreCount;
          setEnergy(energyRef.current);

          const sprite = generatorSpritesRef.current.get(generatorId);
          if (sprite) {
            platform.hapticFeedback('notification');
            shakeSprite(sprite);
          }

          if (result.error === 'Generator not ready') {
            showGeneratorNotReadyTooltip(generatorId, result.cooldown_until);
          }
          break;
        }

        if (result.generator) replaceGenerator(result.generator);

        removeItems(tempIds);
        cellKeys.forEach((k) => reservedCellsRef.current.delete(k));

        for (const realItem of result.items) {
          addItem(realItem);
        }

        const pending = tapQueueRef.current.length;
        energyRef.current = result.energy - pending;
        setEnergy(energyRef.current);
      } catch (err: unknown) {
        const is403 =
          err && typeof err === 'object' && 'response' in err &&
          (err as { response?: { status?: number } }).response?.status === 403;

        removeItems(tempIds);
        cellKeys.forEach((k) => reservedCellsRef.current.delete(k));

        const remainingEntries = tapQueueRef.current.splice(0);
        removeItems(remainingEntries.map((e) => e.tempId));
        remainingEntries.forEach((e) => reservedCellsRef.current.delete(e.cellKey));

        const restoreCount = count + remainingEntries.length;
        energyRef.current += restoreCount;
        setEnergy(energyRef.current);

        if (is403) {
          platform.hapticFeedback('notification');
          const sprite = generatorSpritesRef.current.get(generatorId);
          if (sprite) shakeSprite(sprite);
          window.dispatchEvent(new CustomEvent('no-energy'));
        } else {
          console.error('Generator tap batch failed', err);
        }
        break;
      }
    }

    tapProcessingRef.current = false;

    if (tapQueueRef.current.length > 0) {
      flushTapQueue();
    }
  };

  const scheduleTapFlush = () => {
    if (tapFlushTimerRef.current) return;
    tapFlushTimerRef.current = setTimeout(flushTapQueue, TAP_BATCH_DELAY_MS);
  };

  const enqueueGeneratorTap = (generatorId: number) => {
    if (energyRef.current <= 0) {
      platform.hapticFeedback('notification');
      const sprite = generatorSpritesRef.current.get(generatorId);
      if (sprite) shakeSprite(sprite);
      window.dispatchEvent(new CustomEvent('no-energy'));
      return;
    }

    const gen = generatorsRef.current.find((g) => g.id === generatorId);
    if (!gen) return;

    const slot = findLocalEmptySlot(gen.grid_x, gen.grid_y);
    if (!slot) return;

    energyRef.current -= 1;
    setEnergy(energyRef.current);
    platform.hapticFeedback('selection');

    const cellKey = `${slot.x},${slot.y}`;
    reservedCellsRef.current.add(cellKey);

    const tempId = -(Date.now() + Math.random());
    const themeSlug = gen.theme?.slug ?? '';
    const optimisticItem: GameItem = {
      id: tempId,
      theme_id: gen.theme_id,
      theme_slug: themeSlug,
      item_level: 1,
      grid_x: slot.x,
      grid_y: slot.y,
      image_url: null,
      item_name: null,
    };

    addItem(optimisticItem);
    pendingGeneratorSpawnRef.current.set(tempId, { fromGx: gen.grid_x, fromGy: gen.grid_y });

    tapQueueRef.current.push({ generatorId, tempId, cellKey });
    scheduleTapFlush();
  };

  /** Attach idle pulse to this Pixi app once; must run after generator layers are in refs. */
  const syncGeneratorPulseTicker = (app: Application) => {
    const existing = generatorPulseTickerBindingRef.current;
    if (existing?.app === app) return;

    if (existing) {
      try {
        existing.app.ticker.remove(existing.fn);
      } catch {
        /* noop */
      }
      generatorPulseTickerBindingRef.current = null;
    }

    generatorPulseAnchorRef.current = Date.now();

    const fn = (_ticker: Ticker) => {
      const layers = generatorPulseLayerRef.current;
      if (layers.size === 0) return;

      const cyclePos = (Date.now() - generatorPulseAnchorRef.current) % GENERATOR_IDLE_PULSE_PERIOD_MS;
      let s = 1;
      if (cyclePos < GENERATOR_IDLE_PULSE_MS) {
        const u = cyclePos / GENERATOR_IDLE_PULSE_MS;
        const breathe = Math.sin(Math.PI * u);
        s = 1 + (GENERATOR_IDLE_PULSE_MAX_SCALE - 1) * breathe;
      }

      layers.forEach((pulseInner, genId) => {
        if (!pulseInner.parent) return;
        const ptr = generatorPointerRef.current;
        if (ptr?.generatorId === genId && ptr.dragging) {
          pulseInner.scale.set(1);
          return;
        }
        pulseInner.scale.set(s);
      });
    };

    app.ticker.add(fn);
    generatorPulseTickerBindingRef.current = { app, fn };
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
    generatorPulseLayerRef.current.clear();
    generatorChargeTextRef.current.clear();

    const currentItems = itemsRef.current;
    const currentGenerators = generatorsRef.current;

    currentItems.forEach((item) => {
      const container = createItemSprite(item);
      const pos = gridToPixel(item.grid_x, item.grid_y);
      container.x = pos.x;
      container.y = pos.y;

      const spawn = pendingGeneratorSpawnRef.current.get(item.id);
      if (spawn) {
        pendingGeneratorSpawnRef.current.delete(item.id);
        const fromPos = gridToPixel(spawn.fromGx, spawn.fromGy);
        container.x = fromPos.x;
        container.y = fromPos.y;
        container.scale.set(GENERATOR_SPAWN_START_SCALE);
        container.zIndex = 2000;
        void animateTween(
          container,
          { x: pos.x, y: pos.y, scaleX: 1, scaleY: 1 },
          GENERATOR_SPAWN_MS,
          easeOutBack,
        ).then(() => {
          if (!container.destroyed) container.zIndex = 0;
        });
      }

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

    syncGeneratorPulseTicker(app);
  };

  const createItemSprite = (item: GameItem): Container => {
    const container = new Container();
    container.pivot.set(0, 0);
    container.eventMode = 'static';
    container.cursor = 'pointer';

    const iconSize = CELL_SIZE;

    const textureUrl =
      item.image_url
      ?? lookupItemDefinitionImageUrl(itemDefinitionsRef.current, item.theme_id, item.item_level);
    if (textureUrl) {
      const untilTex = new Graphics();
      untilTex.roundRect(-iconSize / 2 + 3, -iconSize / 2 + 3, iconSize - 6, iconSize - 6, 10);
      untilTex.fill({ color: 0xd8c8ae, alpha: 0.85 });
      container.addChild(untilTex);

      loadItemTexture(textureUrl, iconSize).then((texture) => {
        if (container.destroyed) return;
        untilTex.destroy();
        if (texture) {
          const sprite = new Sprite(texture);
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.roundPixels = true;
          container.addChildAt(sprite, 0);
        }
      });
    }

    if (item.item_level >= 8) {
      const frame = new Graphics();
      frame.roundRect(-CELL_SIZE / 2, -CELL_SIZE / 2, CELL_SIZE, CELL_SIZE, 10);
      frame.stroke({ color: 0xFFD700, width: 2 });
      container.addChild(frame);
    }

    // ── Drag & Drop ──

    container.on('pointerdown', (e: FederatedPointerEvent) => {
      dragRef.current = {
        itemId: item.id,
        startX: container.x,
        startY: container.y,
        sprite: container,
      };
      (container as any).__pointerStartX = e.global.x;
      (container as any).__pointerStartY = e.global.y;
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

    container.on('pointerup', (e: FederatedPointerEvent) => {
      if (!dragRef.current || dragRef.current.itemId !== item.id || !container.parent) return;

      const pos = e.getLocalPosition(container.parent);
      const { gx, gy } = pixelToGrid(pos.x, pos.y);
      const drag = dragRef.current;
      dragRef.current = null;

      const psx = (container as any).__pointerStartX ?? 0;
      const psy = (container as any).__pointerStartY ?? 0;
      const tapDist = Math.hypot(e.global.x - psx, e.global.y - psy);
      if (tapDist < GENERATOR_DRAG_THRESHOLD_PX) {
        const cur = itemsRef.current.find((i) => i.id === item.id);
        if (cur) {
          window.dispatchEvent(new CustomEvent('item-selected', {
            detail: { name: cur.item_name ?? cur.theme_slug, level: cur.item_level },
          }));
        }
      }

      container.alpha = 1;
      container.scale.set(1);
      container.zIndex = 0;

      if (gx < 0 || gx >= GRID_WIDTH || gy < 0 || gy >= GRID_HEIGHT) {
        container.x = drag.startX;
        container.y = drag.startY;
        return;
      }

      const currentItem = itemsRef.current.find((i) => i.id === item.id);
      if (!currentItem) {
        return;
      }

      const targetItem = itemsRef.current.find(
        (i) => i.grid_x === gx && i.grid_y === gy && i.id !== currentItem.id
      );

      if (targetItem && targetItem.theme_slug === currentItem.theme_slug && targetItem.item_level === currentItem.item_level) {
        const targetSprite = itemSpritesRef.current.get(targetItem.id);
        const targetPos = gridToPixel(gx, gy);

        if (targetSprite) {
          playMergeAnimation(container, targetSprite, targetPos.x, targetPos.y);
        }

        const tempId = -(Date.now() + Math.random());
        const newLevel = currentItem.item_level + 1;
        const optimisticItem: GameItem = {
          id: tempId,
          theme_id: currentItem.theme_id,
          theme_slug: currentItem.theme_slug,
          item_level: newLevel,
          grid_x: gx,
          grid_y: gy,
          image_url: currentItem.image_url,
          item_name: currentItem.item_name,
        };

        const savedItems = [{ ...currentItem }, { ...targetItem }];
        removeItems([currentItem.id, targetItem.id]);
        addItem(optimisticItem);

        gameApi.merge(currentItem.id, targetItem.id).then((result) => {
          removeItems([tempId, currentItem.id, targetItem.id]);
          addItem(result.new_item);
          energyRef.current = result.energy;
          setEnergy(result.energy);

          if (result.character_line) {
            window.dispatchEvent(new CustomEvent('character-line', { detail: result.character_line }));
          }
        }).catch(() => {
          removeItems([tempId]);
          savedItems.forEach((si) => addItem(si));
        });
      } else if (targetItem || generatorsRef.current.some((g) => g.grid_x === gx && g.grid_y === gy)) {
        container.x = drag.startX;
        container.y = drag.startY;
      } else {
        updateItemPosition(currentItem.id, gx, gy);
        pendingMovesRef.current.push({ type: 'item', id: currentItem.id, grid_x: gx, grid_y: gy });
        scheduleMoveFlush();
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

    const slug = gen.theme?.slug;
    const imageUrl = gen.image_url ?? (slug ? getGeneratorImageUrl(slug) : null);

    const pulseInner = new Container();
    container.addChild(pulseInner);
    generatorPulseLayerRef.current.set(gen.id, pulseInner);

    const chargeText = new Text({
      text: generatorUnderIconLabel(gen),
      style: new TextStyle({ fontSize: 10, fill: 0x666666 }),
    });
    chargeText.anchor.set(0.5);
    chargeText.y = 19;
    pulseInner.addChild(chargeText);
    generatorChargeTextRef.current.set(gen.id, chargeText);

    const iconSize = CELL_SIZE;

    if (imageUrl) {
      loadItemTexture(imageUrl, iconSize).then((texture) => {
        if (pulseInner.destroyed) return;
        if (texture) {
          const sprite = new Sprite(texture);
          sprite.width = iconSize;
          sprite.height = iconSize;
          sprite.anchor.set(0.5);
          sprite.roundPixels = true;
          pulseInner.addChildAt(sprite, 0);
        }
      });
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

        const cellOccupied =
          itemsRef.current.some((i) => i.grid_x === gx && i.grid_y === gy) ||
          generatorsRef.current.some((g) => g.id !== gen.id && g.grid_x === gx && g.grid_y === gy);

        if (cellOccupied) {
          container.x = ptr.startSpriteX;
          container.y = ptr.startSpriteY;
          return;
        }

        updateGeneratorPosition(gen.id, gx, gy);
        pendingMovesRef.current.push({ type: 'generator', id: gen.id, grid_x: gx, grid_y: gy });
        scheduleMoveFlush();
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

function lookupItemDefinitionImageUrl(
  map: ItemDefinitionMap | null,
  themeId: number,
  level: number,
): string | null {
  const list = map?.[String(themeId)];
  if (!list) return null;
  return list.find((d) => d.level === level)?.image_url ?? null;
}

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
