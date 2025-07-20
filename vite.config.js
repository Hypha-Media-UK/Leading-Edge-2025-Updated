import { defineConfig } from 'vite';
import { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  
  build: {
    outDir: 'web/_dist',
    rollupOptions: {
      input: {
        'css/styles': resolve(__dirname, 'src/css/styles.css'),
        'js/app': resolve(__dirname, 'src/js/app.js')
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name].[ext]'
      }
    }
  },
  
  css: {
    postcss: {
      plugins: [
        require('postcss-import'),
        require('postcss-nesting'),
        require('autoprefixer')
      ]
    }
  },
  
  server: {
    port: 3000,
    proxy: {
      '^(?!/_dist/).*': 'https://tle-2025.ddev.site'
    }
  },
  
  base: '/_dist/'
});
