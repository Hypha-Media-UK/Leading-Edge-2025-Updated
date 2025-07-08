# Leading Edge 2025 - Modern CSS Build Pipeline

This project uses a modern PostCSS build pipeline to process CSS files, replacing CodeKit for faster and more efficient builds.

## Setup

### 1. Install Dependencies
```bash
npm install
```

### 2. Build Commands

#### Development (with watch mode)
```bash
npm run dev
# or
npm run css:watch
```
This will:
- Watch for changes in `src/css/` files
- Automatically rebuild when files change
- Output to `web/_dist/css/`
- Include source maps for debugging

#### Production Build
```bash
npm run build
# or
npm run css:build
```
This will:
- Process all CSS files
- Add vendor prefixes
- Minify and optimize
- Output to `web/_dist/css/`

#### Development Build (one-time)
```bash
npm run css:dev
```
Same as production but includes source maps.

## CSS Architecture

### Source Files (`src/css/`)
- `variables.css` - CSS custom properties and design tokens
- `utilities.css` - Utility classes and layout helpers
- `main.css` - Global styles and base CSS
- `components/` - Individual component styles
- `*.css` - Page-specific styles (home.css, careers.css, etc.)

### Output Files (`web/_dist/css/`)
- Processed, optimized, and minified CSS files
- Vendor prefixes automatically added
- Modern CSS features transformed for browser compatibility

## Modern CSS Features Used

- **Native CSS Nesting** - Familiar SCSS-like syntax
- **CSS Custom Properties** - Runtime-changeable variables
- **CSS Layers** - Better organization and specificity control
- **Logical Properties** - Better internationalization support
- **Advanced Color Functions** - color-mix() for dynamic colors

## Browser Support

Configured to support:
- Modern browsers (> 1% usage)
- Last 2 versions of major browsers
- Excludes IE 11 and dead browsers

## Performance Benefits

- **~10x faster** than CodeKit
- **Instant rebuilds** in watch mode
- **Optimized output** with automatic minification
- **Vendor prefixes** added automatically
- **Source maps** for development debugging

## Development Workflow

1. Start watch mode: `npm run dev`
2. Edit CSS files in `src/css/`
3. Changes automatically compile to `web/_dist/css/`
4. Refresh browser to see changes

## Production Deployment

1. Run: `npm run build`
2. Deploy the `web/_dist/css/` files
3. CSS is optimized and ready for production

## Troubleshooting

### Build Errors
- Check that all `@import` paths are correct
- Ensure CSS syntax is valid
- Check PostCSS plugin compatibility

### Performance Issues
- Use `npm run css:dev` for development (includes source maps)
- Use `npm run css:build` for production (optimized)

### Browser Compatibility
- Modern CSS features are automatically transformed
- Check `browserslist` in package.json for target browsers
- Add fallbacks manually if needed for specific features
