import { createContext, useCallback, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import type { GameItem, Generator, Order, Character, User, ItemDefinitionMap } from '@/types/game';
import { gameApi } from '@/services/GameApi';
import { apiClient } from '@/services/ApiClient';
import { getValidTelegramWidgetQueryParams } from '@/utils/telegramWidgetAuth';
import { usePlatform } from './PlatformContext';

const BACKGROUND_SYNC_INTERVAL_MS = 30_000;

type PendingMove = { type: 'item' | 'generator'; id: number; grid_x: number; grid_y: number };

interface GameContextValue {
  user: User | null;
  items: GameItem[];
  generators: Generator[];
  orders: Order[];
  characters: Character[];
  energy: number;
  energyMax: number;
  loading: boolean;
  refreshState: () => Promise<void>;
  refreshOrders: () => Promise<void>;
  setItems: (items: GameItem[]) => void;
  setEnergy: (energy: number) => void;
  setUser: (user: User) => void;
  addItem: (item: GameItem) => void;
  removeItems: (ids: number[]) => void;
  updateItemPosition: (id: number, gridX: number, gridY: number) => void;
  replaceGenerator: (generator: Generator) => void;
  updateGeneratorPosition: (id: number, gridX: number, gridY: number) => void;
  pendingMoves: React.MutableRefObject<PendingMove[]>;
  flushPendingMoves: () => void;
  itemsRef: React.MutableRefObject<GameItem[]>;
  generatorsRef: React.MutableRefObject<Generator[]>;
  /** Populated from `/game/state` — use for optimistic items before `image_url` is set. */
  itemDefinitionsRef: React.MutableRefObject<ItemDefinitionMap | null>;
}

const GameContext = createContext<GameContextValue | null>(null);

export function GameProvider({ children }: { children: ReactNode }) {
  const platform = usePlatform();
  const [user, setUser] = useState<User | null>(null);
  const [items, setItemsRaw] = useState<GameItem[]>([]);
  const [generators, setGeneratorsRaw] = useState<Generator[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [characters, setCharacters] = useState<Character[]>([]);
  const [energy, setEnergy] = useState(50);
  const [energyMax, setEnergyMax] = useState(50);
  const [loading, setLoading] = useState(true);
  const initializedRef = useRef(false);
  const pendingMovesRef = useRef<PendingMove[]>([]);
  const itemsRef = useRef<GameItem[]>([]);
  const generatorsRef = useRef<Generator[]>([]);
  const itemDefinitionsRef = useRef<ItemDefinitionMap | null>(null);

  const setItems = useCallback((newItems: GameItem[]) => {
    itemsRef.current = newItems;
    setItemsRaw(newItems);
  }, []);

  const setGenerators = useCallback((newGens: Generator[]) => {
    generatorsRef.current = newGens;
    setGeneratorsRaw(newGens);
  }, []);

  useEffect(() => {
    apiClient.init(platform.platform, platform.initData);
    const widget =
      platform.platform === 'telegram' && !platform.initData
        ? getValidTelegramWidgetQueryParams()
        : null;
    apiClient.setTelegramWidgetParams(widget);
    initializeGame();
  }, [platform]);

  const initializeGame = async () => {
    try {
      const userRes = await Promise.resolve(gameApi.getUser())
        .then((value) => ({ status: 'fulfilled' as const, value }))
        .catch((reason) => ({ status: 'rejected' as const, reason }));

      if (userRes.status === 'fulfilled') {
        setUser(userRes.value.user);
      } else {
        console.error('Failed to load user:', userRes.reason);
      }

      const [stateRes, ordersRes, charsRes] = await Promise.allSettled([
        gameApi.getGameState(),
        gameApi.getOrders(),
        gameApi.getCharacters(),
      ]);

      if (stateRes.status === 'fulfilled') {
        const st = stateRes.value;
        setItems(st.items);
        setGenerators(st.generators);
        setEnergy(st.energy);
        setEnergyMax(st.energy_max);
        if (st.item_definitions) {
          itemDefinitionsRef.current = st.item_definitions;
        }
      } else {
        console.error('Failed to load game state:', stateRes.reason);
      }

      if (ordersRes.status === 'fulfilled') {
        setOrders(ordersRes.value.orders);
      } else {
        console.error('Failed to load orders:', ordersRes.reason);
      }

      if (charsRes.status === 'fulfilled') {
        setCharacters(charsRes.value.characters);
      } else {
        console.error('Failed to load characters:', charsRes.reason);
      }
    } catch (err) {
      console.error('Failed to initialize game:', err);
    } finally {
      setLoading(false);
      initializedRef.current = true;
    }
  };

  const flushPendingMoves = useCallback(() => {
    const batch = pendingMovesRef.current.splice(0);
    if (batch.length === 0) return;
    gameApi.moveBatch(batch).catch(() => {});
  }, []);

  const refreshState = useCallback(async () => {
    flushPendingMoves();
    const state = await gameApi.getGameState();
    setItems(state.items);
    setGenerators(state.generators);
    setEnergy(state.energy);
    setEnergyMax(state.energy_max);
    if (state.item_definitions) {
      itemDefinitionsRef.current = state.item_definitions;
    }
  }, [flushPendingMoves]);

  const refreshOrders = useCallback(async () => {
    const res = await gameApi.getOrders();
    setOrders(res.orders);
  }, []);

  // --- Granular optimistic updaters (eagerly sync refs for immediate reads) ---

  const addItem = useCallback((item: GameItem) => {
    itemsRef.current = [...itemsRef.current.filter((i) => i.id !== item.id), item];
    setItemsRaw((prev) => [...prev.filter((i) => i.id !== item.id), item]);
  }, []);

  const removeItems = useCallback((ids: number[]) => {
    const idSet = new Set(ids);
    itemsRef.current = itemsRef.current.filter((i) => !idSet.has(i.id));
    setItemsRaw((prev) => prev.filter((i) => !idSet.has(i.id)));
  }, []);

  const updateItemPosition = useCallback((id: number, gridX: number, gridY: number) => {
    itemsRef.current = itemsRef.current.map((i) => (i.id === id ? { ...i, grid_x: gridX, grid_y: gridY } : i));
    setItemsRaw((prev) => prev.map((i) => (i.id === id ? { ...i, grid_x: gridX, grid_y: gridY } : i)));
  }, []);

  const replaceGenerator = useCallback((generator: Generator) => {
    const update = (list: Generator[]) =>
      list.map((g) => {
        if (g.id !== generator.id) return g;
        const theme = generator.theme ?? g.theme;
        return theme ? { ...generator, theme } : generator;
      });
    generatorsRef.current = update(generatorsRef.current);
    setGeneratorsRaw(update);
  }, []);

  const updateGeneratorPosition = useCallback((id: number, gridX: number, gridY: number) => {
    const update = (list: Generator[]) => list.map((g) => (g.id === id ? { ...g, grid_x: gridX, grid_y: gridY } : g));
    generatorsRef.current = update(generatorsRef.current);
    setGeneratorsRaw(update);
  }, []);

  // --- Background sync to correct any drift ---
  useEffect(() => {
    const id = setInterval(() => {
      if (!initializedRef.current) return;
      refreshState().catch(() => {});
    }, BACKGROUND_SYNC_INTERVAL_MS);
    return () => clearInterval(id);
  }, [refreshState]);

  // --- Flush pending moves on visibility change / beforeunload ---
  useEffect(() => {
    const onVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        flushPendingMoves();
      }
    };
    const onBeforeUnload = () => {
      flushPendingMoves();
    };

    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('beforeunload', onBeforeUnload);
    return () => {
      document.removeEventListener('visibilitychange', onVisibilityChange);
      window.removeEventListener('beforeunload', onBeforeUnload);
    };
  }, [flushPendingMoves]);

  return (
    <GameContext.Provider value={{
      user, items, generators, orders, characters,
      energy, energyMax, loading,
      refreshState, refreshOrders, setItems, setEnergy, setUser,
      addItem, removeItems, updateItemPosition, replaceGenerator, updateGeneratorPosition,
      pendingMoves: pendingMovesRef, flushPendingMoves,
      itemsRef, generatorsRef, itemDefinitionsRef,
    }}>
      {children}
    </GameContext.Provider>
  );
}

export function useGame(): GameContextValue {
  const ctx = useContext(GameContext);
  if (!ctx) throw new Error('useGame must be used inside GameProvider');
  return ctx;
}
