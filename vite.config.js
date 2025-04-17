import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, '.'),
    }
  },
  build: {
    outDir: 'assets/dist',
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'assets/js/vue/admin.js'),
        frontend: resolve(__dirname, 'assets/js/vue/frontend.js')
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name].[ext]'
      }
    }
  }
});