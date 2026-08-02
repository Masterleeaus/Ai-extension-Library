# Titan Suite templates integration

Integrated into the Chatbot extension:

- 10 Titan app templates under `resources/titan-apps/TitanSuiteTemplates/`
- `System/Titan/TitanRegistry.php`
- authenticated API routes under `/api/v2/titan`
- public read-only catalogue at `/api/v2/titan/public/apps`
- `titan-assets` publish tag
- first Configure panel app selector that applies name, system prompt, prompts and accent colour

## Commands

```bash
php artisan vendor:publish --tag=titan-assets
php artisan route:clear
php artisan config:clear
```

Authenticated test:

```bash
curl -H "Accept: application/json" http://localhost:8000/api/v2/titan/apps
```

Public catalogue test:

```bash
curl http://localhost:8000/api/v2/titan/public/apps
```
