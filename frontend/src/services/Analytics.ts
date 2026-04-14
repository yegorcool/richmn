import { gameApi } from './GameApi';

class Analytics {
  private buffer: { name: string; properties?: Record<string, unknown>; timestamp?: number }[] = [];
  init() {
    setInterval(() => this.flush(), 30_000);
    window.addEventListener('beforeunload', () => this.flush());
  }

  track(name: string, properties?: Record<string, unknown>) {
    this.buffer.push({
      name,
      properties,
      timestamp: Math.floor(Date.now() / 1000),
    });

    if (this.buffer.length >= 20) {
      this.flush();
    }
  }

  async flush() {
    if (this.buffer.length === 0) return;
    const events = [...this.buffer];
    this.buffer = [];

    try {
      await gameApi.trackEvents(events);
    } catch {
      this.buffer.unshift(...events);
    }
  }

  sessionStart() {
    this.track('session_start');
  }

  sessionEnd() {
    this.track('session_end');
    this.flush();
  }

  merge(themeSlug: string, resultLevel: number) {
    this.track('merge', { theme: themeSlug, result_level: resultLevel });
  }

  orderComplete(themeSlug: string, level: number) {
    this.track('order_complete', { theme: themeSlug, level });
  }

  energyDepleted() {
    this.track('energy_depleted');
  }

  energyRefill(source: string) {
    this.track('energy_refill', { source });
  }

  adShown(format: string, placement: string) {
    this.track('ad_shown', { format, placement });
  }

  adCompleted(format: string, placement: string) {
    this.track('ad_completed', { format, placement });
  }

  characterLineShown(characterSlug: string, trigger: string, lineId: number) {
    this.track('character_line_shown', { character: characterSlug, trigger, line_id: lineId });
  }

  levelUp(newLevel: number) {
    this.track('level_up', { new_level: newLevel });
  }

  decorPlaced(location: string, item: string) {
    this.track('decor_placed', { location, item });
  }
}

export const analytics = new Analytics();
