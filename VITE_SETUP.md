# Vite Build System Setup

This project now uses Vite as the build system, replacing the previous PostCSS-only setup. Vite provides faster builds, hot module replacement, and handles both CSS and JavaScript processing.

## Available Scripts

- `npm run dev` - Start development server with hot reload
- `npm run build` - Build for production
- `npm run preview` - Preview production build locally
- `npm run watch` - Build and watch for changes

## What's Included

### Performance Optimizations
- **Throttled scroll events** using `requestAnimationFrame`
- **Modern CSS scroll-driven animations** with Intersection Observer fallback
- **GPU acceleration** for interactive elements
- **CSS containment** for better paint performance
- **Mobile-specific optimizations** for touch scrolling
- **Accessibility support** for users who prefer reduced motion
- **No external animation libraries** - pure CSS + native APIs

### Build Features
- **CSS Processing**: PostCSS with import, nesting, autoprefixer, and minification
- **JavaScript Processing**: Modern ES modules with legacy browser support
- **Minification**: Both CSS and JS are minified for production
- **Source Maps**: Available in development mode
- **Hot Module Replacement**: Instant updates during development

## File Structure

```
src/
├── css/           # CSS source files
│   ├── main.css   # Main stylesheet with imports
│   ├── performance.css  # Performance utility classes
│   └── components/      # Component-specific styles
└── js/            # JavaScript source files
    ├── main.js    # Main JavaScript with scroll optimizations
    └── *.js       # Page-specific JavaScript

web/_dist/         # Built files (auto-generated)
├── css/           # Processed CSS files
└── js/            # Processed JavaScript files
```

## Performance Improvements Made

### JavaScript Optimizations
1. **Scroll Event Throttling**: Reduced scroll event frequency using `requestAnimationFrame`
2. **GSAP Animation Batching**: Combined similar animations to reduce ScrollTrigger instances
3. **Reduced Motion Support**: Respects user's motion preferences
4. **Optimized Animation Settings**: Faster, more efficient animations

### CSS Optimizations
1. **GPU Acceleration**: Added `transform: translateZ(0)` and `will-change` properties
2. **Removed Backdrop Filter**: Replaced expensive backdrop-filter with solid background
3. **Performance Utility Classes**: Added classes for scroll optimization
4. **CSS Containment**: Improved paint and layout performance

## Development Workflow

1. **Development**: Run `npm run dev` for hot reload development server
2. **Building**: Run `npm run build` to create production files
3. **Watching**: Run `npm run watch` for continuous building during development

## Browser Support

- Modern browsers (ES2015+)
- Legacy browser support via Babel transforms
- Autoprefixer for CSS vendor prefixes

## Migration Notes

- Removed all PostCSS-specific configuration
- Replaced PostCSS CLI scripts with Vite commands
- Maintained existing file structure and output paths
- **Updated script tags in templates**: Added `type="module"` to JavaScript imports
- All performance optimizations are now active

## Important: Script Tag Requirements

When using Vite-built JavaScript files, make sure your HTML templates load them as ES modules:

```html
<!-- Correct -->
<script type="module" src="/_dist/js/main.js"></script>

<!-- Incorrect - will cause import.meta syntax errors -->
<script src="/_dist/js/main.js"></script>
```
