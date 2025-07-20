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
        // Single CSS entry point
        'css/styles': resolve(__dirname, 'src/css/styles.css'),
        
        // Single JavaScript entry point
        'js/app': resolve(__dirname, 'src/js/app.js')
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
