import { useEffect, useState } from 'react';
import { gameApi } from '@/services/GameApi';
import type { EventInfo } from '@/types/game';
import './EventsPage.css';

export function EventsPage() {
  const [events, setEvents] = useState<EventInfo[]>([]);
  const [challenges, setChallenges] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [eventsRes, challengeRes] = await Promise.all([
        gameApi.getActiveEvents(),
        gameApi.getDailyChallenge(),
      ]);
      setEvents(eventsRes.events);
      setChallenges(challengeRes);
    } catch (err) {
      console.error('Failed to load events', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="events-page__loading">Загрузка...</div>;

  return (
    <div className="events-page">
      <section className="events-page__section">
        <h2>Ежедневные задания</h2>
        {challenges?.challenges?.map((ch: any, i: number) => (
          <div key={i} className={`events-page__challenge ${(challenges.completed ?? []).includes(i) ? 'events-page__challenge--done' : ''}`}>
            <div className="events-page__challenge-info">
              <span className={`events-page__difficulty events-page__difficulty--${ch.difficulty}`}>
                {ch.difficulty === 'easy' ? 'Лёгкое' : ch.difficulty === 'medium' ? 'Среднее' : 'Сложное'}
              </span>
              <p>{ch.description}</p>
              <div className="events-page__progress-bar">
                <div
                  className="events-page__progress-fill"
                  style={{ width: `${Math.min(100, ((ch.progress || 0) / ch.target) * 100)}%` }}
                />
              </div>
              <span className="events-page__progress-text">{ch.progress || 0}/{ch.target}</span>
            </div>
          </div>
        ))}
      </section>

      {events.length > 0 && (
        <section className="events-page__section">
          <h2>Активные ивенты</h2>
          {events.map((event) => (
            <div key={event.id} className="events-page__event-card">
              <h3>{event.name}</h3>
              <p className="events-page__event-type">{event.type === 'weekly' ? 'Еженедельный' : 'Сезонный'}</p>
              <div className="events-page__event-score">Очки: {event.score}</div>
            </div>
          ))}
        </section>
      )}

      {events.length === 0 && (
        <section className="events-page__section">
          <h2>Активные ивенты</h2>
          <p className="events-page__empty">Скоро начнутся новые ивенты!</p>
        </section>
      )}
    </div>
  );
}
