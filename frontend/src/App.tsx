import { useEffect, useMemo, useState } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { PlatformProvider, usePlatform } from '@/contexts/PlatformContext';
import { GameProvider, useGame } from '@/contexts/GameContext';
import { Navigation } from '@/components/Navigation/Navigation';
import { GamePage } from '@/pages/GamePage';
import { DecorPage } from '@/pages/DecorPage';
import { EventsPage } from '@/pages/EventsPage';
import { MorePage } from '@/pages/MorePage';
import { GuestLandingPage } from '@/pages/GuestLandingPage';
import { AuthTelegramPage } from '@/pages/AuthTelegramPage';
import { getValidTelegramWidgetQueryParams } from '@/utils/telegramWidgetAuth';
import './App.css';

function AppContent() {
  const { loading } = useGame();

  if (loading) {
    return (
      <div className="app-loading">
        <div className="app-loading__spinner" />
        <p>Загрузка игры...</p>
      </div>
    );
  }

  return (
    <div className="app">
      <div className="app__content">
        <Routes>
          <Route path="/" element={<GamePage />} />
          <Route path="/decor" element={<DecorPage />} />
          <Route path="/events" element={<EventsPage />} />
          <Route path="/more" element={<MorePage />} />
        </Routes>
      </div>
      <Navigation />
    </div>
  );
}

function AuthenticatedRoutes() {
  return (
    <GameProvider>
      <AppContent />
    </GameProvider>
  );
}

function AppGate() {
  const platform = usePlatform();
  const [sessionRefresh, setSessionRefresh] = useState(0);

  useEffect(() => {
    const onAuthRequired = () => setSessionRefresh((n) => n + 1);
    window.addEventListener('richmn:auth-required', onAuthRequired);

    return () => window.removeEventListener('richmn:auth-required', onAuthRequired);
  }, []);

  const allowGame = useMemo(() => {
    if (platform.initData) {
      return true;
    }
    if (platform.platform === 'telegram' && getValidTelegramWidgetQueryParams()) {
      return true;
    }
    return false;
  }, [platform.initData, platform.platform, sessionRefresh]);

  return (
    <Routes>
      <Route path="/login/telegram" element={<AuthTelegramPage />} />
      <Route
        path="*"
        element={
          allowGame ? (
            <AuthenticatedRoutes />
          ) : (
            <GuestLandingPage onTelegramWidgetAuth={() => setSessionRefresh((n) => n + 1)} />
          )
        }
      />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <PlatformProvider>
        <AppGate />
      </PlatformProvider>
    </BrowserRouter>
  );
}
