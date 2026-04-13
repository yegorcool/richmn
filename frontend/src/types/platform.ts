export type Platform = 'telegram' | 'max';

export interface ThemeParams {
  bgColor?: string;
  textColor?: string;
  hintColor?: string;
  linkColor?: string;
  buttonColor?: string;
  buttonTextColor?: string;
  secondaryBgColor?: string;
}

export interface PlatformAPI {
  platform: Platform;
  initData: string;
  hapticFeedback: (type: 'impact' | 'notification' | 'selection') => void;
  expand: () => void;
  close: () => void;
  themeParams: ThemeParams;
}
