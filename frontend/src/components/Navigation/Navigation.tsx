import { useLocation, useNavigate } from 'react-router-dom';
import './Navigation.css';

interface NavItem {
  path: string;
  label: string;
  icon: string;
}

const NAV_ITEMS: NavItem[] = [
  { path: '/', label: 'Поле', icon: '🎮' },
  { path: '/decor', label: 'Декор', icon: '🏠' },
  { path: '/events', label: 'Ивенты', icon: '🎉' },
  { path: '/more', label: 'Ещё', icon: '⚙️' },
];

export function Navigation() {
  const location = useLocation();
  const navigate = useNavigate();

  return (
    <nav className="navigation">
      {NAV_ITEMS.map((item) => (
        <button
          key={item.path}
          className={`navigation__item ${location.pathname === item.path ? 'navigation__item--active' : ''}`}
          onClick={() => navigate(item.path)}
        >
          <span className="navigation__icon">{item.icon}</span>
          <span className="navigation__label">{item.label}</span>
        </button>
      ))}
    </nav>
  );
}
