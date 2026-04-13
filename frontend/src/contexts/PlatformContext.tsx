import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import type { Platform, PlatformAPI, ThemeParams } from '@/types/platform';

const PlatformContext = createContext<PlatformAPI | null>(null);

function detectPlatform(): Platform {
  const hostname = window.location.hostname;
  if (hostname.startsWith('max.')) {
    return 'max';
  }
  return 'telegram';
}

function getTelegramThemeParams(): ThemeParams {
  const tp = (window as unknown as { Telegram?: { WebApp?: { themeParams?: Record<string, string> } } }).Telegram?.WebApp
    ?.themeParams ?? {};
  return {
    bgColor: tp.bg_color,
    textColor: tp.text_color,
    hintColor: tp.hint_color,
    linkColor: tp.link_color,
    buttonColor: tp.button_color,
    buttonTextColor: tp.button_text_color,
    secondaryBgColor: tp.secondary_bg_color,
  };
}

function buildPlatformApi(): PlatformAPI {
  const platform = detectPlatform();

  if (platform === 'max') {
    const max = (window as unknown as { MaxWebApp?: { initData?: string } }).MaxWebApp;
    return {
      platform: 'max',
      initData: max?.initData ?? '',
      hapticFeedback: () => {},
      expand: () => {},
      close: () => {},
      themeParams: {},
    };
  }

  const tw = (window as unknown as { Telegram?: { WebApp?: TelegramWebAppLike } }).Telegram?.WebApp;

  return {
    platform: 'telegram',
    initData: tw?.initData ?? '',
    hapticFeedback: (type) => {
      const hf = tw?.HapticFeedback;
      if (!hf) {
        return;
      }
      switch (type) {
        case 'impact':
          hf.impactOccurred('medium');
          break;
        case 'notification':
          hf.notificationOccurred('success');
          break;
        case 'selection':
          hf.selectionChanged();
          break;
      }
    },
    expand: () => {
      tw?.expand();
    },
    close: () => {
      tw?.close();
    },
    themeParams: getTelegramThemeParams(),
  };
}

/** Minimal shape; full SDK types come from telegram-web-app.js at runtime */
type TelegramWebAppLike = {
  initData?: string;
  ready: () => void;
  expand: () => void;
  close: () => void;
  HapticFeedback?: {
    impactOccurred: (s: string) => void;
    notificationOccurred: (s: string) => void;
    selectionChanged: () => void;
  };
};

function telegramWebApp(): TelegramWebAppLike | undefined {
  return (window as unknown as { Telegram?: { WebApp?: TelegramWebAppLike } }).Telegram?.WebApp;
}

function shouldRetryTelegramWebApp(): boolean {
  const href = window.location.href;
  if (/[?#&]tgWebAppData=/.test(href)) {
    return true;
  }
  const ua = navigator.userAgent || '';
  if (/Telegram/i.test(ua)) {
    return true;
  }
  return false;
}

export function PlatformProvider({ children }: { children: ReactNode }) {
  const [ready, setReady] = useState(false);
  const [api, setApi] = useState<PlatformAPI>(() => buildPlatformApi());

  useEffect(() => {
    if (detectPlatform() === 'max') {
      setApi(buildPlatformApi());
      setReady(true);
      return;
    }

    let cancelled = false;
    let intervalId: ReturnType<typeof setInterval> | undefined;

    const commit = () => {
      if (cancelled) {
        return;
      }
      setApi(buildPlatformApi());
      setReady(true);
    };

    const tryTelegram = (): boolean => {
      const tw = telegramWebApp();
      if (!tw) {
        return false;
      }
      tw.ready();
      tw.expand();
      commit();
      return true;
    };

    if (tryTelegram()) {
      return () => {
        cancelled = true;
      };
    }

    if (!shouldRetryTelegramWebApp()) {
      commit();
      return () => {
        cancelled = true;
      };
    }

    let attempts = 0;
    const maxAttempts = 50;
    intervalId = setInterval(() => {
      if (tryTelegram()) {
        if (intervalId) {
          clearInterval(intervalId);
        }
        return;
      }
      attempts += 1;
      if (attempts >= maxAttempts) {
        if (intervalId) {
          clearInterval(intervalId);
        }
        commit();
      }
    }, 100);

    return () => {
      cancelled = true;
      if (intervalId) {
        clearInterval(intervalId);
      }
    };
  }, []);

  if (!ready) {
    return null;
  }

  return <PlatformContext.Provider value={api}>{children}</PlatformContext.Provider>;
}

export function usePlatform(): PlatformAPI {
  const ctx = useContext(PlatformContext);
  if (!ctx) {
    throw new Error('usePlatform must be used inside PlatformProvider');
  }
  return ctx;
}
