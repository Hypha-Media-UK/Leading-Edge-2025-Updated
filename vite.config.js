import { defineConfig } from 'vite';
import { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  
  // Define entry points for CSS and JS
  build: {
    outDir: 'web/_dist',
    emptyOutDir: false, // Don't clear the entire directory
    rollupOptions: {
      input: {
        // CSS files
        'css/main': resolve(__dirname, 'src/css/main.css'),
        'css/home': resolve(__dirname, 'src/css/home.css'),
        'css/careers': resolve(__dirname, 'src/css/careers.css'),
        'css/contact': resolve(__dirname, 'src/css/contact.css'),
        'css/index': resolve(__dirname, 'src/css/index.css'),
        'css/news': resolve(__dirname, 'src/css/news.css'),
        'css/offers': resolve(__dirname, 'src/css/offers.css'),
        'css/salon': resolve(__dirname, 'src/css/salon.css'),
        'css/services': resolve(__dirname, 'src/css/services.css'),
        'css/team': resolve(__dirname, 'src/css/team.css'),
        'css/utilities': resolve(__dirname, 'src/css/utilities.css'),
        'css/variables': resolve(__dirname, 'src/css/variables.css'),
        
        // JavaScript files
        'js/main': resolve(__dirname, 'src/js/main.js'),
        'js/home-js': resolve(__dirname, 'src/js/home.js'),
        'js/careers-js': resolve(__dirname, 'src/js/careers.js'),
        'js/contact-js': resolve(__dirname, 'src/js/contact.js'),
        'js/news-js': resolve(__dirname, 'src/js/news.js'),
        'js/offers-js': resolve(__dirname, 'src/js/offers.js'),
        'js/salon-js': resolve(__dirname, 'src/js/salon.js'),
        'js/services-js': resolve(__dirname, 'src/js/services.js'),
        'js/team-js': resolve(__dirname, 'src/js/team.js')
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return '[name].css';
          }
          return '[name].[ext]';
        }
      }
    },
    
    // Minification settings for production only
    minify: process.env.NODE_ENV === 'production' ? 'terser' : false,
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    },
    
    // CSS settings
    cssMinify: process.env.NODE_ENV === 'production'
  },
  
  // PostCSS configuration
  css: {
    postcss: {
      plugins: [
        require('postcss-import'),
        require('postcss-nesting'),
        require('autoprefixer'),
        ...(process.env.NODE_ENV === 'production' ? [require('cssnano')({ preset: 'default' })] : [])
      ]
    }
  },
  
  // Development server settings
  server: {
    host: '0.0.0.0',
    port: 3000,
    open: false,
    cors: true,
    https: false,
    hmr: {
      port: 3000,
      host: 'localhost'
    },
    proxy: {
      // Proxy all requests except /_dist/ to DDEV site
      '^(?!/_dist/).*': {
        target: 'https://tle-2025.ddev.site',
        changeOrigin: true,
        secure: false,
        ws: true,
        configure: (proxy, options) => {
          proxy.on('error', (err, req, res) => {
            console.log('Proxy error:', err);
          });
          proxy.on('proxyReq', (proxyReq, req, res) => {
            proxyReq.setHeader('Host', 'tle-2025.ddev.site');
          });
        }
      }
    }
  },
  
  // Base path for assets
  base: '/_dist/'
});
