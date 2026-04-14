import { useGame } from '@/contexts/GameContext';
import type { Order } from '@/types/game';
import './OrderPanel.css';

export function OrderPanel() {
  const { orders } = useGame();

  return (
    <div className="order-panel">
      {orders.map((order) => (
        <OrderCard key={order.id} order={order} />
      ))}
      {orders.length === 0 && (
        <div className="order-panel__empty">Заказы скоро появятся...</div>
      )}
    </div>
  );
}

function OrderCard({ order }: { order: Order }) {
  const character = order.character;
  const fulfilled = order.required_items.filter((r) => r.fulfilled).length;
  const total = order.required_items.length;

  return (
    <div className="order-card">
      <div className="order-card__avatar">
        <div className="order-card__avatar-circle">
          {character?.name?.charAt(0) ?? '?'}
        </div>
        <span className="order-card__name">{character?.name ?? 'Заказчик'}</span>
      </div>
      <div className="order-card__items">
        {order.required_items.map((req, i) => (
          <div
            key={i}
            className={`order-card__item ${req.fulfilled ? 'order-card__item--done' : ''}`}
          >
            <span className="order-card__item-theme" title={req.theme_slug}>
              {req.theme_slug}
            </span>
            <span className="order-card__item-level">Lv{req.item_level}</span>
            {req.fulfilled && <span className="order-card__check">✓</span>}
          </div>
        ))}
      </div>
      <div className="order-card__progress">
        {fulfilled}/{total}
      </div>
    </div>
  );
}
