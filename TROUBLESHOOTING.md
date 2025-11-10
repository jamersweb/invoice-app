# Troubleshooting Blank Page Issue

## Issue: Blank Page After Loading

If you're seeing a blank page, follow these steps:

### Step 1: Check if Vite Dev Server is Running

Since `APP_ENV=local`, you need the Vite dev server running:

```bash
cd web
npm run dev
```

This will start Vite on `http://localhost:5173` (or similar port). Keep this terminal window open.

### Step 2: Verify Laravel is Serving Correctly

Make sure your Laravel app is accessible. Check:
- Apache/XAMPP is running
- Database connection is working
- `.env` has correct settings

### Step 3: Check Browser Console

Open browser DevTools (F12) and check:
- **Console tab**: Look for JavaScript errors
- **Network tab**: Check if `app.ts` and other assets are loading (200 status)
- **Sources tab**: Check if files are loading from Vite dev server

### Step 4: Alternative - Build for Production

If you want to run without dev server:

```bash
cd web

# Fix Vite version compatibility first
npm install vite@^6.4.1 --save-dev --legacy-peer-deps

# Then build
npm run build -- --mode production
```

### Step 5: Common Issues

**Issue**: `GET http://localhost:5173/@vite/client net::ERR_CONNECTION_REFUSED`
- **Fix**: Start Vite dev server (`npm run dev`)

**Issue**: `Unable to locate file in Vite manifest`
- **Fix**: Rebuild assets (`npm run build`) OR start dev server

**Issue**: `$t is not defined` errors
- **Fix**: Already handled in `app.ts`, but rebuild assets

**Issue**: Components not found
- **Fix**: Check that `AppHeader.vue` and `AppFooter.vue` exist in `resources/js/Components/`

### Quick Debug Steps

1. **Check if dev server is running**:
   ```bash
   netstat -ano | findstr :5173
   ```

2. **Clear all caches**:
   ```bash
   php artisan view:clear
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Test route directly**:
   Visit: `http://localhost/invoice-app/web/` (adjust path to your setup)

### Expected Behavior

When working correctly:
- Home page should show header, hero section, features
- No errors in browser console
- Assets loading from Vite dev server (check Network tab)
- Inertia should mount the Vue app

### Still Blank?

1. Check browser console for specific errors
2. Verify `resources/js/app.ts` has no syntax errors
3. Ensure Inertia middleware is applied
4. Check that `@inertia` directive is in `app.blade.php`

If you see specific error messages, share them for further debugging.









