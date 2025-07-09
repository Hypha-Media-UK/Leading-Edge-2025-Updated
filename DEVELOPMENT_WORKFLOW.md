# Development Workflow with HMR (Hot Module Replacement)

This guide explains how to use the enhanced development setup with Hot Module Replacement for your CraftCMS site.

## Prerequisites

- DDEV running with your site accessible at `https://tle-2025.ddev.site`
- Node.js and npm installed
- Dependencies installed (`npm install`)

## Development Commands

### Primary Development Command
```bash
npm run dev:hmr
```
**Use this for active development** - Provides full HMR with instant CSS updates and browser refresh.

### Alternative Commands
```bash
npm run dev          # Same as dev:hmr
npm run build:watch  # Traditional watch mode (builds files, manual refresh)
npm run build        # Production build
```

## How to Start Development

### 1. Start DDEV
```bash
ddev start
```
Verify your site is accessible at `https://tle-2025.ddev.site`

### 2. Start Vite HMR Server
```bash
npm run dev:hmr
```

### 3. Access Your Site
Open your browser to: **`http://localhost:3001`** (or whatever port Vite shows in the terminal)

⚠️ **Important**: Use the localhost port shown in the terminal, NOT your DDEV URL during development.

**Note**: The Vite dev server will show `http://localhost:3001/_dist/` in the terminal, but you should access your site at the root `http://localhost:3001` - the `/_dist/` path is just for asset serving.

## What You Get with HMR

### ✅ Instant CSS Updates
- Edit any CSS file in `src/css/`
- Changes appear in browser **without page refresh**
- Typical update time: 50-200ms

### ✅ JavaScript Hot Reload
- Edit JS files in `src/js/`
- Browser updates with state preservation
- Update time: 100-500ms

### ✅ Template Change Detection
- Edit Twig templates
- Browser automatically refreshes the page
- Full page reload: ~1-2s

### ✅ Error Overlay
- CSS/JS errors shown directly in browser
- No need to check console constantly

### ✅ Network Access
- Other devices can access via `http://YOUR_IP:3000`
- Great for mobile testing

## File Watching

The HMR server watches:
- `src/css/**/*.css` - All CSS files and components
- `src/js/**/*.js` - All JavaScript files
- `templates/**/*.twig` - All Twig templates (triggers page refresh)

## Development Workflow Examples

### Working on CSS Components
1. Start HMR: `npm run dev:hmr`
2. Open `http://localhost:3000/salon` in browser
3. Edit `src/css/components/simple-cards.css`
4. See changes instantly without refresh!

### Working on Page-Specific Styles
1. Edit `src/css/salon.css`
2. Changes appear immediately on salon page
3. No build step needed

### Working on Templates
1. Edit `templates/salon.twig`
2. Browser automatically refreshes
3. All styling remains intact

## Troubleshooting

### Port 3000 Already in Use
```bash
# Kill process using port 3000
lsof -ti:3000 | xargs kill -9

# Or use different port
npm run dev:hmr -- --port 3001
```

### DDEV Site Not Accessible
```bash
# Restart DDEV
ddev restart

# Check DDEV status
ddev describe
```

### HMR Not Working
1. Check console for WebSocket errors
2. Ensure DDEV site is accessible
3. Try restarting both DDEV and Vite server

### Proxy Errors
- Check that `https://tle-2025.ddev.site` is accessible
- Verify DDEV is running
- Check for certificate issues

## Production Build

When ready for production:
```bash
npm run build
```

This creates optimized files in `web/_dist/` for production use.

## Network Configuration

### Local Development
- Vite server: `http://localhost:3000`
- DDEV site: `https://tle-2025.ddev.site` (proxied)

### Team Development
- Share your IP: `http://YOUR_IP:3000`
- Colleagues can access your development server
- Great for design reviews and mobile testing

## Performance Tips

### Faster CSS Development
- Use browser dev tools alongside HMR
- Changes in dev tools are temporary
- Make permanent changes in source files

### Efficient Workflow
1. Keep HMR server running during development
2. Use `Cmd+S` (Mac) or `Ctrl+S` (Windows) to save and see instant updates
3. Use browser's responsive mode for mobile testing

## File Structure Reminder

```
src/
├── css/
│   ├── components/          # Component styles (simple-cards.css, etc.)
│   ├── home.css            # Page-specific styles
│   ├── salon.css           # Page-specific styles
│   └── main.css            # Base styles
├── js/
│   ├── main.js             # Base JavaScript
│   └── salon.js            # Page-specific JS
templates/
├── _components/
│   └── sections/
│       └── simple-cards.twig
└── salon.twig              # Page templates
```

## Next Steps

1. Start your development server: `npm run dev:hmr`
2. Open `http://localhost:3000`
3. Try editing `src/css/components/simple-cards.css`
4. Watch the magic happen! ✨

Happy coding! 🚀
