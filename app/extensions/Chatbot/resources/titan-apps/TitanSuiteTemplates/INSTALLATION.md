# Titan Suite Installation Guide

## Quick Start (5 minutes)

### 1. Extract Templates
```bash
cd /path/to/magicai-chatbot-extension
unzip titan-suite-templates.zip
cp -r titan-suite-templates/ resources/
```

### 2. Update Service Provider
Edit `app/Providers/ExtensionServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Extensions\Chatbot\Titan\TitanRegistry;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register Titan apps
        TitanRegistry::init();
    }

    public function boot()
    {
        // Publish Titan assets
        $this->publishes([
            resource_path('titan-apps') => public_path('titan-apps'),
        ], 'titan-assets');
    }
}
```

### 3. Add Routes
Create `routes/titan.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TitanController;
use App\Extensions\Chatbot\Titan\TitanRegistry;

Route::middleware(['auth', 'throttle:60,1'])->prefix('api/v2/titan')->group(function () {
    // List all apps
    Route::get('apps', function () {
        return response()->json(TitanRegistry::toArray());
    });

    // Install app
    Route::post('install/{app}', [TitanController::class, 'install']);

    // Get manifest
    Route::get('{app}/manifest.json', [TitanController::class, 'manifest']);

    // Titan Go routes
    Route::prefix('go')->group(function () {
        Route::get('jobs/today', 'TitanGoController@today');
        Route::post('job/{id}/complete', 'TitanGoController@complete');
        Route::post('location/update', 'TitanGoController@updateLocation');
        Route::post('clock-toggle', 'TitanGoController@clockToggle');
    });

    // Titan Hub routes
    Route::prefix('hub')->group(function () {
        Route::get('services/upcoming', 'TitanHubController@upcoming');
        Route::post('service/book', 'TitanHubController@book');
        Route::get('invoices', 'TitanHubController@invoices');
        Route::post('payment/process', 'TitanHubController@payment');
    });

    // [Add routes for 8 more apps...]
});

// Include in routes/api.php:
// Route::include('routes/titan.php');
```

### 4. Run Migrations (if needed)
```bash
php artisan migrate
```

### 5. Publish Assets
```bash
php artisan vendor:publish --tag=titan-assets
```

### 6. Test Installation
```bash
curl http://localhost:8000/api/v2/titan/apps
```

Should return:
```json
[
  {
    "slug": "titan-go",
    "name": "Titan Go",
    "type": "technician-work",
    "icon": "🚀",
    "color": "#00d4ff",
    "features": [...]
  },
  ...
]
```

---

## Detailed Setup

### Update Laravel Config

Add to `config/app.php`:

```php
'extensions' => [
    'chatbot' => [
        'enabled' => true,
        'titan' => [
            'templates_path' => resource_path('titan-apps/titan-suite-templates'),
            'features' => [
                'offline_sync' => true,
                'chatbot_embedding' => true,
                'realtime_updates' => true,
            ],
        ],
    ],
],
```

### Create Controller

Create `app/Http/Controllers/TitanController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Extensions\Chatbot\Titan\TitanRegistry;
use Illuminate\Http\Request;

class TitanController extends Controller
{
    /**
     * List all Titan apps
     */
    public function list()
    {
        return response()->json(TitanRegistry::toArray());
    }

    /**
     * Get app details
     */
    public function show($slug)
    {
        $app = TitanRegistry::get($slug);
        
        if (!$app) {
            return response()->json(['error' => 'App not found'], 404);
        }

        return response()->json($app);
    }

    /**
     * Install app for company
     */
    public function install(Request $request, $app)
    {
        $template = TitanRegistry::get($app);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $company = auth()->user()->company;

        // Create app instance
        $appInstance = $company->titanApps()->create([
            'template' => $app,
            'name' => $request->input('name', $template['name']),
            'config' => $request->input('config', []),
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $appInstance->id,
            'slug' => $app,
            'url' => route('titan.app', ['app' => $app, 'id' => $appInstance->id]),
        ]);
    }

    /**
     * Get PWA manifest
     */
    public function manifest($app)
    {
        $titanApp = TitanRegistry::create($app);
        
        if (!$titanApp) {
            return response()->json(['error' => 'App not found'], 404);
        }

        return response()->json($titanApp->manifest());
    }
}
```

### Create Models (if using database)

```php
php artisan make:model TitanApp -m
```

Migration (`database/migrations/create_titan_apps_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('titan_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('template'); // titan-go, titan-hub, etc
            $table->string('name');
            $table->json('config')->nullable();
            $table->string('status')->default('active');
            $table->string('uuid')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('titan_apps');
    }
};
```

### Environment Variables

Add to `.env`:

```env
TITAN_ENABLE=true
TITAN_OFFLINE_MODE=true
TITAN_REALTIME_SYNC=true
TITAN_DEFAULT_COLOR=#00d4ff
```

---

## Verification Checklist

- [ ] Templates extracted to `resources/titan-apps/`
- [ ] Service Provider registered
- [ ] Routes included in `routes/api.php`
- [ ] Database migrations run (if using DB)
- [ ] Assets published with `vendor:publish`
- [ ] `.env` variables configured
- [ ] Cache cleared: `php artisan config:cache`
- [ ] `/api/v2/titan/apps` endpoint returns 200
- [ ] Browser can access `/public/titan-apps/`

---

## Usage Examples

### JavaScript/Vue
```javascript
// Fetch available apps
const apps = await fetch('/api/v2/titan/apps').then(r => r.json());

// Install an app
const installed = await fetch('/api/v2/titan/install/titan-go', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'My Field App' })
}).then(r => r.json());

// Open app
window.location.href = installed.url;
```

### PHP/Laravel
```php
use App\Extensions\Chatbot\Titan\TitanRegistry;

// Get all apps
$apps = TitanRegistry::all();

// Get specific app
$goApp = TitanRegistry::get('titan-go');

// Create instance
$instance = TitanRegistry::create('titan-hub', [
    'company_id' => $companyId,
]);

// Get chatbot config
$chatbot = TitanRegistry::getChatbot('titan-go');
```

---

## Troubleshooting

### "Template not found" error
```
Solution:
1. Check resources/titan-apps/titan-suite-templates/ exists
2. Verify all config.json files are valid JSON
3. Run: php artisan config:clear && php artisan config:cache
```

### Routes not working
```
Solution:
1. Verify routes/titan.php is included in routes/api.php
2. Check middleware is applied correctly
3. Run: php artisan route:list | grep titan
```

### Manifest not loading
```
Solution:
1. Check TitanController is defined
2. Verify manifest route is registered
3. Check browser console for 404 errors
4. Run: php artisan route:clear
```

### Offline sync not working
```
Solution:
1. Check Service Worker is registered
2. Verify IndexedDB quota (DevTools → Application)
3. Check browser console for SW errors
4. Test in private/incognito window
```

---

## Support

- **Documentation**: See README.md
- **API Reference**: /docs/api/v2/titan
- **Issues**: File on GitHub
- **Community**: Discord channel #titan-suite

---

## Next Steps

1. **Customize** - Edit template configs to match your branding
2. **Deploy** - Build and release your first vertical app
3. **Monitor** - Track usage in Analytics app
4. **Iterate** - Add features based on user feedback
5. **Scale** - Roll out to more verticals

---

**Installation Complete!** 🎉

Your Titan Suite is ready to deploy.
