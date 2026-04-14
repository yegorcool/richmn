import { lazy, Suspense, useState, useEffect } from 'react';

const GameField = lazy(() =>
  import('@/components/GameField/GameField').then((m) => ({ default: m.GameField })),
);
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
  const [selectedItem, setSelectedItem] = useState<{ name: string; level: number } | null>(null);

  useEffect(() => {
    const handler = () => setShowRefill(true);
    window.addEventListener('no-energy', handler);
    return () => window.removeEventListener('no-energy', handler);
  }, []);

  useEffect(() => {
    const handler = (e: Event) => {
      const detail = (e as CustomEvent).detail as { name: string; level: number };
      setSelectedItem(detail);
    };
    window.addEventListener('item-selected', handler);
    return () => window.removeEventListener('item-selected', handler);
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
        <Suspense fallback={<div className="game-page__field-loading">Загрузка поля…</div>}>
          <GameField />
        </Suspense>
      </div>

      <div className="game-page__item-info">
        {selectedItem ? (
          <>
            <span className="game-page__item-info-name">{selectedItem.name}</span>
            <span className="game-page__item-info-level">Lv.{selectedItem.level}</span>
          </>
        ) : (
          <span className="game-page__item-info-hint">Нажмите на предмет</span>
        )}
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
