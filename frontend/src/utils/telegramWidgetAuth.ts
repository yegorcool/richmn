const STORAGE_KEY = 'richmn_telegram_login_widget';

export type TelegramWidgetAuthPayload = {
  id: number;
  first_name?: string;
  last_name?: string;
  username?: string;
  photo_url?: string;
  auth_date: number;
  hash: string;
};

function toQueryFields(data: TelegramWidgetAuthPayload): Record<string, string> {
  const out: Record<string, string> = {
    id: String(data.id),
    auth_date: String(data.auth_date),
    hash: data.hash,
  };
  if (data.first_name !== undefined) {
    out.first_name = data.first_name;
  }
  if (data.last_name !== undefined) {
    out.last_name = data.last_name;
  }
  if (data.username !== undefined) {
    out.username = data.username;
  }
  if (data.photo_url !== undefined) {
    out.photo_url = data.photo_url;
  }
  return out;
}

export function isTelegramWidgetAuthExpired(authDate: number): boolean {
  const now = Math.floor(Date.now() / 1000);

  return now - authDate > 86400;
}

export function saveTelegramWidgetAuth(data: TelegramWidgetAuthPayload | Record<string, unknown>): void {
  const normalized: TelegramWidgetAuthPayload = {
    id: Number((data as TelegramWidgetAuthPayload).id),
    auth_date: Number((data as TelegramWidgetAuthPayload).auth_date),
    hash: String((data as TelegramWidgetAuthPayload).hash),
  };
  const d = data as TelegramWidgetAuthPayload;
  if (d.first_name !== undefined) {
    normalized.first_name = String(d.first_name);
  }
  if (d.last_name !== undefined) {
    normalized.last_name = String(d.last_name);
  }
  if (d.username !== undefined) {
    normalized.username = String(d.username);
  }
  if (d.photo_url !== undefined) {
    normalized.photo_url = String(d.photo_url);
  }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
}

export function clearTelegramWidgetAuth(): void {
  localStorage.removeItem(STORAGE_KEY);
}

export function getValidTelegramWidgetQueryParams(): Record<string, string> | null {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    return null;
  }
  try {
    const data = JSON.parse(raw) as TelegramWidgetAuthPayload;
    const idNum = Number(data.id);
    const authNum = Number(data.auth_date);
    if (!Number.isFinite(idNum) || !data.hash || !Number.isFinite(authNum)) {
      clearTelegramWidgetAuth();
      return null;
    }
    if (isTelegramWidgetAuthExpired(authNum)) {
      clearTelegramWidgetAuth();
      return null;
    }
    return toQueryFields(data);
  } catch {
    clearTelegramWidgetAuth();
    return null;
  }
}

export function parseTelegramWidgetFromSearch(search: string): TelegramWidgetAuthPayload | null {
  const params = new URLSearchParams(search);
  const id = params.get('id');
  const hash = params.get('hash');
  const authDate = params.get('auth_date');
  if (!id || !hash || !authDate) {
    return null;
  }
  const auth_date = Number.parseInt(authDate, 10);
  if (Number.isNaN(auth_date)) {
    return null;
  }
  const payload: TelegramWidgetAuthPayload = {
    id: Number.parseInt(id, 10),
    auth_date,
    hash,
  };
  const first_name = params.get('first_name');
  const last_name = params.get('last_name');
  const username = params.get('username');
  const photo_url = params.get('photo_url');
  if (first_name) {
    payload.first_name = first_name;
  }
  if (last_name) {
    payload.last_name = last_name;
  }
  if (username) {
    payload.username = username;
  }
  if (photo_url) {
    payload.photo_url = photo_url;
  }
  if (Number.isNaN(payload.id)) {
    return null;
  }
  return payload;
}
