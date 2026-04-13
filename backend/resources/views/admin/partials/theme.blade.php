{{-- Тёмная админ-тема: primary/secondary/accent из градиента CTA (cyan → violet → pink), без внешних библиотек --}}
<style>
:root {
    --admin-bg: #0a0b10;
    --admin-surface: #12141c;
    --admin-surface-hover: #1a1d28;
    --admin-border: #2d3348;
    --admin-text: #f1f4f8;
    --admin-muted: #8b92a8;
    --admin-sidebar: #07080c;
    --admin-sidebar-hover: #12141d;
    --admin-sidebar-border: #1a1e2e;
    /* Три цвета из градиента CTA: cyan → violet → pink */
    --admin-primary: rgb(64, 221, 255);
    --admin-primary-hover: rgb(110, 236, 255);
    --admin-primary-muted: rgba(64, 221, 255, 0.18);
    --admin-secondary: rgb(118, 18, 250);
    --admin-secondary-hover: rgb(154, 75, 252);
    --admin-secondary-muted: rgba(118, 18, 250, 0.22);
    --admin-secondary-ink: #faf8ff;
    --admin-accent: rgb(250, 18, 227);
    --admin-accent-hover: rgb(255, 92, 236);
    --admin-accent-muted: rgba(250, 18, 227, 0.2);
    --admin-accent-ink: #1a0518;
    --admin-gradient-cta: linear-gradient(100deg, rgb(64, 221, 255) -6.08%, rgb(118, 18, 250) 25.08%, rgb(250, 18, 227));
    --admin-gradient-cta-hover: linear-gradient(100deg, rgb(100, 232, 255) -6.08%, rgb(145, 65, 252) 25.08%, rgb(255, 95, 238));
    /* Семантика */
    --admin-warning: #fbbf24;
    --admin-warning-hover: #fcd34d;
    --admin-warning-muted: rgba(251, 191, 36, 0.2);
    --admin-warning-ink: #1a1404;
    --admin-danger: #ff6b8a;
    --admin-danger-hover: #ff8fab;
    --admin-danger-muted: rgba(255, 107, 138, 0.18);
    --admin-success-bg: rgba(52, 211, 153, 0.14);
    --admin-success-text: #6ee7b7;
    --admin-error-bg: rgba(255, 107, 138, 0.12);
    --admin-error-text: #ffa7bb;
    --admin-radius: 10px;
    --admin-radius-sm: 7px;
    --admin-font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --admin-sidebar-w: 252px;
}

*, *::before, *::after { box-sizing: border-box; }
* { margin: 0; padding: 0; }

body.admin-app {
    font-family: var(--admin-font);
    background: var(--admin-bg);
    color: var(--admin-text);
    line-height: 1.5;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

/* Не красим ссылки-кнопки (.btn — иначе перебивается цвет текста, напр. «Создать набор») */
.admin-app a:not(.btn) { color: var(--admin-accent); text-decoration: none; }
.admin-app a:not(.btn):hover { color: var(--admin-accent-hover); }

.link-accent { color: var(--admin-accent) !important; font-weight: 600; }
.link-accent:hover { color: var(--admin-accent-hover) !important; }

.link-primary { color: var(--admin-primary) !important; font-weight: 600; }
.link-primary:hover { color: var(--admin-primary-hover) !important; }

.admin-app table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.admin-app th,
.admin-app td {
    padding: 0.65rem 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
}

.admin-app th {
    background: var(--admin-surface-hover);
    font-weight: 600;
    color: var(--admin-muted);
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.04em;
}

.admin-app tbody tr:hover td { background: rgba(255, 255, 255, 0.02); }

.admin-app code {
    font-size: 0.75rem;
    background: var(--admin-bg);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    border: 1px solid var(--admin-border);
    color: var(--admin-muted);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.45rem 0.95rem;
    border: 1px solid transparent;
    border-radius: var(--admin-radius-sm);
    cursor: pointer;
    font-size: 0.8125rem;
    font-weight: 600;
    font-family: inherit;
    text-decoration: none;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-primary {
    background: var(--admin-gradient-cta);
    background-size: 120% 120%;
    background-position: 50% 50%;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    box-shadow:
        0 0 28px rgba(118, 18, 250, 0.38),
        0 0 14px rgba(64, 221, 255, 0.22);
    transition: background 0.2s, box-shadow 0.2s, filter 0.2s;
}

.btn-primary:hover {
    background: var(--admin-gradient-cta-hover);
    background-size: 120% 120%;
    background-position: 50% 50%;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    box-shadow:
        0 0 36px rgba(250, 18, 227, 0.35),
        0 0 22px rgba(118, 18, 250, 0.45);
}

.btn-secondary {
    background: var(--admin-secondary-muted);
    color: var(--admin-secondary-hover);
    border: 1px solid rgba(118, 18, 250, 0.45);
}

.btn-secondary:hover {
    background: var(--admin-secondary);
    color: #fff;
    border-color: var(--admin-secondary);
}

.btn-accent {
    background: var(--admin-accent-muted);
    color: var(--admin-accent-hover);
    border: 1px solid rgba(250, 18, 227, 0.45);
}

.btn-accent:hover {
    background: var(--admin-accent);
    color: #fff;
    border-color: var(--admin-accent);
}

.btn-warning {
    background: var(--admin-warning-muted);
    color: var(--admin-warning);
    border: 1px solid rgba(251, 191, 36, 0.4);
}

.btn-warning:hover {
    background: var(--admin-warning);
    color: var(--admin-warning-ink);
    border-color: var(--admin-warning);
}

.btn-danger {
    background: var(--admin-danger-muted);
    color: var(--admin-danger);
    border: 1px solid rgba(255, 107, 138, 0.4);
}

.btn-danger:hover {
    background: var(--admin-danger);
    color: #fff;
    border-color: var(--admin-danger);
}

.btn-sm { padding: 0.3rem 0.65rem; font-size: 0.75rem; }

/* Иконки действий в таблицах */
.actions-row {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border: none;
    border-radius: var(--admin-radius-sm);
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    transition: background 0.15s, color 0.15s, transform 0.12s, box-shadow 0.15s;
}

.btn-icon svg { flex-shrink: 0; }

.btn-icon:hover { transform: translateY(-1px); }

.btn-icon:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px var(--admin-primary-muted);
}

.btn-icon--primary {
    background: var(--admin-primary-muted);
    color: var(--admin-primary);
}

.btn-icon--primary:hover {
    background: var(--admin-primary);
    color: #fff;
    box-shadow: 0 4px 18px rgba(64, 221, 255, 0.45);
}

.btn-icon--secondary {
    background: var(--admin-secondary-muted);
    color: var(--admin-secondary);
}

.btn-icon--secondary:hover {
    background: var(--admin-secondary);
    color: #fff;
    box-shadow: 0 4px 18px rgba(118, 18, 250, 0.45);
}

.btn-icon--accent {
    background: var(--admin-accent-muted);
    color: var(--admin-accent);
}

.btn-icon--accent:hover {
    background: var(--admin-accent);
    color: #fff;
    box-shadow: 0 4px 18px rgba(250, 18, 227, 0.45);
}

.btn-icon--warning {
    background: var(--admin-warning-muted);
    color: var(--admin-warning);
}

.btn-icon--warning:hover {
    background: var(--admin-warning);
    color: var(--admin-warning-ink);
    box-shadow: 0 4px 16px rgba(251, 191, 36, 0.35);
}

.btn-icon--danger {
    background: var(--admin-danger-muted);
    color: var(--admin-danger);
}

.btn-icon--danger:hover {
    background: var(--admin-danger);
    color: #fff;
    box-shadow: 0 4px 16px rgba(255, 107, 138, 0.35);
}

.btn-ghost {
    background: transparent;
    color: var(--admin-muted);
    border: 1px solid var(--admin-border);
    width: 100%;
}

.btn-ghost:hover {
    background: var(--admin-surface-hover);
    color: var(--admin-text);
    border-color: var(--admin-muted);
}

.admin-app input,
.admin-app select,
.admin-app textarea {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-sm);
    font-size: 0.8125rem;
    font-family: inherit;
    background: var(--admin-bg);
    color: var(--admin-text);
}

.admin-app input:focus,
.admin-app select:focus,
.admin-app textarea:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px var(--admin-primary-muted);
}

.form-group { margin-bottom: 0.75rem; }

.form-group label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
    color: var(--admin-muted);
}

.alert {
    padding: 0.65rem 1rem;
    border-radius: var(--admin-radius-sm);
    margin-bottom: 1rem;
    font-size: 0.8125rem;
    border: 1px solid transparent;
}

.alert-success {
    background: var(--admin-success-bg);
    color: var(--admin-success-text);
    border-color: rgba(52, 211, 153, 0.35);
}

.alert-error {
    background: var(--admin-error-bg);
    color: var(--admin-error-text);
    border-color: rgba(255, 107, 138, 0.35);
}

.pagination-wrap { margin-top: 1rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1rem; }

.pagination-info { font-size: 0.8125rem; color: var(--admin-muted); }

.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    list-style: none;
}

.pagination a,
.pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.15rem;
    height: 2.15rem;
    padding: 0 0.5rem;
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-sm);
    font-size: 0.8125rem;
    text-decoration: none;
    color: var(--admin-text);
    background: var(--admin-surface);
}

.pagination a:hover {
    background: var(--admin-surface-hover);
    border-color: var(--admin-muted);
}

.pagination span.active {
    background: var(--admin-primary-muted);
    border-color: var(--admin-primary);
    color: var(--admin-primary);
    font-weight: 700;
}

.pagination span.disabled,
.pagination span[aria-disabled="true"] {
    opacity: 0.4;
    cursor: not-allowed;
}

.text-muted { color: var(--admin-muted); }
.text-center { text-align: center; }

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.btn.btn-with-icon {
    gap: 0.4rem;
}

/* Верхняя панель: заголовок страницы + выход */
body.admin-app .admin-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

body.admin-app .admin-topbar__title {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
    min-width: 0;
}

body.admin-app .admin-topbar__logout {
    margin: 0;
    padding: 0;
    border: none;
    background: none;
    flex-shrink: 0;
}

body.admin-app .btn-topbar-logout {
    background: var(--admin-surface);
    color: var(--admin-muted);
    border: 1px solid var(--admin-border);
}

body.admin-app .btn-topbar-logout:hover {
    background: var(--admin-surface-hover);
    color: var(--admin-text);
    border-color: var(--admin-muted);
}

body.admin-app .admin-main-inner {
    min-width: 0;
}
.mt-lg { margin-top: 1.5rem; }
.mb-sm { margin-bottom: 0.75rem; }

.level-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--admin-gradient-cta);
    background-size: 180% 180%;
    background-position: 40% 50%;
    color: #fff;
    font-weight: 700;
    font-size: 0.7rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
    box-shadow: 0 2px 12px rgba(118, 18, 250, 0.4);
}

.thumb-48 {
    width: 48px;
    height: 48px;
    object-fit: contain;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
}

.thumb-placeholder {
    display: inline-flex;
    width: 48px;
    height: 48px;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-hover);
    border: 1px solid var(--admin-border);
    align-items: center;
    justify-content: center;
    color: var(--admin-muted);
    font-size: 0.75rem;
}

.merge-thumb {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: var(--admin-radius-sm);
}

.merge-placeholder {
    width: 40px;
    height: 40px;
    background: var(--admin-surface-hover);
    border-radius: var(--admin-radius-sm);
    line-height: 40px;
    text-align: center;
    font-size: 0.65rem;
    color: var(--admin-muted);
    border: 1px solid var(--admin-border);
}

.merge-caption {
    font-size: 0.625rem;
    color: var(--admin-muted);
    max-width: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.merge-arrow { color: var(--admin-border); font-size: 1.1rem; }

.form-preview-img {
    width: 64px;
    height: 64px;
    object-fit: contain;
    border-radius: var(--admin-radius-sm);
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
}

.inline-form { display: inline; }

/* Login */
body.admin-app.admin-login {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 1.5rem;
    background:
        radial-gradient(ellipse 100% 70% at 50% -30%, var(--admin-primary-muted), transparent 50%),
        radial-gradient(ellipse 70% 50% at 0% 80%, var(--admin-secondary-muted), transparent 42%),
        radial-gradient(ellipse 80% 50% at 100% 100%, var(--admin-accent-muted), transparent 45%),
        var(--admin-bg);
}

.admin-login .login-card {
    width: 100%;
    max-width: 380px;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    padding: 2rem 1.75rem;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
}

.admin-login .login-card h1 {
    font-size: 1.35rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
}

.admin-login .form-group { margin-bottom: 1rem; }

.admin-login .form-group label { color: var(--admin-muted); }

.admin-login .form-group input {
    width: 100%;
    padding: 0.65rem 0.85rem;
}

.admin-login .btn {
    width: 100%;
    padding: 0.7rem;
    margin-top: 0.25rem;
    font-size: 0.9rem;
}

.admin-login .error {
    color: var(--admin-error-text);
    font-size: 0.8125rem;
    text-align: center;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: var(--admin-error-bg);
    border-radius: var(--admin-radius-sm);
    border: 1px solid rgba(255, 107, 138, 0.35);
}

/* Старые классы в дочерних Blade — те же токены */
body.admin-app .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: var(--admin-sidebar-w);
    background: var(--admin-sidebar);
    border-right: 1px solid var(--admin-sidebar-border);
    padding: 1.25rem 0;
    z-index: 40;
}

body.admin-app .sidebar nav a {
    display: block;
    margin: 0 0.65rem;
    padding: 0.55rem 0.85rem;
    border-radius: var(--admin-radius-sm);
    border: 1px solid transparent;
    background: transparent;
    color: var(--admin-muted);
    font-size: 0.875rem;
    text-decoration: none;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
}

/* Неактивный: лёгкий фон только при наведении */
body.admin-app .sidebar nav a:hover:not(.active) {
    background: var(--admin-sidebar-hover);
    color: var(--admin-text);
}

/* Активный: тот же прозрачный фон, градиент только на тексте */
body.admin-app .sidebar nav a.active,
body.admin-app .sidebar nav a.active:hover {
    font-weight: 600;
    border-color: transparent;
    box-shadow: none;
    background: var(--admin-gradient-cta-hover);
    background-size: 120% 120%;
    background-position: 50% 50%;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
}

body.admin-app .main {
    margin-left: var(--admin-sidebar-w);
    padding: 1.25rem 2rem;
    min-width: 0;
}

/* Панель кнопок/фильтров над карточками — всё слева в ряд */
body.admin-app .header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

body.admin-app .header > div {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

body.admin-app .card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1rem;
}

body.admin-app .card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

body.admin-app .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

body.admin-app .stat-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    padding: 1.1rem 1.2rem;
    transition: border-color 0.15s;
}

body.admin-app .stat-card:hover { border-color: var(--admin-muted); }

body.admin-app .stat-card .value {
    font-size: 1.65rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    background: var(--admin-gradient-cta);
    background-size: 140% 140%;
    background-position: 30% 50%;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    line-height: 1.2;
}

body.admin-app .stat-card .label {
    font-size: 0.8125rem;
    color: var(--admin-muted);
    margin-top: 0.35rem;
}

body.admin-app .card.card--muted {
    background: var(--admin-surface-hover);
}

body.admin-app .header form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

body.admin-app .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

body.admin-app .grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

body.admin-app .grid-1-2 {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 0.75rem;
}

@media (max-width: 900px) {
    body.admin-app .grid-4 { grid-template-columns: repeat(2, 1fr); }
    body.admin-app .grid-1-2 { grid-template-columns: 1fr; }
}

body.admin-app .input-block { width: 100%; }

body.admin-app .form-row-preview {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    body.admin-app .sidebar {
        position: relative;
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--admin-sidebar-border);
        padding-bottom: 1.25rem;
    }

    body.admin-app .main {
        margin-left: 0;
    }
}
</style>
