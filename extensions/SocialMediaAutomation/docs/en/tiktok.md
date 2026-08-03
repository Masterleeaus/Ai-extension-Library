# TikTok Automation Setup Guide

## Requirements

- TikTok Developer account → [developers.tiktok.com](https://developers.tiktok.com)
- **TikTok Business or Creator account** (Personal accounts are not supported)
- An approved TikTok Developer App
- Publicly accessible domain with HTTPS (required for webhooks)
- TikTok credentials entered in the admin panel
- Cron job must be configured (`php artisan schedule:run`)

> **⚠️ Important Limitation:** TikTok does **not grant DM API access** to third-party apps. TikTok automation only supports **public comment replies**.

---

## 1. Create a TikTok Developer App

1. Go to [developers.tiktok.com](https://developers.tiktok.com)
2. Log in and click **Manage Apps**
3. Click **Create app**
4. Fill in the following:

| Field | Value |
|-------|-------|
| App name | Your project name |
| App description | Short description |
| Category | Business |
| Platform | Web |

5. Click **Submit** → App enters the review process

> TikTok apps require approval. Basic scopes are approved quickly; sensitive scopes may take longer.

---

## 2. App Credentials

After approval, go to the **App Detail** page:

| Field | Where | Admin Panel Field |
|-------|-------|-------------------|
| Client Key (App ID) | App Detail → Client Key | `TIKTOK_APP_ID` and `TIKTOK_APP_KEY` |
| Client Secret | App Detail → Client Secret | `TIKTOK_APP_SECRET` |

Enter these values at:
`Dashboard → Admin → Social Media Settings → TikTok`

> **Note:** Enter the same **Client Key** value in both `TIKTOK_APP_ID` and `TIKTOK_APP_KEY` fields.

---

## 3. OAuth Redirect URI

Add the following to **App Detail → Redirect URI**:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/tiktok
```

Click **Save**.

---

## 4. Scopes

From **App Detail → Scopes**, add and request approval for:

| Scope | Description | Required |
|-------|-------------|----------|
| `user.info.basic` | Basic user information | ✅ Yes |
| `video.list` | Video list — to fetch posts | ✅ Yes |
| `comment.list` | Read comment list | ✅ Yes |
| `comment.create` | Reply to comments | ✅ Yes |

> **No DM scope:** TikTok's DM API (`message.send`, etc.) is not publicly available. Only comment replies are supported.

---

## 5. Domain Verification (Required)

TikTok requires domain ownership verification before enabling webhooks.

1. Go to **App Detail → Domain Verification**
2. Download the verification file (e.g., `tiktokXXXXXXXX.txt`)
3. Upload it in the admin panel:
   `Dashboard → Admin → Social Media Settings → TikTok → Verification File`
4. Click **Verify** in the TikTok Developer Portal
5. A domain verified confirmation message should appear

---

## 6. Webhook Setup

### 6.1 Register the Webhook URL

Go to **App Detail → Event Subscriptions** or the **Webhook** section:

| Field | Value |
|-------|-------|
| Endpoint URL | `https://experiment.liquid-themes.com/social-media/automation/webhook/tiktok` |

Click **Save**.

TikTok will send a challenge request to verify the URL:
- A POST request is sent with a `challenge` parameter
- The system returns the same `challenge` value as JSON
- Verification succeeds and the webhook is registered

> This verification is handled automatically — no extra steps needed.

### 6.2 Event Subscription — Which Events to Listen To

| Event | Description | Required |
|-------|-------------|----------|
| **`comment.create`** | Triggered when a new comment is posted | ✅ Yes |

> Only `comment.create` is required.

---

## 7. How It Works

TikTok webhook uses the following payload format:

```json
{
  "event": "comment.create",
  "data": {
    "video_id": "7123456789012345678",
    "comment_id": "7123456789012345679",
    "user_id": "6123456789012345678",
    "username": "username",
    "display_name": "Display Name",
    "text": "What is the price of the product in this video?"
  }
}
```

```
Someone comments on your video
        ↓
TikTok sends a POST request to your webhook URL
        ↓
/social-media/automation/webhook/tiktok is called
        ↓
event = "comment.create" is checked
        ↓
Matching Live automations are found
        ↓
Pending automation record is saved to the database
        ↓
Cron job processes the pending record (every minute)
        ↓
/v2/comment/reply/create/ API posts a public reply
```

---

## 8. Connect Your TikTok Account

1. Go to `Dashboard → User → Social Media`
2. Click **Connect** on the TikTok row
3. Log in to your TikTok account
4. Accept the requested scope permissions

---

## 9. DM Limitation — Important

| Feature | Status |
|---------|--------|
| Public comment reply | ✅ Supported |
| DM (direct message) | ❌ TikTok API does not allow it |
| Private message | ❌ Not supported |

When creating an automation:
- **DM / Text / Button / Image action** → will not work for TikTok
- **Public Reply action** → supported; reply appears under the comment ✓

---

## 10. Create an Automation

1. Go to `Dashboard → User → Social Media Automation → Create`
2. Select your **TikTok** account
3. Set the **Trigger**:
   - `All Posts` → respond to comments on any video
   - `Specific Post` → respond to comments on a selected video
4. Add optional **Keyword** filter:
   - E.g., respond only to comments containing "price", "link", "where to buy"
5. Add **Public Reply** action (DM action does not work for TikTok)
6. Set status to **Live**

---

## 11. Testing

### Step 1: Confirm Cron Job is Configured
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Step 2: Post a Comment
Comment on one of your TikTok videos.

### Step 3: Check the Logs
```bash
tail -f storage/logs/laravel.log | grep -E "TikTok|tiktok|webhook|automation"
```

### Step 4: Check Automation Logs
`Dashboard → User → Social Media Automation` → Open the relevant automation → **Logs** tab.

---

## Troubleshooting

| Issue | Likely Cause | Solution |
|-------|-------------|----------|
| Webhook challenge fails | URL not accessible | Verify the URL is public and HTTPS |
| Domain verification fails | File not uploaded | Upload the verification file via admin panel |
| Comments not captured | `comment.create` not subscribed | Select `comment.create` in event subscriptions |
| Reply not sent | `comment.create` scope missing | Add and get approval for `comment.create` scope in the app |
| DM not working | TikTok limitation | Use Public Reply action instead of DM |
| App not approved | Pending review | Wait for TikTok's approval process |
| Automation not processing | Cron job not configured | Configure cron: `* * * * * cd /path && php artisan schedule:run` |
