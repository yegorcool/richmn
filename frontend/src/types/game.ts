export interface User {
  id: number;
  platform_id: string;
  source: string;
  username: string | null;
  first_name: string;
  last_name: string | null;
  avatar_url: string | null;
  is_premium: boolean;
  language_code: string;
  level: number;
  experience: number;
  energy: number;
  energy_updated_at: string;
  coins: number;
  referral_code: string;
}

export interface GameItem {
  id: number;
  theme_id: number;
  theme_slug: string;
  item_level: number;
  grid_x: number;
  grid_y: number;
  image_url?: string | null;
  item_name?: string | null;
}

export interface Generator {
  id: number;
  theme_id: number;
  level: number;
  charges_left: number;
  max_charges: number;
  cooldown_until: string | null;
  grid_x: number;
  grid_y: number;
  /** Theme generator icon from API; fallback to Iconify by slug in GameField. */
  image_url?: string | null;
  /** Present when API loads `theme` relation (e.g. game state). */
  theme?: { id: number; slug: string; name: string };
}

export interface ItemDefinitionMap {
  [themeId: string]: ItemDefinitionEntry[];
}

export interface ItemDefinitionEntry {
  level: number;
  name: string;
  slug: string;
  image_url: string | null;
}

export interface GameState {
  items: GameItem[];
  generators: Generator[];
  energy: number;
  energy_max: number;
  energy_recovery_at: string | null;
  item_definitions?: ItemDefinitionMap;
}

export type TapGeneratorResult =
  | {
      success: true;
      item: GameItem;
      item_definition?: ItemDefinitionEntry | null;
      generator: Generator;
      energy: number;
      energy_max: number;
    }
  | {
      success: false;
      error: string;
      cooldown_until?: string | null;
    };

export type TapGeneratorBatchResult =
  | {
      success: true;
      items: GameItem[];
      generator: Generator;
      energy: number;
      energy_max: number;
      tapped: number;
    }
  | {
      success: false;
      error: string;
      cooldown_until?: string | null;
    };

export interface Order {
  id: number;
  character_id: number;
  character: Character;
  required_items: OrderRequirement[];
  reward: OrderReward;
  status: 'active' | 'completed' | 'expired';
  created_at: string;
}

export interface OrderRequirement {
  theme_slug: string;
  item_level: number;
  fulfilled: boolean;
}

export interface OrderReward {
  coins: number;
  experience: number;
  decor_resource?: number;
  chest_type?: string;
}

export interface Character {
  id: number;
  name: string;
  slug: string;
  theme_id: number;
  personality: string;
  avatar_path: string;
  unlock_level: number;
  mood?: CharacterMood;
  relationship?: RelationshipLevel;
}

export type CharacterMood = 'happy' | 'neutral' | 'impatient' | 'delighted';
export type RelationshipLevel = 'new' | 'familiar' | 'loyal';

export interface CharacterLine {
  id: number;
  character_id: number;
  trigger: string;
  text: string;
}

export interface MergeResult {
  new_item: GameItem;
  energy: number;
  character_line: CharacterLine | null;
  experience_gained: number;
}

export interface Chest {
  id: number;
  type: 'small' | 'medium' | 'large' | 'super';
  source: string;
  unlock_at: string | null;
  opened_at: string | null;
  can_open: boolean;
}

export interface Theme {
  id: number;
  name: string;
  slug: string;
  unlock_level: number;
  chain_config: ChainLevel[];
}

export interface ChainLevel {
  level: number;
  name: string;
  sprite_key: string;
}

export interface DecorLocation {
  id: number;
  name: string;
  slug: string;
  unlock_level: number;
  max_items: number;
  placed_items: DecorPlacedItem[];
}

export interface DecorPlacedItem {
  id: number;
  item_key: string;
  style_variant: number;
}

export interface StreakInfo {
  current_streak: number;
  longest_streak: number;
  reward_claimed_today: boolean;
  rewards: StreakReward[];
}

export interface StreakReward {
  day: number;
  type: string;
  amount: number;
  claimed: boolean;
}

export interface DailyChallenge {
  id: number;
  description: string;
  difficulty: 'easy' | 'medium' | 'hard';
  target: number;
  progress: number;
  completed: boolean;
  claimed: boolean;
  reward: OrderReward;
}

export interface EventInfo {
  id: number;
  type: 'weekly' | 'seasonal' | 'daily_challenge';
  name: string;
  starts_at: string;
  ends_at: string;
  score: number;
  milestones: EventMilestone[];
}

export interface EventMilestone {
  threshold: number;
  reward: OrderReward;
  claimed: boolean;
}
