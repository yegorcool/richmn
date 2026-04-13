import { createContext, useCallback, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import type { GameItem, Generator, Order, Character, User } from '@/types/game';
import { gameApi } from '@/services/GameApi';
import { apiClient } from '@/services/ApiClient';
import { getValidTelegramWidgetQueryParams } from '@/utils/telegramWidgetAuth';
import { usePlatform } from './PlatformContext';

const BACKGROUND_SYNC_INTERVAL_MS = 30_000;

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
}

const GameContext = createContext<GameContextValue | null>(null);

export function GameProvider({ children }: { children: ReactNode }) {
  const platform = usePlatform();
  const [user, setUser] = useState<User | null>(null);
  const [items, setItems] = useState<GameItem[]>([]);
  const [generators, setGenerators] = useState<Generator[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [characters, setCharacters] = useState<Character[]>([]);
  const [energy, setEnergy] = useState(50);
  const [energyMax, setEnergyMax] = useState(50);
  const [loading, setLoading] = useState(true);
  const initializedRef = useRef(false);

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
        setItems(stateRes.value.items);
        setGenerators(stateRes.value.generators);
        setEnergy(stateRes.value.energy);
        setEnergyMax(stateRes.value.energy_max);
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

  const refreshState = useCallback(async () => {
    const state = await gameApi.getGameState();
    setItems(state.items);
    setGenerators(state.generators);
    setEnergy(state.energy);
    setEnergyMax(state.energy_max);
  }, []);

  const refreshOrders = useCallback(async () => {
    const res = await gameApi.getOrders();
    setOrders(res.orders);
  }, []);

  // --- Granular optimistic updaters ---

  const addItem = useCallback((item: GameItem) => {
    setItems((prev) => [...prev, item]);
  }, []);

  const removeItems = useCallback((ids: number[]) => {
    const idSet = new Set(ids);
    setItems((prev) => prev.filter((i) => !idSet.has(i.id)));
  }, []);

  const updateItemPosition = useCallback((id: number, gridX: number, gridY: number) => {
    setItems((prev) => prev.map((i) => (i.id === id ? { ...i, grid_x: gridX, grid_y: gridY } : i)));
  }, []);

  const replaceGenerator = useCallback((generator: Generator) => {
    setGenerators((prev) => prev.map((g) => (g.id === generator.id ? generator : g)));
  }, []);

  const updateGeneratorPosition = useCallback((id: number, gridX: number, gridY: number) => {
    setGenerators((prev) => prev.map((g) => (g.id === id ? { ...g, grid_x: gridX, grid_y: gridY } : g)));
  }, []);

  // --- Background sync to correct any drift ---
  useEffect(() => {
    const id = setInterval(() => {
      if (!initializedRef.current) return;
      refreshState().catch(() => {});
    }, BACKGROUND_SYNC_INTERVAL_MS);
    return () => clearInterval(id);
  }, [refreshState]);

  return (
    <GameContext.Provider value={{
      user, items, generators, orders, characters,
      energy, energyMax, loading,
      refreshState, refreshOrders, setItems, setEnergy, setUser,
      addItem, removeItems, updateItemPosition, replaceGenerator, updateGeneratorPosition,
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
