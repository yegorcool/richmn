import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import type { GameItem, Generator, Order, Character, User } from '@/types/game';
import { gameApi } from '@/services/GameApi';
import { apiClient } from '@/services/ApiClient';
import { getValidTelegramWidgetQueryParams } from '@/utils/telegramWidgetAuth';
import { usePlatform } from './PlatformContext';

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
      // Ensure the user row exists (and starter generators are seeded in middleware) before loading field state.
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

  return (
    <GameContext.Provider value={{
      user, items, generators, orders, characters,
      energy, energyMax, loading,
      refreshState, refreshOrders, setItems, setEnergy, setUser,
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
