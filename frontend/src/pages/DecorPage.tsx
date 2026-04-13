import { useEffect, useState } from 'react';
import { gameApi } from '@/services/GameApi';
import type { DecorLocation } from '@/types/game';
import './DecorPage.css';

export function DecorPage() {
  const [locations, setLocations] = useState<DecorLocation[]>([]);
  const [selectedLocation, setSelectedLocation] = useState<DecorLocation | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadLocations();
  }, []);

  const loadLocations = async () => {
    try {
      const res = await gameApi.getDecorLocations();
      setLocations(res.locations);
      if (res.locations.length > 0) setSelectedLocation(res.locations[0]);
    } catch (err) {
      console.error('Failed to load decor', err);
    } finally {
      setLoading(false);
    }
  };

  const handlePlaceDecor = async (itemKey: string, style: number) => {
    if (!selectedLocation) return;
    try {
      await gameApi.placeDecor(selectedLocation.id, itemKey, style);
      await loadLocations();
    } catch (err) {
      console.error('Failed to place decor', err);
    }
  };

  if (loading) return <div className="decor-page__loading">Загрузка...</div>;

  return (
    <div className="decor-page">
      <div className="decor-page__tabs">
        {locations.map((loc) => (
          <button
            key={loc.id}
            className={`decor-page__tab ${selectedLocation?.id === loc.id ? 'decor-page__tab--active' : ''}`}
            onClick={() => setSelectedLocation(loc)}
          >
            {loc.name}
          </button>
        ))}
      </div>

      {selectedLocation && (
        <div className="decor-page__content">
          <h2 className="decor-page__title">{selectedLocation.name}</h2>
          <div className="decor-page__grid">
            {((selectedLocation as any).available_items as any[])?.map((item: any) => {
              const placed = ((selectedLocation as any).placed_items as any[])?.find((p: any) => p.item_key === item.key);
              return (
                <div key={item.key} className={`decor-page__item ${placed ? 'decor-page__item--placed' : ''}`}>
                  <div className="decor-page__item-name">{item.name}</div>
                  <div className="decor-page__item-styles">
                    {[1, 2, 3].map((style) => (
                      <button
                        key={style}
                        className={`decor-page__style-btn ${placed?.style_variant === style ? 'decor-page__style-btn--selected' : ''}`}
                        onClick={() => handlePlaceDecor(item.key, style)}
                      >
                        {style}
                      </button>
                    ))}
                  </div>
                  {placed && <span className="decor-page__placed-badge">Установлено</span>}
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
