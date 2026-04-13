import { useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { parseTelegramWidgetFromSearch, saveTelegramWidgetAuth } from '@/utils/telegramWidgetAuth';

/**
 * Handles Telegram Login Widget redirect flow: backend /auth/telegram → here with query string.
 */
export function AuthTelegramPage() {
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const payload = parseTelegramWidgetFromSearch(location.search);
    if (payload) {
      saveTelegramWidgetAuth(payload);
    }
    navigate('/', { replace: true });
  }, [location.search, navigate]);

  return null;
}
