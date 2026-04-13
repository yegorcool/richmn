import { useCallback, useEffect, useRef, useState } from 'react';
import { useGame } from '@/contexts/GameContext';
import { ENERGY_RECOVERY_MINUTES } from '@/config/constants';

export function useEnergy() {
  const { energy, energyMax, setEnergy } = useGame();
  const [secondsToNext, setSecondsToNext] = useState(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | undefined>(undefined);

  useEffect(() => {
    if (energy >= energyMax) {
      setSecondsToNext(0);
      return;
    }

    setSecondsToNext(ENERGY_RECOVERY_MINUTES * 60);

    timerRef.current = setInterval(() => {
      setSecondsToNext((prev) => {
        if (prev <= 1) {
          setEnergy(Math.min(energy + 1, energyMax));
          return ENERGY_RECOVERY_MINUTES * 60;
        }
        return prev - 1;
      });
    }, 1000);

    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [energy, energyMax, setEnergy]);

  const formatTime = useCallback((seconds: number): string => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  }, []);

  return {
    energy,
    energyMax,
    secondsToNext,
    formattedTime: formatTime(secondsToNext),
    isLow: energy <= 10,
    isEmpty: energy === 0,
    isFull: energy >= energyMax,
  };
}
