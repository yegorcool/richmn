import { useState, useEffect } from 'react';
import { GameField } from '@/components/GameField/GameField';
import { OrderPanel } from '@/components/OrderPanel/OrderPanel';
import { EnergyBar } from '@/components/EnergyBar/EnergyBar';
import { CharacterBubble, useCharacterBubble } from '@/components/CharacterBubble/CharacterBubble';
import { useGame } from '@/contexts/GameContext';
import { gameApi } from '@/services/GameApi';
import './GamePage.css';

export function GamePage() {
  const { user, refreshState } = useGame();
  const { line, clearLine } = useCharacterBubble();
  const [showRefill, setShowRefill] = useState(false);

  useEffect(() => {
    const handler = () => setShowRefill(true);
    window.addEventListener('no-energy', handler);
    return () => window.removeEventListener('no-energy', handler);
  }, []);

  const handleRefill = async (source: 'ad' | 'referral') => {
    try {
      await gameApi.refillEnergy(source);
      await refreshState();
      setShowRefill(false);
    } catch (err) {
      console.error('Refill failed', err);
    }
  };

  return (
    <div className="game-page">
      <div className="game-page__header">
        <EnergyBar onRefillClick={() => setShowRefill(true)} />
        <div className="game-page__level">Lv.{user?.level ?? 1}</div>
      </div>

      <div className="game-page__replica-slot">
        <CharacterBubble line={line} onDismiss={clearLine} />
      </div>

      <OrderPanel />

      <div className="game-page__field">
        <GameField />
      </div>

      {showRefill && (
        <div className="game-page__modal-overlay" onClick={() => setShowRefill(false)}>
          <div className="game-page__modal" onClick={(e) => e.stopPropagation()}>
            <h3>Получить заряды</h3>
            <button className="game-page__modal-btn game-page__modal-btn--primary" onClick={() => handleRefill('ad')}>
              Посмотреть рекламу (+10)
            </button>
            <button className="game-page__modal-btn" onClick={() => setShowRefill(false)}>
              Подождать
            </button>
            <button className="game-page__modal-btn" onClick={() => handleRefill('referral')}>
              Пригласить друга (+20)
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
