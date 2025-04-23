import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  plugins: [
    vue(),
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, '.')
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        // If you have global variables
      }
    }
  },
  build: {
    outDir: 'dist',
    // Enable source maps for easier debugging
    sourcemap: true,
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src/js/admin/admin.js'),
        frontend: resolve(__dirname, 'src/js/frontend/frontend.js')
      },
      output: {
        format: 'es',
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name].[ext]'
      }
    }
  }
});