# Titan Suite Templates
**10 Pre-configured Vertical Apps for MagicAI Chatbot Extension**

## Overview
These templates add 10 complete vertical business applications to the existing Chatbot Extension. Each app is a fully configured chatbot instance with:
- Mobile-optimized UI following your design system
- Dark theme with gradient accents
- 5-step wizard configuration
- Embedded chatbot with role-specific prompts
- Offline-first capabilities
- Real-time sync

## Apps Included

### 1. **Titan Go** 🚀 (Cyan)
Field technician work app
- Job assignment & navigation
- Photo capture & signatures
- Work checklists & time tracking
- GPS location tracking
- Offline job completion

### 2. **Titan Hub** 🏠 (Green)
Customer service & booking
- Chat support
- Service booking
- Quote calculator
- Invoice viewing
- Payment processing
- Review & rating

### 3. **Titan Dispatch** 📍 (Amber)
Dispatch & scheduling
- Map view with live technician tracking
- Unassigned job queue
- Optimal tech assignment
- Route optimization
- Real-time alerts

### 4. **Titan Money** 💰 (Purple)
Financial & invoicing
- Invoice generation
- Payment processing
- Receivables tracking
- Expense management
- Financial reporting

### 5. **Titan Teams** 👥 (Pink)
Team & HR management
- Employee roster
- Shift scheduling
- Certification tracking
- Time clock
- Performance management

### 6. **Titan Locker** 🏗 (Indigo)
Inventory & operations
- Stock level tracking
- Reorder management
- Equipment monitoring
- Maintenance scheduling
- Par level alerts

### 7. **Titan Analytics** 📊 (Cyan)
Analytics & business intelligence
- Real-time KPI dashboard
- Revenue trends
- Technician productivity
- Custom reports
- Forecasting

### 8. **Titan Front Desk** ☎️ (Orange)
Phone answering & reception
- Call handling interface
- Queued call management
- Appointment scheduling
- Message taking
- Routing to staff

### 9. **Titan Marketing** 📢 (Teal)
Multi-channel campaigns
- WhatsApp campaigns
- Email campaigns
- SMS campaigns
- Telegram campaigns
- Campaign analytics

### 10. **Titan Social** 📱 (Blue)
Social media management
- Multi-platform posting
- Content scheduling
- Engagement tracking
- Analytics dashboard
- Caption suggestions

## Installation

### Step 1: Copy Templates to Extension
```bash
# From your MagicAI Chatbot Extension directory:
cp -r titan-suite-templates/ resources/titan-apps/
```

### Step 2: Register in Extension Service Provider
Add to `app/Providers/ChatbotServiceProvider.php`:

```php
public function boot()
{
    // Load Titan app templates
    $this->loadTitanApps();
}

private function loadTitanApps()
{
    $templatesPath = resource_path('titan-apps/titan-suite-templates');
    
    foreach (glob($templatesPath . '/titan-*/config.json') as $configFile) {
        $config = json_decode(file_get_contents($configFile), true);
        TitanRegistry::register($config);
    }
}
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Publish Assets
```bash
php artisan vendor:publish --tag=titan-assets
```

## Using a Template

### Create Titan Go Instance
```php
// In your controller
$chatbot = TitanFactory::create('titan-go', [
    'company_id' => $companyId,
    'name' => 'Cleaning Co Field App',
    'description' => 'Technician work management',
]);

return $chatbot->getInstallUrl(); // Returns PWA install link
```

### Customize Template
Each template's `config.json` can be extended:

```json
{
  "name": "Titan Go - Custom Branding",
  "chatbot": {
    "suggested_prompts": [
      "Custom prompt 1",
      "Custom prompt 2"
    ]
  },
  "theme": {
    "primary": "#custom-color"
  }
}
```

## File Structure

```
titan-suite-templates/
├── titan-go/
│   ├── config.json           (App configuration)
│   ├── JobList.vue           (Dashboard component)
│   └── chatbot.ts            (Chatbot prompts)
├── titan-hub/
│   ├── config.json
│   └── CustomerDashboard.vue
├── [8 more apps...]
├── components/
│   ├── TitanWizard.vue       (5-step wizard template)
│   ├── ChatbotWidget.vue     (Embedded chatbot)
│   └── TitanHeader.vue       (App header)
├── styles/
│   ├── theme.scss            (Design system)
│   └── colors.scss           (App color palette)
├── README.md
└── INSTALLATION.md
```

## API Endpoints (Per App)

Each app has dedicated API routes:

```
GET  /api/v2/titan-go/jobs/today
POST /api/v2/titan-go/job/{id}/complete

GET  /api/v2/titan-hub/services/upcoming
POST /api/v2/titan-hub/service/book

GET  /api/v2/titan-dispatch/technicians/live
POST /api/v2/titan-dispatch/assign

[And 7 more apps...]
```

All routes use the same authentication as the original Chatbot Extension.

## Database Schema

Each app has dedicated tables (created by migrations):
- `titan_go_jobs`
- `titan_go_photos`
- `titan_hub_bookings`
- `titan_hub_invoices`
- [etc...]

All tables share a common `company_id` for multi-tenancy.

## Chatbot Integration

Every app has an embedded chatbot with:
- **Role-specific system prompt** - Trained for the vertical
- **Suggested prompts** - Context-aware quick actions
- **Conversation context** - Job ID, customer name, etc.
- **Offline capability** - Messages queue locally, sync when online

Example: Titan Go chatbot
```
System Prompt: "You are a field technician assistant..."
Suggested: ["How do I handle this issue?", "Need extra time?"]
Context: { job_id, customer_name, service_type }
```

## Features Per App

### Offline Capabilities
All apps work 100% offline:
- Messages queue locally
- Photos stored locally (sync when online)
- All data cached IndexedDB
- Background sync on reconnect

### Real-Time Sync
When online:
- Real-time location updates (Dispatch)
- Live technician status (Teams, Dispatch)
- Payment confirmations (Money, Hub)
- Analytics updates (Analytics)

### Permissions Required
Each app declares needed permissions:
```json
{
  "permissions": {
    "geolocation": true,      // Titan Go, Dispatch
    "camera": true,            // Titan Go, Hub
    "notification": true,      // All apps
    "background_sync": true   // All apps
  }
}
```

## Customization

### Change App Theme
Edit `config.json`:
```json
{
  "theme": {
    "primary": "#your-color",
    "gradient": "linear-gradient(...)"
  }
}
```

### Add Custom Prompts
Edit chatbot config:
```json
{
  "chatbot": {
    "suggested_prompts": [
      "Your custom prompt 1",
      "Your custom prompt 2"
    ]
  }
}
```

### Add Features
Add to `features` array:
```json
{
  "features": [
    "existing-feature",
    "new-custom-feature"
  ]
}
```

## Testing

### Local Development
```bash
# Start dev server with template loading
npm run dev:titan

# Test specific app
npm run dev:titan -- --app=titan-go

# Run E2E tests
npm run test:e2e -- --template=titan-go
```

### Build & Deploy
```bash
# Build all templates
npm run build:titan

# Deploy to production
npm run deploy:titan

# Verify installation
curl https://your-domain.com/api/v2/titan-apps/list
```

## Troubleshooting

### Template Not Loading
1. Check `resources/titan-apps/` directory exists
2. Verify `config.json` syntax is valid JSON
3. Check Laravel logs: `storage/logs/laravel.log`
4. Run: `php artisan config:cache`

### Chatbot Not Responding
1. Verify chatbot extension is enabled
2. Check API routes are registered
3. Verify JWT token is valid
4. Check OpenAI/LLM provider credentials

### Offline Sync Issues
1. Check IndexedDB quota (DevTools → Application)
2. Verify Service Worker is registered
3. Check browser support (Chrome 40+, Firefox 44+)
4. Clear cache & reload

## Support & Documentation

- **Extension Docs**: See original Chatbot Extension README
- **API Reference**: `/docs/api/v2`
- **Component Library**: Check `/resources/titan-apps/components/`
- **Issue Tracker**: GitHub Issues

---

**Built on:** MagicAI Chatbot Extension v6.4  
**Compatibility:** Laravel 10+, PHP 8.1+  
**License:** Same as original extension
