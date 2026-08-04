# Social Media Automation — General Setup

## Platform Documentation

Detailed setup guides for each platform:

| Platform | File | Webhook URL |
|----------|------|-------------|
| Facebook | [facebook.md](./facebook.md) | `/social-media/webhook/facebook` |
| Instagram | [instagram.md](./instagram.md) | `/social-media/webhook/instagram` |
| X (Twitter) | [x-twitter.md](./x-twitter.md) | `/social-media/automation/webhook/x` |
| TikTok | [tiktok.md](./tiktok.md) | `/social-media/automation/webhook/tiktok` |

---

## Platform Support Matrix

| Platform | Comment Capture | DM Support | Public Reply | Webhook Field |
|----------|:--------------:|:----------:|:------------:|:-------------:|
| Facebook | ✅ | ✅ | ✅ | `feed` |
| Instagram | ✅ | ✅ (Private Reply) | ✅ | `comments` |
| X (Twitter) | ✅ (reply tweet) | ✅ | ✅ | Account Activity API |
| TikTok | ✅ | ❌ API not supported | ✅ | `comment.create` |

---

## Files Changed (v1 — Initial Release)

The following files were created or modified for this feature:

| File | Change |
|------|--------|
| `app/Extensions/SocialMedia/System/Helpers/Facebook.php` | Removed deprecated `type` field from `getPageFeed()` default fields (fixed Facebook posts API 400 error) |
| `app/Extensions/SocialMedia/System/Enums/PlatformEnum.php` | Uncommented `facebook_webhook_secret`; added `webhookUrl()` method |
| `app/Extensions/SocialMedia/resources/views/setting/index.blade.php` | Webhook URI now displayed for all supported platforms |
| `app/Extensions/SocialMedia/System/Http/Controllers/Oauth/FacebookController.php` | Added `pages_manage_metadata` OAuth scope |
| `app/Extensions/SocialMediaAutomation/System/Http/Controllers/WebhookController.php` | Added `facebook()` and `instagram()` handler methods |
| `app/Extensions/SocialMediaAutomation/System/SocialMediaAutomationServiceProvider.php` | Added Facebook and Instagram webhook routes |

---

## Server Requirements

### Cron Job (Required)

A cron job **must be configured** to process pending automations. Without it, comments will be received but no DMs or replies will ever be sent.

Add the following entry to your server's crontab (`crontab -e`):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and will:
- Process pending automations (`social-media-automation:process-pending`) — every minute
- Clean up old completed/failed records (`social-media-automation:cleanup-pending`) — daily

**cPanel users:** Go to `cPanel → Cron Jobs` and add the command above with "Every Minute" frequency.

---

## Admin Panel Settings

Go to `Dashboard → Admin → Social Media Settings` and enter credentials for each platform:

### Facebook
| Field | Description |
|-------|-------------|
| `FACEBOOK_APP_ID` | Facebook Developer → Settings → Basic → App ID |
| `FACEBOOK_APP_SECRET` | Facebook Developer → Settings → Basic → App Secret |
| `FACEBOOK_WEBHOOK_SECRET` | Your chosen verify token — must match the one in Developer Console |

### Instagram
| Field | Description |
|-------|-------------|
| `INSTAGRAM_APP_ID` | Facebook Developer → Settings → Basic → App ID (same app) |
| `INSTAGRAM_APP_SECRET` | Facebook Developer → Settings → Basic → App Secret |
| `INSTAGRAM_WEBHOOK_SECRET` | Your chosen verify token — must match the one in Developer Console |

### X (Twitter)
| Field | Description |
|-------|-------------|
| `X_API_KEY` | Developer Portal → Keys and tokens → API Key |
| `X_API_SECRET` | Developer Portal → Keys and tokens → API Key Secret |
| `X_ACCESS_TOKEN` | Developer Portal → Keys and tokens → Access Token |
| `X_ACCESS_TOKEN_SECRET` | Developer Portal → Keys and tokens → Access Token Secret |
| `X_CLIENT_ID` | User authentication settings → Client ID |
| `X_CLIENT_SECRET` | User authentication settings → Client Secret |

### TikTok
| Field | Description |
|-------|-------------|
| `TIKTOK_APP_ID` | App Detail → Client Key |
| `TIKTOK_APP_KEY` | App Detail → Client Key (same value) |
| `TIKTOK_APP_SECRET` | App Detail → Client Secret |

---

## Recommended Setup Order

1. **Enter platform credentials** in the admin panel (App ID, Secret, etc.)
2. **Register and verify the webhook URL** in the platform's Developer Console
3. **Subscribe to the required events** (feed, comments, comment.create, etc.)
4. **Connect the user account** via the Connect button
5. **Verify the cron job is configured**
6. **Create an automation and set it to Live**
7. **Post a test comment** to confirm the full flow works

---

## Log Monitoring

**Real-time webhook and automation logs:**
```bash
tail -f storage/logs/laravel.log | grep -E "webhook|automation|DM|reply"
```

**View failed automations (via Tinker):**
```bash
php artisan tinker --execute="echo App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation::where('status','failed')->get();"
```

**Check pending automations count:**
```bash
php artisan tinker --execute="echo App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation::where('status','pending')->count();"
```

---

## Frequently Asked Questions

**Q: Webhook verification is failing — what should I do?**
Make sure the Webhook Secret in the admin panel and the Verify Token in the Developer Console are exactly the same — no extra spaces or case differences.

**Q: Comments are coming in but no DM or reply is sent.**
Make sure the cron job is configured: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`. If it is configured, check `storage/logs/laravel.log` for error messages and the `ext_sm_pending_automations` table for failed records.

**Q: Automation logs show "failed".**
Read the `error_message` field in the log. It is usually an API credential error or an expired token. Disconnect and reconnect the platform account.

**Q: I only want to respond to specific comments.**
Use the Keyword filter when creating the automation. You can configure it to respond only to comments containing specific words.

**Q: Can I send DMs on TikTok?**
No. TikTok does not provide DM API access to third-party apps. Only public comment replies are supported.

**Q: Do I need to set up a webhook for every user?**
No. Webhooks are registered at the app level, not per user. One webhook registration covers all users who connect their accounts.
