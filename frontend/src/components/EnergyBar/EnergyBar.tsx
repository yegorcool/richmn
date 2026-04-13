import { useEnergy } from '@/hooks/useEnergy';
import './EnergyBar.css';

interface EnergyBarProps {
  onRefillClick: () => void;
}

export function EnergyBar({ onRefillClick }: EnergyBarProps) {
  const { energy, energyMax, formattedTime, isLow, isEmpty, isFull } = useEnergy();
  const percentage = (energy / energyMax) * 100;

  return (
    <div className={`energy-bar ${isLow ? 'energy-bar--low' : ''}`}>
      <div className="energy-bar__icon">⚡</div>
      <div className="energy-bar__track">
        <div
          className="energy-bar__fill"
          style={{ width: `${percentage}%` }}
        />
      </div>
      <div className="energy-bar__count">
        {energy}/{energyMax}
      </div>
      {!isFull && (
        <div className="energy-bar__timer">{formattedTime}</div>
      )}
      {(isLow || isEmpty) && (
        <button className="energy-bar__refill" onClick={onRefillClick}>
          +
        </button>
      )}
    </div>
  );
}
