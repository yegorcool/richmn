import { useEffect, useState } from 'react';
import { useGame } from '@/contexts/GameContext';
import { gameApi } from '@/services/GameApi';
import type { StreakInfo } from '@/types/game';
import { STREAK_REWARDS } from '@/config/constants';
import './MorePage.css';

export function MorePage() {
  const { user } = useGame();
  const [streak, setStreak] = useState<StreakInfo | null>(null);
  const [referralLink, setReferralLink] = useState('');
  const [invitedCount, setInvitedCount] = useState(0);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [streakRes, refRes] = await Promise.all([
        gameApi.getStreak(),
        gameApi.getReferral(),
      ]);
      setStreak(streakRes);
      setReferralLink(refRes.link);
      setInvitedCount(refRes.invited_count);
    } catch (err) {
      console.error(err);
    }
  };

  const handleClaimStreak = async () => {
    try {
      await gameApi.claimStreak();
      const res = await gameApi.getStreak();
      setStreak(res);
    } catch (err) {
      console.error(err);
    }
  };

  const handleClaimGifts = async () => {
    try {
      const res = await gameApi.claimGifts();
      if (res.claimed > 0) {
        alert(`Получено ${res.energy_received} зарядов от ${res.claimed} подарков!`);
      }
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div className="more-page">
      <section className="more-page__section">
        <h2>Профиль</h2>
        <div className="more-page__card">
          <div className="more-page__profile">
            <div className="more-page__avatar">{user?.first_name?.charAt(0) ?? '?'}</div>
            <div>
              <div className="more-page__name">{user?.first_name} {user?.last_name ?? ''}</div>
              <div className="more-page__level">Уровень {user?.level}</div>
            </div>
          </div>
          <div className="more-page__stats">
            <div className="more-page__stat">
              <span>Монеты</span>
              <strong>{user?.coins ?? 0}</strong>
            </div>
            <div className="more-page__stat">
              <span>Опыт</span>
              <strong>{user?.experience ?? 0}</strong>
            </div>
          </div>
        </div>
      </section>

      <section className="more-page__section">
        <h2>Ежедневный бонус</h2>
        <div className="more-page__card">
          <div className="more-page__streak-days">
            {STREAK_REWARDS.map((r) => (
              <div key={r.day} className={`more-page__streak-day ${streak && r.day <= (streak.current_streak % 7 || 7) ? 'more-page__streak-day--active' : ''}`}>
                <div className="more-page__streak-num">{r.day}</div>
                <div className="more-page__streak-label">{r.label}</div>
              </div>
            ))}
          </div>
          {streak && !streak.reward_claimed_today && (
            <button className="more-page__btn" onClick={handleClaimStreak}>Забрать награду</button>
          )}
          {streak?.reward_claimed_today && (
            <p className="more-page__claimed">Награда получена! Приходи завтра</p>
          )}
        </div>
      </section>

      <section className="more-page__section">
        <h2>Друзья</h2>
        <div className="more-page__card">
          <p>Пригласи друзей и получи +20 зарядов!</p>
          <div className="more-page__referral">
            <input type="text" readOnly value={referralLink} className="more-page__referral-input" />
          </div>
          <p className="more-page__invited">Приглашено: {invitedCount}</p>
          <button className="more-page__btn" onClick={handleClaimGifts}>Получить подарки</button>
        </div>
      </section>
    </div>
  );
}
