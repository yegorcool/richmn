# Richmn — Architecture Document

## 1. Overview

Richmn (Merge Town) is a casual merge-2 game delivered as a MiniApp in two messengers simultaneously:
- **Domain 1** (`tg.richmn.com`) — Telegram MiniApp
- **Domain 2** (`max.richmn.com`) — MAX MiniApp

The project is a **monorepo** with shared backend and frontend code. Users from different platforms are treated as separate entities (no cross-platform matching). Each user record carries a `source` field (`telegram` | `max`).

---

## 2. High-Level Architecture

```
┌──────────────────┐    ┌──────────────────┐
│ Telegram MiniApp │    │   MAX MiniApp    │
│ tg.richmn.com    │    │ max.richmn.com   │
└────────┬─────────┘    └────────┬─────────┘
         │          HTTPS         │
         └──────────┬─────────────┘
                    ▼
         ┌──────────────────┐
         │      Nginx       │
         │ SSL termination  │
         │ Routing          │
         └─────┬──────┬─────┘
               │      │
     /api/*    │      │  /* static
     /admin/*  │      │
               ▼      ▼
     ┌──────────┐  ┌──────────────┐
     │ PHP-FPM  │  │ React Static │
     │ Laravel  │  │ (Vite build) │
     └────┬─────┘  └──────────────┘
          │
    ┌─────┼─────┐
    ▼     ▼     ▼
┌──────┐┌─────┐┌───────────┐
│MySQL ││Redis││Queue Worker│
└──────┘└─────┘└───────────┘
```

---

## 3. Repository Structure

```
richmn/
├── backend/                          # Laravel 12 application
│   ├── app/
│   │   ├── Console/Commands/         # Artisan commands (notifications, timers, events)
│   │   ├── Helpers/                  # Utility helpers
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/              # REST API controllers (/api/*)
│   │   │   │   └── Admin/            # Admin panel controllers (/admin/*)
│   │   │   ├── Middleware/
│   │   │   │   ├── MiniAppValidation.php    # Telegram + MAX initData validation
│   │   │   │   ├── AdminAuth.php            # Admin session auth
│   │   │   │   └── CheckAdminRole.php       # Admin RBAC
│   │   │   ├── Requests/             # Form request validation
│   │   │   └── Resources/            # API resource serialization
│   │   ├── Models/                   # Eloquent models
│   │   ├── Providers/                # Service providers
│   │   └── Services/                 # Business logic services
│   ├── bootstrap/
│   ├── config/
│   │   ├── telegram.php              # Telegram bot settings
│   │   ├── max.php                   # MAX messenger settings
│   │   ├── cors.php                  # CORS for both domains
│   │   └── ...
│   ├── database/
│   │   ├── migrations/               # DB schema
│   │   ├── seeders/                  # Initial data (themes, characters, lines)
│   │   └── factories/
│   ├── routes/
│   │   ├── api.php                   # /api/* routes (MiniApp auth middleware)
│   │   ├── web.php                   # /admin/* routes + SPA fallback
│   │   └── console.php               # Scheduled commands
│   ├── resources/views/admin/        # Blade templates for admin panel
│   ├── .env.example
│   └── composer.json
├── frontend/                         # React + TypeScript SPA
│   ├── public/
│   ├── src/
│   │   ├── components/               # React components
│   │   ├── hooks/                    # Custom hooks (usePlatform, useGame, etc.)
│   │   ├── services/                 # API client (axios singleton)
│   │   ├── types/                    # TypeScript type definitions
│   │   ├── contexts/                 # React contexts (Auth, Game, Platform)
│   │   ├── utils/                    # Helpers (auth, platform detection)
│   │   ├── config/                   # App constants, theme config
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── index.html
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── package.json
├── nginx/                            # Nginx configuration templates
│   └── richmn.conf
├── docs/                             # Documentation
│   ├── PRD.md
│   └── ARCHITECTURE.md
└── .gitignore
```

---

## 4. Technology Stack

| Layer | Technology | Version |
|---|---|---|
| **Web Server** | Nginx | latest |
| **Application** | PHP-FPM + Laravel | PHP 8.2+ / Laravel 12 |
| **Frontend** | React + TypeScript + Vite | React 19 / Vite 7 / TS 5.8 |
| **Database** | MySQL | 8.0+ |
| **Cache / Queue** | Redis | 7+ |
| **Telegram SDK** | `@twa-dev/sdk` | latest |
| **MAX SDK** | MAX MiniApp JS SDK | latest |
| **HTTP Client** | Axios | latest |
| **Routing (FE)** | react-router-dom | v7 |

---

## 5. Dual-Platform Architecture

### 5.1 Nginx Configuration

Two `server` blocks, both proxying to the same Laravel + React application:

- `tg.richmn.com` — Telegram MiniApp
- `max.richmn.com` — MAX MiniApp

Both share identical routing rules:
- `/api/*`, `/admin/*` → PHP-FPM (Laravel)
- `/*` → React static build (`frontend/dist/`)

See `nginx/richmn.conf` for the full configuration template.

### 5.2 Platform Detection

The frontend detects which platform it runs on via:
1. **Domain name**: `window.location.hostname` (`tg.*` vs `max.*`)
2. **SDK availability**: `window.Telegram?.WebApp` for Telegram, MAX SDK global for MAX

A `PlatformContext` React context exposes the current platform (`telegram` | `max`) and an abstracted API for haptic feedback, theme params, expand/close, etc.

### 5.3 Authentication Flow

**A. MiniApp (in messenger)**

```
User opens MiniApp in Telegram or MAX
        ↓
React app detects platform (tg / max)
        ↓
Gets initData from platform SDK
        ↓
Sends API request with X-Platform-Init-Data + X-Platform headers
        ↓
Laravel MiniAppValidation middleware:
  - If platform = telegram → validate WebApp initData HMAC (secret: HMAC_SHA256("WebAppData", bot_token))
  - If platform = max → validate initData HMAC with MAX app secret
        ↓
Find or create user by (platform_id, source)
        ↓
Attach user to request, continue
```

**B. Browser (Telegram Login Widget only)**

Used when the SPA is opened in a normal browser: there is no `initData`. The user sees a minimal guest screen with the official [Telegram Login Widget](https://core.telegram.org/widgets/login) only (no marketing copy).

```
User opens site in browser (e.g. tg.richmn.com over HTTPS)
        ↓
No initData → guest shell renders widget
        ↓
User confirms in widget → callback stores signed payload in localStorage
        ↓
API client sends the same fields as query parameters on each /api/* request
        ↓
MiniAppValidation (platform = telegram):
  - If X-Platform-Init-Data is missing/invalid → validate Login Widget HMAC
    (secret: SHA256(bot_token), per Telegram widget spec — different from WebApp)
        ↓
Find or create user with source = telegram (same users table as MiniApp)
```

Optional redirect flow for the widget: `data-auth-url` can point at `GET /auth/telegram` on Laravel; Laravel redirects to `APP_FRONTEND_URL/login/telegram?…` (separate from `/auth/*` so the same public origin does not loop). The in-app **callback** flow (`data-onauth`) is the default and needs no redirect.

**C. MAX — browser / future**

MAX MiniApps keep using initData only (same as today). A future **MAX browser** login (widget or OAuth) is intentionally not implemented yet; architecturally it will mirror block **B**: a dedicated credential channel (headers or query) validated in `MiniAppValidation` when `X-Platform: max`, reusing the same `users` row shape (`source = max`). No extra tables are required beyond optional token/session storage if the future API demands it.

### 5.4 User Model

```
users table:
- id (bigint, PK, auto-increment)
- platform_id (string)         — Telegram user ID or MAX user ID
- source (enum: telegram, max) — platform the user came from
- username (string, nullable)
- first_name (string)
- last_name (string, nullable)
- avatar_url (string, nullable)
- is_premium (boolean, default: false)
- language_code (string, default: 'ru')
- level (int, default: 1)
- experience (int, default: 0)
- energy (int, default: 50)
- energy_updated_at (timestamp)
- coins (bigint, default: 0)
- referral_code (string, unique)
- referred_by (bigint, nullable, FK → users.id)
- last_activity (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
- UNIQUE INDEX (platform_id, source)
```

The same physical person from Telegram and MAX will be stored as two separate rows — no cross-platform matching.

---

## 6. Backend Architecture

### 6.1 API Routes (`/api/*`)

All routes (except `/api/health`) are protected by `MiniAppValidation` middleware.

| Route Group | Description |
|---|---|
| `GET /api/health` | Health check (public) |
| `/api/user/*` | Profile, stats, settings |
| `/api/game/*` | Game state, merge operations, energy, generators |
| `/api/orders/*` | Order list, complete, partial submit |
| `/api/characters/*` | Character lines, mood, relationship |
| `/api/events/*` | Active events, progress, rewards |
| `/api/decor/*` | Decoration state, place/remove decor |
| `/api/chests/*` | Chest list, open (timer or ad) |
| `/api/collection/*` | Discovered items album |
| `/api/referral/*` | Referral info, invite link |
| `/api/notifications/*` | Notification settings, history |
| `/api/ads/*` | Ad view callbacks, reward tracking |

### 6.2 Admin Routes (`/admin/*`)

Session-based authentication (login/password from `.env`), rendered with Laravel Blade views.

Admin panel sections:
- **Dashboard** — KPIs (DAU, retention, revenue, ad metrics)
- **Users** — search, view profiles, edit, ban
- **Game Config** — themes, generators, orders, energy balance tuning
- **Characters** — character lines CRUD, trigger configuration
- **Events** — create/schedule events, manage rewards
- **Analytics** — activity stats, ad stats, retention funnel
- **Notifications** — broadcast messages to users

### 6.3 Key Services

| Service | Responsibility | Status |
|---|---|---|
| `MergeService` | Validate and execute merge-2 logic, chain merges, energy deduction, experience gain | ✅ Implemented |
| `GeneratorService` | Generator tap logic: energy check, spawn level, slot search, cooldown management | ✅ Implemented |
| `GameInitService` | Place 3 starter generators for new users on first login (idempotent) | ✅ Implemented |
| `EnergyService` | Track charges, auto-recover, rewarded refill, source-tracked spending | ✅ Implemented |
| `OrderService` | Generate orders, validate completion, distribute rewards | ✅ Implemented |
| `CharacterLineService` | Select character line by trigger + conditions (PRD section 6.2) | ✅ Implemented |
| `EventService` | Manage weekly/seasonal events, progress tracking | Planned |
| `DecorService` | Track decoration state per location | Planned |
| `ChestService` | Chest timers, opening logic, loot tables | Planned |
| `TelegramService` | Validate Telegram WebApp initData, validate Login Widget payloads, send bot notifications | ✅ Implemented |
| `MaxService` | Validate MAX initData, send push notifications | Planned |
| `AdService` | Track ad views, enforce daily limits, reward distribution | Planned |
| `NotificationService` | Send messages via Telegram Bot API / MAX API | Planned |

### 6.4 Console Commands (Scheduled)

| Command | Schedule | Description |
|---|---|---|
| `energy:recover` | Every minute | Compute energy recovery for users |
| `chests:process` | Every minute | Update chest unlock timers |
| `generators:cooldown` | Every minute | Reset cooldown generators |
| `notifications:send` | Every minute | Process notification queue |
| `events:manage` | Daily | Start/end events per schedule |
| `daily-challenge:rotate` | Daily 00:00 | Generate new daily challenges |

---

## 7. Frontend Architecture

### 7.1 Platform Abstraction

```typescript
type Platform = 'telegram' | 'max';

interface PlatformAPI {
  platform: Platform;
  initData: string;
  hapticFeedback: (type: string) => void;
  expand: () => void;
  close: () => void;
  themeParams: ThemeParams;
}
```

Hooks:
- `usePlatform()` — returns current platform + abstracted SDK methods
- `useGame()` — game state management
- `useEnergy()` — energy tracking with recovery timer

### 7.2 API Client

Singleton `ApiClient` class (same pattern as topliga):
- Base URL: `/api` (relative, proxied by Nginx in prod / Vite in dev)
- Request interceptor adds `X-Platform-Init-Data` and `X-Platform` headers; for Telegram browser auth, adds Login Widget fields as query parameters
- Response interceptor handles 401 (clear stored widget payload, dispatch session event)
- Typed methods for every API endpoint

### 7.3 Key Components

| Component | Description |
|---|---|
| `GameField` | 6×8 grid with drag-and-drop merge (Canvas) |
| `OrderPanel` | Top panel with character avatars and order cards |
| `CharacterBubble` | Speech bubble with character lines |
| `EnergyBar` | Charge indicator with recovery timer |
| `Navigation` | Bottom tab bar (Field, Decor, Events, More) |
| `ChestModal` | Chest opening with timer / ad option |
| `DecorEditor` | Location decoration interface |

### 7.4 Rendering

Per PRD requirements:
- **Game field**: HTML5 Canvas via PixiJS or Phaser for smooth 60fps rendering
- **UI chrome**: React components for orders, navigation, modals, popups
- **Communication**: Shared state via React context between Canvas game engine and React UI

---

## 8. Merge-2 Game Mechanism — Implementation

Ниже описана реализация ядра игрового процесса: генераторы, предметы, merge, энергия, админка и рендеринг.

### 8.1 Data Model

#### `item_definitions` — справочник предметов (нормализованная таблица)

```
item_definitions
├── id (PK)
├── theme_id (FK → themes.id, CASCADE DELETE)
├── level (tinyint, unsigned) — уровень в цепочке (1–10)
├── name (string) — «Эспрессо», «Клубок», «Ваза»
├── slug (string) — «coffee_3», «fabrics_2»
├── image_url (string, nullable) — URL иконки (Iconify API / локальный storage)
├── created_at, updated_at
└── UNIQUE INDEX (theme_id, level)
```

Каждая тематика содержит до 10 уровней предметов. `image_url` хранит либо внешний URL (`https://api.iconify.design/…`), либо путь в `storage/app/public/items/…` для загруженных файлов.

#### `generators` — экземпляры генераторов на поле игрока

```
generators
├── id (PK)
├── user_id (FK → users.id)
├── theme_id (FK → themes.id)
├── type (enum: chargeable | cooldown)
├── level (int) — уровень генератора (влияет на уровень спавна предметов)
├── charges_left (int) — оставшиеся заряды (только для chargeable)
├── max_charges (int) — максимум зарядов
├── generation_limit (int, default 5) — базовый лимит зарядов
├── generation_timeout_seconds (int, default 1800) — кулдаун перезарядки
├── energy_cost (int, default 1) — стоимость тапа в энергии
├── cooldown_until (datetime, nullable)
├── grid_x, grid_y (int) — позиция на поле 6×8
└── created_at, updated_at
```

#### `items` — предметы на игровом поле

```
items
├── id (PK)
├── user_id (FK → users.id)
├── theme_id (FK → themes.id)
├── item_level (int) — текущий уровень предмета
├── grid_x, grid_y (int) — позиция на поле
└── created_at, updated_at
```

#### `themes` — расширенная конфигурация тематик

К существующей таблице `themes` добавлены поля настроек генератора по умолчанию:

```
themes (дополнительные поля)
├── generator_energy_cost (int, default 1)
├── generator_generation_limit (int, default 5)
├── generator_generation_timeout (int, default 1800)
```

Эти значения используются как дефолты при создании новых генераторов для темы и при первичной инициализации.

### 8.2 Backend Services

#### `GeneratorService` — генерация предметов

Отвечает за логику тапа по генератору:

1. **Проверка готовности**: `Generator::isReady()` — для `chargeable` проверяет `charges_left > 0`, для `cooldown` — проверяет `cooldown_until`.
2. **Проверка энергии**: текущая энергия игрока ≥ `energy_cost` генератора.
3. **Поиск свободной клетки**: сначала соседние 8 клеток вокруг генератора (приоритет), затем полный скан поля 6×8.
4. **Списание энергии**: через `EnergyService::spendEnergy($user, $cost, 'generator')`.
5. **Определение уровня спавна**: `getSpawnLevel()` — взвешенный случайный выбор; генератор уровня N может выдать предмет уровня 1..min(N, 3), с убывающим весом к старшим уровням.
6. **Создание предмета**: `Item::create(...)` с позицией и уровнем.
7. **Обновление генератора**: у `chargeable` — декремент зарядов, при 0 — установка `cooldown_until`; у `cooldown` — установка `cooldown_until` на каждый тап.
8. **Обогащение ответа**: из `ItemDefinition` подгружается `image_url` и `name` для рендеринга на фронте.

Также реализован `mergeGenerators()` — объединение двух генераторов одного уровня и темы в генератор уровня N+1 с увеличенным лимитом зарядов.

#### `MergeService` — объединение предметов (merge-2)

1. **Валидация**: оба предмета принадлежат игроку, одна тема и уровень, не достигнут max_level, достаточно энергии.
2. **Выполнение в транзакции** (`DB::transaction`):
   - Списание энергии (1 заряд за merge, конфигурируется через `game.energy.merge_cost`).
   - Удаление двух исходных предметов.
   - Создание нового предмета уровня N+1 на позиции второго предмета.
   - **Chain merge** — рекурсивный `checkChainMerge()`: проверяет 4 соседние клетки (вверх/вниз/лево/право) на предмет того же уровня и темы. Если найден — автоматически мержит дальше (рекурсия). Возвращает итоговую длину цепочки.
   - Начисление опыта: `base = newLevel × 5`, плюс бонус за цепочку `(chainLength - 1) × 10`.
   - Проверка повышения уровня игрока: порог = `level × 100 + level² × 10`.

#### `GameInitService` — инициализация для нового игрока

При первом входе (если у пользователя нет генераторов) автоматически размещает 3 стартовых генератора:

- Берёт первые 3 активные темы, отсортированные по `unlock_level`.
- Размещает генераторы уровня 1 на позициях `(1,5)`, `(3,5)`, `(5,5)` — нижняя часть поля.
- Каждый генератор получает настройки из своей темы (лимит, таймаут, стоимость энергии).
- Идемпотентно: если генераторы уже есть — ничего не делает.

#### `EnergyService` — система энергии

- `getCurrentEnergy()`: вычисляет текущую энергию с учётом авто-восстановления (1 заряд каждые `recovery_minutes` минут, до `max`).
- `spendEnergy($user, $amount, $source)`: списание с логированием в `energy_logs` (source: `merge` | `generator`).
- `refillFromAd()`: +10 зарядов за просмотр рекламы.
- `refillFromBonus()`: бонусное пополнение (streak, подарок).
- `getRecoverySecondsRemaining()`: время до следующего восстановления.

### 8.3 API Endpoints (Game Mechanism)

| Endpoint | Method | Description |
|---|---|---|
| `GET /api/game/state` | GET | Полное состояние: предметы (с image_url), генераторы, энергия, справочник item_definitions |
| `POST /api/game/generator/tap` | POST | Тап по генератору → спавн предмета, возврат нового предмета с позицией и изображением |
| `POST /api/game/merge` | POST | Merge двух предметов → новый предмет, chain_length, энергия, опыт, реплика персонажа |
| `POST /api/game/move-item` | POST | Перемещение предмета на свободную клетку (drag & drop) |

#### Формат ответа `GET /api/game/state`

```json
{
  "items": [
    {
      "id": 1,
      "theme_id": 1,
      "item_level": 3,
      "grid_x": 2,
      "grid_y": 4,
      "theme_slug": "coffee",
      "image_url": "https://api.iconify.design/noto/teacup-without-handle.svg",
      "item_name": "Эспрессо"
    }
  ],
  "generators": [
    {
      "id": 1,
      "theme_id": 1,
      "type": "chargeable",
      "level": 1,
      "charges_left": 3,
      "max_charges": 5,
      "cooldown_until": null,
      "grid_x": 1,
      "grid_y": 5,
      "theme": { "id": 1, "slug": "coffee", "name": "Кофейня" }
    }
  ],
  "energy": 42,
  "energy_max": 50,
  "energy_recovery_seconds": 120,
  "grid": { "width": 6, "height": 8 },
  "item_definitions": {
    "1": [
      { "level": 1, "name": "Зёрна", "slug": "coffee_1", "image_url": "..." },
      { "level": 2, "name": "Молотый кофе", "slug": "coffee_2", "image_url": "..." }
    ]
  }
}
```

### 8.4 Frontend Rendering (PixiJS)

Игровое поле реализовано на **PixiJS v8** (HTML5 Canvas), UI-обёртка — React.

#### Компонент `GameField`

- **Сетка 6×8**: каждая клетка `56×56px` с зазором 4px. Фон — скруглённые прямоугольники пастельных тонов.
- **Рендер элементов**: при каждом изменении `items` / `generators` полностью перестраивает спрайты (`renderItems`). Данные читаются из refs для защиты от stale closures при асинхронной инициализации PixiJS.
- **Предметы**: цветной фон по уровню (10 пастельных цветов), иконка из `image_url` (async loading через `Assets.load` + кэш в `Map<string, Texture>`), fallback на emoji по теме. Золотая рамка для уровней 8+.
- **Генераторы**: фон зависит от типа (персиковый для `chargeable`, голубой для `cooldown`), иконка ⚙️, индикатор зарядов.

#### Drag & Drop

Предметы поддерживают полный drag & drop через `pointerdown` / `globalpointermove` / `pointerup`:

1. При захвате предмет становится полупрозрачным, увеличивается, поднимается по z-index.
2. При отпускании определяется целевая клетка (`pixelToGrid`).
3. Если в целевой клетке предмет того же уровня и темы → вызов `gameApi.merge()`.
4. Если клетка свободна → вызов `gameApi.moveItem()`.
5. Если за пределами поля → возврат на исходную позицию.

#### Анимации

| Анимация | Когда | Описание |
|---|---|---|
| **Spawn** | После тапа по генератору | Предмет появляется в позиции генератора (scale 0→1.25→1), перемещается к целевой клетке, частицы мятного цвета |
| **Merge** | При объединении двух предметов | Оба предмета сжимаются (scale→0, alpha→0) к точке слияния, белая вспышка (круг scale 0→1.5, fade out), золотые частицы |
| **Chain Combo** | При chain merge (2+ в цепи) | Текст «x2!», «x3!» всплывает и гаснет над точкой слияния |
| **Particles** | Spawn / Merge | 6 частиц разлетаются по кругу с затуханием |
| **Shake** | Нет энергии при тапе | Генератор дрожит 3 раза (±4px) |

Все анимации реализованы через `animateTween()` — универсальную функцию твининга на основе `app.ticker`, поддерживающую x, y, scaleX, scaleY, alpha с произвольным easing (`easeOutBack`, `easeOutQuad`, `easeInQuad`).

#### Интеграция Canvas ↔ React

- `GameContext` (React context) хранит `items`, `generators`, `energy` и предоставляет `refreshState()`, `setEnergy()`.
- `GameField` подписывается на контекст через `useGame()`, обновляя PixiJS-спрайты при каждом изменении стейта.
- Для обратной связи Canvas → React используются custom DOM events: `no-energy` (открывает модал пополнения), `character-line` (показывает речевой пузырь).
- Haptic feedback через `usePlatform().hapticFeedback()` при merge и ошибках.

### 8.5 Admin Panel — CRUD для наборов и предметов

Реализован полный CRUD в админ-панели (Blade + Laravel):

#### Тематики (`/admin/themes`)

- **Список**: таблица с названием, slug, типом генератора, уровнем разблокировки, количеством предметов, настройками генератора.
- **Создание / Редактирование**: форма с полями: имя, slug, тип генератора (`chargeable` / `cooldown`), название генератора, уровень разблокировки, стоимость энергии, лимит зарядов, таймаут перезарядки, флаг активности.
- **Удаление**: каскадное удаление item_definitions, очистка загруженных изображений.

#### Предметы (`/admin/themes/{theme}/items`)

- **Список**: таблица с уровнем, названием, slug, превью изображения, визуализация merge-цепочки.
- **Создание / Редактирование**: форма с полями: уровень, имя, slug (авто-генерация), загрузка изображения (`image/png,jpg,svg,webp`, до 2MB) или внешний URL, удаление текущего изображения.
- **Синхронизация `chain_config`**: при любом изменении предметов автоматически перестраивается JSON `chain_config` в таблице `themes`.

### 8.6 Seeder — начальные данные

`ItemDefinitionSeeder` заполняет 3 тематических набора по 10 предметов:

| Тематика | Генератор | Иконки |
|---|---|---|
| **Кофейня** (`coffee`) | Кофемашина (chargeable) | Noto / Twemoji (Iconify API) |
| **Ткани** (`fabrics`) | Швейная машинка (chargeable) | Noto (Iconify API) |
| **Посуда** (`pottery`) | Гончарный круг (cooldown) | Noto / Twemoji (Iconify API) |

Сидер использует `updateOrCreate` для идемпотентности. Иконки загружаются из Iconify API в формате SVG.

### 8.7 Configuration

Настройки вынесены в `config/game.php` и `.env`:

```php
'generator' => [
    'default_limit' => env('GENERATOR_DEFAULT_LIMIT', 5),       // зарядов до кулдауна
    'default_timeout' => env('GENERATOR_DEFAULT_TIMEOUT', 1800), // секунд кулдауна
    'default_energy_cost' => env('GENERATOR_ENERGY_COST', 1),   // энергии за тап
],
'energy' => [
    'max' => env('ENERGY_MAX', 50),
    'recovery_minutes' => env('ENERGY_RECOVERY_MINUTES', 3),
    'merge_cost' => env('MERGE_ENERGY_COST', 1),
    'ad_refill' => env('ENERGY_AD_REFILL', 10),
],
'grid' => ['width' => 6, 'height' => 8],
```

---

## 9. Database Schema (Key Tables)

### Core (MVP)

| Table | Description |
|---|---|
| `users` | Player profile + platform source |
| `generators` | Generator instances per user (type, level, charges, cooldown, grid position) |
| `items` | Items currently on the field (theme, level, grid position) |
| `themes` | Theme definitions (name, unlock_level, chain config, generator defaults) |
| `item_definitions` | Normalized item catalog: theme × level → name, slug, image_url |
| `orders` | Active orders per user (character, required items, rewards) |
| `characters` | Character definitions (name, theme_id, personality) |
| `character_lines` | Line database (character, trigger, conditions JSON, text, priority) |
| `character_line_shows` | Track shown lines per user (for max_shows / cooldown) |
| `character_relationships` | User ↔ character relationship level (orders completed count) |

### Economy & Progress

| Table | Description |
|---|---|
| `energy_logs` | Energy transactions (spent, recovered, rewarded) — source: `merge` / `generator` / `ad` / bonus |
| `chests` | User chests (type, unlock_at, contents JSON) |
| `decor_locations` | Location definitions (name, unlock_level) |
| `decor_items` | Placed decorations per user per location |
| `transactions` | Generic currency log (coins, experience) |
| `streaks` | Daily login streak tracking |

### Events & Social

| Table | Description |
|---|---|
| `events` | Event definitions (type, dates, config JSON) |
| `event_progress` | User progress in events |
| `daily_challenges` | Challenge definitions + user completion |
| `referrals` | Referral tracking |
| `ad_views` | Ad view log (format, timestamp, rewarded flag) |
| `notifications` | Notification queue (user, channel, message, status) |

---

## 10. Deployment Architecture

```
Production Server
├── Nginx          — SSL termination, routing to PHP-FPM or static files
├── PHP-FPM        — runs Laravel application
├── Queue Worker   — processes async jobs (notifications, heavy calculations)
├── Scheduler      — cron-based, runs periodic artisan commands
├── MySQL 8        — primary data store
└── Redis          — cache, sessions, queue backend
```

Frontend is built via `npm run build` and served as static files from `frontend/dist/`.

---

## 11. SPA Fallback

In `routes/web.php`, the fallback route serves the React SPA for all non-API, non-admin routes:

```php
Route::fallback(function () {
    if (request()->is('admin') || request()->is('admin/*') || request()->is('api/*')) {
        abort(404);
    }
    $frontendPath = public_path('../frontend/dist/index.html');
    if (file_exists($frontendPath)) {
        return response()->file($frontendPath);
    }
    return view('welcome');
});
```

---

## 12. Security

- **Messenger auth** — MiniApp `initData` validated with HMAC (Telegram WebApp vs MAX secret scheme).
- **Telegram browser auth** — Login Widget payload validated with Telegram’s widget HMAC (SHA256(bot_token) as key); short-lived `auth_date` (24h) enforced server-side. Stored client-side only for attaching parameters to API calls; treat as a session credential, not a long-lived secret.
- **MAX browser** — not implemented; when added, should follow the same pattern: server-validated, short-lived proof bound to `source = max`.
- **Admin panel** — simple login/password from `.env` (`AdminAuth` middleware)
- **Server-side validation** for all merge operations (anti-cheat)
- **Rate limiting** on API endpoints (Laravel built-in throttle)
- **CORS** configured for both domains (`tg.richmn.com`, `max.richmn.com`)
- **CSRF** disabled for `/api/*` routes (stateless, header-based auth)
