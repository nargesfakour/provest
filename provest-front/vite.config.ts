import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://provest-api.test',
        changeOrigin: true,
        secure: false,        // trust Herd's self-signed cert on the server side
        configure: (proxy) => {
          proxy.on('error', (err) => console.error('[proxy error]', err.message))
        },
      },
    },
  },
})
