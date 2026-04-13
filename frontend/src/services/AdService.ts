import { gameApi } from './GameApi';

class AdManager {
  private lastInterstitialTime = 0;
  private readonly INTERSTITIAL_COOLDOWN = 180_000; // 3 minutes

  async init(): Promise<void> {
    try {
      if ((window as any).Monetag) {
        await (window as any).Monetag.init();
      }
    } catch (err) {
      console.warn('Monetag SDK not available:', err);
    }
  }

  async showRewarded(placement: string): Promise<boolean> {
    try {
      if ((window as any).Monetag?.showRewardedInterstitial) {
        const result = await (window as any).Monetag.showRewardedInterstitial();
        if (result?.completed) {
          await gameApi.reportAd('rewarded', placement);
          return true;
        }
        return false;
      }

      // Fallback: grant reward if SDK not loaded (graceful degradation per PRD)
      await gameApi.reportAd('rewarded', placement);
      return true;
    } catch (err) {
      console.error('Rewarded ad failed:', err);
      await gameApi.reportAd('rewarded', placement);
      return true;
    }
  }

  async showInterstitial(placement: string): Promise<void> {
    const now = Date.now();
    if (now - this.lastInterstitialTime < this.INTERSTITIAL_COOLDOWN) {
      return;
    }

    try {
      if ((window as any).Monetag?.showInterstitial) {
        await (window as any).Monetag.showInterstitial();
      }
      this.lastInterstitialTime = now;
      await gameApi.reportAd('interstitial', placement);
    } catch (err) {
      console.warn('Interstitial failed:', err);
    }
  }

  async showRewardedPopup(placement: string): Promise<boolean> {
    try {
      if ((window as any).Monetag?.showRewardedPopup) {
        const result = await (window as any).Monetag.showRewardedPopup();
        if (result?.completed) {
          await gameApi.reportAd('popup', placement);
          return true;
        }
        return false;
      }

      await gameApi.reportAd('popup', placement);
      return true;
    } catch (err) {
      console.error('Popup ad failed:', err);
      await gameApi.reportAd('popup', placement);
      return true;
    }
  }
}

export const adManager = new AdManager();
