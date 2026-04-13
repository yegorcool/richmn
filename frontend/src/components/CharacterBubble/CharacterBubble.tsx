import { useEffect, useState } from 'react';
import type { CharacterLine } from '@/types/game';
import './CharacterBubble.css';

interface CharacterBubbleProps {
  line: CharacterLine | null;
  onDismiss?: () => void;
}

export function CharacterBubble({ line, onDismiss }: CharacterBubbleProps) {
  const [visible, setVisible] = useState(false);
  const [currentLine, setCurrentLine] = useState<CharacterLine | null>(null);

  useEffect(() => {
    if (line) {
      setCurrentLine(line);
      setVisible(true);
      const timer = setTimeout(() => {
        setVisible(false);
        onDismiss?.();
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [line]);

  if (!visible || !currentLine) return null;

  return (
    <div className="character-bubble" onClick={() => { setVisible(false); onDismiss?.(); }}>
      <div className="character-bubble__content">
        <p className="character-bubble__text">{currentLine.text}</p>
      </div>
      <div className="character-bubble__tail" />
    </div>
  );
}

export function useCharacterBubble() {
  const [line, setLine] = useState<CharacterLine | null>(null);

  useEffect(() => {
    const handler = (e: Event) => {
      const detail = (e as CustomEvent).detail;
      if (detail) setLine(detail);
    };
    window.addEventListener('character-line', handler);
    return () => window.removeEventListener('character-line', handler);
  }, []);

  return { line, clearLine: () => setLine(null) };
}
