import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'com.storeyar.app',
  appName: 'StoreYar',
  webDir: '.output/public',
  server: {
    androidScheme: 'https',
  },
}

export default config
