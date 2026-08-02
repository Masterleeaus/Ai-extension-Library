# Facebook Automation Setup Guide

## Requirements

- Facebook Developer account → [developers.facebook.com](https://developers.facebook.com)
- **Facebook Business Page** (not a Personal profile)
- Publicly accessible domain with HTTPS (required for webhooks)
- Facebook App ID and App Secret entered in the admin panel
- Cron job must be configured (`php artisan schedule:run`)

---

## 1. Create a Facebook Developer App

1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Click **My Apps → Create App** in the top right
3. Select **Business** as the app type
4. Enter the app name and contact email
5. Click **Create App** and complete the security check

---

## 2. Add Required Products

From the App Dashboard left sidebar, click **Add Product** and add:

| Product | Purpose | How |
|---------|---------|-----|
| **Messenger** | Send DMs (private replies) | Add Product → Messenger → Set Up |
| **Webhooks** | Receive comment notifications | Add Product → Webhooks → Set Up |
| **Facebook Login** | OAuth account connection | Add Product → Facebook Login → Set Up |

---

## 3. App Credentials

Go to **Settings → Basic**:

| Field | Value | Note |
|-------|-------|------|
| App ID | Shown at the top of the page | Enter in admin panel |
| App Secret | Click **Show** | Enter in admin panel |

Enter these values at:
`Dashboard → Admin → Social Media Settings → Facebook`

---

## 4. Facebook Login — OAuth Redirect URI

Go to **Facebook Login → Settings** and add to **Valid OAuth Redirect URIs**:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/facebook
```

Click **Save Changes**.

---

## 5. Permissions

From **App Review → Permissions and Features**, add the following:

| Permission | Description | Required |
|------------|-------------|----------|
| `pages_manage_posts` | Manage page posts | ✅ Yes |
| `pages_show_list` | List pages | ✅ Yes |
| `pages_read_user_content` | Read user content | ✅ Yes |
| `pages_read_engagement` | Read engagement data | ✅ Yes |
| `pages_messaging` | Send Messenger DMs | ✅ Yes |
| `pages_manage_metadata` | Subscribe to webhooks | ✅ Yes |
| `read_insights` | Page analytics | Optional |

> **Development Mode:** App Review is not required for testing. Only accounts added as Admin/Developer to the app can use these permissions.
>
> **Production:** Each permission requires a separate App Review submission to make it available to all users.

---

## 6. Webhook Setup

### 6.1 Set a Webhook Secret

Set a verify token in the admin panel:

`Dashboard → Admin → Social Media Settings → Facebook → Facebook Webhook Secret`

> Example: `fixbiz-facebook-webhook-2024`
> Keep a note of this value — you will use it in the Developer Console.

### 6.2 Register the Webhook Callback URL

Go to Facebook Developer Console → **Webhooks**:

1. Click **Add Callback URL**
2. Enter the following:

| Field | Value |
|-------|-------|
| Callback URL | `https://experiment.liquid-themes.com/social-media/webhook/facebook` |
| Verify Token | The `Facebook Webhook Secret` value you set in the admin panel |

3. Click **Verify and Save**
4. Facebook will call this URL to validate the token automatically
5. A **"Webhooks saved"** message confirms success

> **If verification fails:** Make sure the token in the admin panel and in the Developer Console are exactly identical — no extra spaces or case differences.

### 6.3 Subscription — Which Events to Listen To

On the Webhooks page, find the **Page** object and subscribe to:

| Field | Description | Required |
|-------|-------------|----------|
| **`feed`** | Comments, likes, and post events | ✅ Yes — this is all you need |

> **Important:** Only `feed` is required. The system automatically filters incoming events and processes only comments (`item = "comment"`), ignoring everything else (likes, new posts, etc.). No other field subscriptions are needed.

---

## 7. Connect Your Facebook Account

1. Go to `Dashboard → User → Social Media`
2. Click **Connect** on the Facebook row
3. Select your Business Page in the Facebook dialog
4. Click **Allow** for all requested permissions
5. Your page name and profile picture should appear after successful connection

> **Warning:** If you previously connected the account without granting `pages_manage_metadata`, click **Disconnect** and reconnect. Without this permission, webhook subscription will not work.

---

## 8. Create an Automation

1. Go to `Dashboard → User → Social Media Automation → Create`
2. Select your **Facebook** page
3. Set the **Trigger**:
   - `All Posts` → respond to comments on any post
   - `Specific Post` → respond to comments on a selected post
   - `Next Post` → respond to comments on your next published post
4. Set **Keyword** filter (optional):
   - Trigger only when comments contain specific words (e.g., "price", "info")
   - Exclude specific words
5. Add **Actions**:
   - `Text` → Send a text message as DM
   - `Button` → Send a clickable button with a link
   - `Image` → Send an image
   - `Quick Replies` → Send predefined reply options
   - `Delay` → Wait before the next action
6. Optionally enable **Public Reply** (visible reply under the comment)
7. Set status to **Live** → Automation becomes active

---

## 9. How It Works

```
Someone comments on your post
        ↓
Facebook sends a POST request to your webhook URL
        ↓
/social-media/webhook/facebook is called
        ↓
Signature is verified (using FACEBOOK_APP_SECRET)
        ↓
Payload is parsed: entry → changes → field=feed, item=comment
        ↓
Matching Live automations are found
        ↓
Pending automation record is saved to the database
        ↓
Cron job processes the pending record (every minute)
        ↓
DM / Public Reply is sent
```

---

## 10. Testing

### Step 1: Confirm Cron Job is Configured
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Step 2: Post a Comment
Comment on a post on your Facebook page.

### Step 3: Check the Logs
```bash
tail -f storage/logs/laravel.log | grep -E "Facebook|webhook|automation"
```

### Step 4: Check Automation Logs
`Dashboard → User → Social Media Automation` → Open the relevant automation → **Logs** tab.

### Manual Webhook Test
Facebook Developer Console → Webhooks → Click **Test** → Select `feed` → Send to My Server.

---

## Troubleshooting

| Issue | Likely Cause | Solution |
|-------|-------------|----------|
| Webhook verification fails | Token mismatch | Ensure `Facebook Webhook Secret` in admin panel exactly matches the Verify Token in Developer Console |
| "Invalid signature" error | Wrong App Secret | Verify `FACEBOOK_APP_SECRET` is correctly entered in the admin panel |
| DM not sent | Cron job not configured | Configure cron: `* * * * * cd /path && php artisan schedule:run` |
| Comments not captured | `feed` not subscribed | Developer Console → Webhooks → Page → subscribe to `feed` |
| "Permissions error" | Missing permission | Verify `pages_messaging` and `pages_manage_metadata` were granted |
| Connection expired | Token expired | `Dashboard → User → Social Media` → Disconnect → Reconnect |
| Automation not triggering | Status not Live | Set automation status to **Live** |
| Specific post not working | Wrong post ID | Check the post ID selected in the automation |
