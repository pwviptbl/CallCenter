import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref } from 'vue'

// Registra Pusher globalmente (necessário para o Echo com Reverb)
;(window as any).Pusher = Pusher

let echo: Echo | null = null

export function useEcho() {
  const connected = ref(false)

  const connect = () => {
    if (echo) return echo

    echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
      wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
    })

    echo.connector.pusher.connection.bind('connected', () => {
      connected.value = true
      console.log('[Echo] Conectado ao Reverb')
    })

    echo.connector.pusher.connection.bind('disconnected', () => {
      connected.value = false
    })

    echo.connector.pusher.connection.bind('error', (err: any) => {
      console.warn('[Echo] Erro de conexão:', err)
      connected.value = false
    })

    return echo
  }

  const disconnect = () => {
    echo?.disconnect()
    echo = null
    connected.value = false
  }

  const channel = (name: string) => {
    return connect().channel(name)
  }

  const leaveChannel = (name: string) => {
    echo?.leaveChannel(name)
  }

  return { connect, disconnect, channel, leaveChannel, connected }
}
