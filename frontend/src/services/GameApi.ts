import { apiClient } from './ApiClient';
import type {
  User, GameState, Order, Character, CharacterLine,
  MergeResult, Chest, DecorLocation, EventInfo, StreakInfo,
  DailyChallenge, Theme, TapGeneratorResult, TapGeneratorBatchResult,
} from '@/types/game';

export const gameApi = {
  getUser: () => apiClient.get<{ user: User }>('/user/me'),

  getGameState: () => apiClient.get<GameState>('/game/state'),

  merge: (itemId1: number, itemId2: number) =>
    apiClient.post<MergeResult>('/game/merge', { item_id_1: itemId1, item_id_2: itemId2 }),

  tapGenerator: (generatorId: number) =>
    apiClient.post<TapGeneratorResult>('/game/generator/tap', { generator_id: generatorId }),

  tapGeneratorBatch: (generatorId: number, count: number) =>
    apiClient.post<TapGeneratorBatchResult>('/game/generator/tap-batch', { generator_id: generatorId, count }),

  moveItem: (itemId: number, gridX: number, gridY: number) =>
    apiClient.post<{ success: boolean }>('/game/move-item', { item_id: itemId, grid_x: gridX, grid_y: gridY }),

  moveGenerator: (generatorId: number, gridX: number, gridY: number) =>
    apiClient.post<{ success: boolean }>('/game/move-generator', {
      generator_id: generatorId,
      grid_x: gridX,
      grid_y: gridY,
    }),

  moveBatch: (moves: { type: 'item' | 'generator'; id: number; grid_x: number; grid_y: number }[]) =>
    apiClient.post<{ success: boolean }>('/game/move-batch', { moves }),

  getOrders: () => apiClient.get<{ orders: Order[] }>('/orders'),

  submitOrder: (orderId: number, itemId: number) =>
    apiClient.post<{ success: boolean; partial: boolean; reward?: any; character_line?: CharacterLine }>(`/orders/${orderId}/submit`, { item_id: itemId }),

  getCharacters: () => apiClient.get<{ characters: Character[] }>('/characters'),

  getCharacterLine: (characterId: number, trigger: string, context?: Record<string, unknown>) =>
    apiClient.get<{ line: CharacterLine | null; mood: string; relationship: string }>(
      `/characters/${characterId}/line?trigger=${trigger}${context ? `&context=${JSON.stringify(context)}` : ''}`
    ),

  getEnergy: () => apiClient.get<{ energy: number; max: number; recovery_seconds: number }>('/energy'),

  refillEnergy: (source: 'ad' | 'referral') =>
    apiClient.post<{ energy: number; max: number }>('/energy/refill', { source }),

  getChests: () => apiClient.get<{ chests: Chest[] }>('/chests'),
  openChest: (chestId: number, adSkip = false) =>
    apiClient.post<{ success: boolean; loot: any }>(`/chests/${chestId}/open`, { ad_skip: adSkip }),

  getDecorLocations: () => apiClient.get<{ locations: DecorLocation[] }>('/decor/locations'),
  placeDecor: (locationId: number, itemKey: string, styleVariant: number) =>
    apiClient.post('/decor/place', { location_id: locationId, item_key: itemKey, style_variant: styleVariant }),
  removeDecor: (locationId: number, itemKey: string) =>
    apiClient.delete('/decor/remove', { data: { location_id: locationId, item_key: itemKey } }),

  getActiveEvents: () => apiClient.get<{ events: EventInfo[] }>('/events/active'),
  getEventProgress: (eventId: number) => apiClient.get(`/events/${eventId}/progress`),
  getEventLeaderboard: (eventId: number) => apiClient.get(`/events/${eventId}/leaderboard`),

  getCollection: () => apiClient.get<{ collection: Theme[] }>('/collection'),

  getReferral: () => apiClient.get<{ referral_code: string; invited_count: number; link: string }>('/referral'),
  claimReferral: (code: string) => apiClient.post('/referral/claim', { referral_code: code }),

  reportAd: (format: string, placement: string) =>
    apiClient.post('/ads/callback', { format, placement }),

  getStreak: () => apiClient.get<StreakInfo>('/streak'),
  claimStreak: () => apiClient.post('/streak/claim'),

  getDailyChallenge: () => apiClient.get<{ challenges: DailyChallenge[]; completed: number[] }>('/daily-challenge'),
  claimDailyChallenge: (index: number) => apiClient.post(`/daily-challenge/${index}/claim`),

  sendGift: (receiverId: number) => apiClient.post('/gifts/send', { receiver_id: receiverId }),
  claimGifts: () => apiClient.post<{ claimed: number; energy_received: number }>('/gifts/claim'),

  trackEvents: (events: { name: string; properties?: Record<string, unknown> }[]) =>
    apiClient.post('/analytics/event', { events }),
};
