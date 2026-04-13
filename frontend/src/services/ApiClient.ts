import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import type { Platform } from '@/types/platform';
import { clearTelegramWidgetAuth } from '@/utils/telegramWidgetAuth';

class ApiClient {
  private client: AxiosInstance;
  private platform: Platform = 'telegram';
  private initData = '';
  private telegramWidgetParams: Record<string, string> | null = null;

  constructor() {
    this.client = axios.create({
      baseURL: '/api',
      timeout: 15000,
      headers: { 'Content-Type': 'application/json' },
    });

    this.client.interceptors.request.use((config) => {
      config.headers['X-Platform'] = this.platform;
      config.headers['X-Platform-Init-Data'] = this.initData;
      if (this.platform === 'telegram' && !this.initData && this.telegramWidgetParams) {
        config.params = { ...config.params, ...this.telegramWidgetParams };
      }
      return config;
    });

    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          console.error('Auth failed, re-initializing...');
          clearTelegramWidgetAuth();
          this.setTelegramWidgetParams(null);
          window.dispatchEvent(new Event('richmn:auth-required'));
        }
        return Promise.reject(error);
      }
    );
  }

  init(platform: Platform, initData: string) {
    this.platform = platform;
    this.initData = initData;
  }

  setTelegramWidgetParams(params: Record<string, string> | null) {
    this.telegramWidgetParams = params;
  }

  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const res = await this.client.get<T>(url, config);
    return res.data;
  }

  async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const res = await this.client.post<T>(url, data, config);
    return res.data;
  }

  async patch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const res = await this.client.patch<T>(url, data, config);
    return res.data;
  }

  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const res = await this.client.delete<T>(url, config);
    return res.data;
  }
}

export const apiClient = new ApiClient();
