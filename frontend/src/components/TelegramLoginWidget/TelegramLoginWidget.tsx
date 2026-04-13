import { useEffect, useRef } from 'react';
import './TelegramLoginWidget.css';

/** Stable name so Telegram iframe/popup always finds the handler (Strict Mode + Date.now broke the old flow). */
const TELEGRAM_WIDGET_GLOBAL_CALLBACK = 'richmnTelegramAuth';

type TelegramWidgetUser = {
  id: number;
  first_name?: string;
  last_name?: string;
  username?: string;
  photo_url?: string;
  auth_date: number;
  hash: string;
};

type TelegramLoginWidgetProps = {
  botName: string;
  onAuth?: (user: TelegramWidgetUser) => void;
  /** If set, Telegram redirects to this URL with query params (use backend /auth/telegram in production). */
  authUrl?: string;
  buttonSize?: 'large' | 'medium' | 'small';
  cornerRadius?: number;
  requestAccess?: boolean;
  usePic?: boolean;
};

export function TelegramLoginWidget({
  botName,
  onAuth,
  authUrl,
  buttonSize = 'large',
  cornerRadius = 10,
  requestAccess = false,
  usePic = true,
}: TelegramLoginWidgetProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const onAuthRef = useRef(onAuth);
  const authUrlRef = useRef(authUrl);
  onAuthRef.current = onAuth;
  authUrlRef.current = authUrl;

  useEffect(() => {
    const el = containerRef.current;
    if (!el) {
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://telegram.org/js/telegram-widget.js?22';
    script.async = true;
    script.setAttribute('data-telegram-login', botName);
    script.setAttribute('data-size', buttonSize);
    script.setAttribute('data-request-access', requestAccess ? 'write' : '');
    script.setAttribute('data-userpic', String(usePic));
    script.setAttribute('data-radius', String(cornerRadius));

    const useCallbackFlow = Boolean(onAuthRef.current);

    if (useCallbackFlow) {
      el.innerHTML = '';

      const w = window as unknown as Record<string, (user: TelegramWidgetUser) => void>;
      w[TELEGRAM_WIDGET_GLOBAL_CALLBACK] = (user: TelegramWidgetUser) => {
        queueMicrotask(() => onAuthRef.current?.(user));
      };

      // Telegram parses this as the *body* of (function (user) { ... }), so it must invoke the handler — see core.telegram.org/widgets/login
      script.setAttribute('data-onauth', `${TELEGRAM_WIDGET_GLOBAL_CALLBACK}(user)`);
      el.appendChild(script);

      return () => {
        delete (window as unknown as Record<string, unknown>)[TELEGRAM_WIDGET_GLOBAL_CALLBACK];
        el.innerHTML = '';
      };
    }

    const url = authUrlRef.current;
    if (url) {
      script.setAttribute('data-auth-url', url);
    }
    el.appendChild(script);

    return () => {
      script.remove();
    };
  }, [botName, buttonSize, cornerRadius, requestAccess, usePic]);

  return <div ref={containerRef} className="telegram-login-widget" />;
}
