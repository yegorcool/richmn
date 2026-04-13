import { TelegramLoginWidget } from '@/components/TelegramLoginWidget/TelegramLoginWidget';
import { saveTelegramWidgetAuth } from '@/utils/telegramWidgetAuth';
import './GuestLandingPage.css';

const BOT_NAME = import.meta.env.VITE_TELEGRAM_BOT_NAME ?? 'richmn_bot';

type GuestLandingPageProps = {
  onTelegramWidgetAuth: () => void;
};

export function GuestLandingPage({ onTelegramWidgetAuth }: GuestLandingPageProps) {
  return (
    <div className="guest-landing">
      <TelegramLoginWidget
        botName={BOT_NAME}
        onAuth={(user) => {
          saveTelegramWidgetAuth(user);
          onTelegramWidgetAuth();
        }}
        buttonSize="large"
        cornerRadius={12}
        requestAccess={false}
        usePic
      />
    </div>
  );
}
