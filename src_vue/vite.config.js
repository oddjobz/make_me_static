import { fileURLToPath, URL } from 'node:url'
import { defineConfig, searchForWorkspaceRoot } from 'vite'
import mkcert from 'vite-plugin-mkcert'
import vue from '@vitejs/plugin-vue'
import requireTransform from 'vite-plugin-require-transform';
import basicSsl from '@vitejs/plugin-basic-ssl';
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js'

export default defineConfig({
  assetsInclude: ['**/*.png', './assets/profiles_32.png' , 'public/*'],
  plugins: [
    vue(),
    requireTransform({}),
    basicSsl(),
    cssInjectedByJsPlugin(),
    mkcert()
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      ws: true,
      port: 5173,
      overlay: false
    },
    hot: false,
    liveReload: false,
    webSocketServer: false,
    fs: {
      allow: [
        searchForWorkspaceRoot(process.cwd()),
      ]
    }
  },
  build: {
    sourcemap: true,
    manifest: false,
    chunkSizeWarningLimit: 4096,
    commonjsOptions: {
      esmExternals: true 
    },
    rollupOptions: {
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: `wp-content/plugins/mms-wp-plugin/assets/[name].[ext]`,
        manualChunks: {}
        }
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    }
  }
})
