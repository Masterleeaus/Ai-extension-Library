# Instagram Automation Setup Guide

## Requirements

- Facebook Developer account → [developers.facebook.com](https://developers.facebook.com) (Instagram API runs on Meta infrastructure)
- **Instagram Professional account** — Business or Creator (Personal profiles are not supported)
- Instagram Professional account must be **linked to a Facebook Business Page**
- Publicly accessible domain with HTTPS (required for webhooks)
- Instagram App ID and App Secret entered in the admin panel
- Cron job must be configured (`php artisan schedule:run`)

---

## 1. Create a Facebook Developer App

> You can reuse the same Meta app for both Facebook and Instagram. Skip this step if you already have one.

1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Click **My Apps → Create App**
3. App type: **Business**
4. Enter the app name → **Create App**

---

## 2. Add Required Products

App Dashboard → **Add Product** and add:

| Product | Purpose | How |
|---------|---------|-----|
| **Instagram Graph API** | Core Instagram API | Add Product → Instagram Graph API → Set Up |
| **Webhooks** | Receive comment notifications | Add Product → Webhooks → Set Up |
| **Facebook Login** | OAuth account connection | Add Product → Facebook Login → Set Up |

---

## 3. App Credentials

From **Settings → Basic**:

| Field | Value | Note |
|-------|-------|------|
| App ID | Shown at the top | Enter in admin panel |
| App Secret | Click **Show** | Enter in admin panel |

Enter these values at:
`Dashboard → Admin → Social Media Settings → Instagram`

---

## 4. OAuth Redirect URI

Go to **Facebook Login → Settings** and add to **Valid OAuth Redirect URIs**:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/instagram
```

Click **Save Changes**.

---

## 5. Permissions

From **App Review → Permissions and Features**, add the following:

| Permission | Description | Required |
|------------|-------------|----------|
| `instagram_basic` | Basic profile information | ✅ Yes |
| `instagram_content_publish` | Publish content | ✅ Yes |
| `instagram_manage_comments` | Read and reply to comments | ✅ Yes |
| `instagram_manage_messages` | Send DMs (Private Reply) | ✅ Yes |
| `instagram_manage_insights` | Analytics | Optional |
| `pages_read_engagement` | Access linked Facebook page | ✅ Yes |
| `pages_show_list` | List pages | ✅ Yes |
| `business_management` | Business Manager access | ✅ Yes |

> **Development Mode:** App Review is not required for testing — only accounts added to the app as Admin/Developer can use these permissions.

---

## 6. Webhook Setup

### 6.1 Set a Webhook Secret

Set a verify token in the admin panel:

`Dashboard → Admin → Social Media Settings → Instagram → Instagram Webhook Secret`

> Example: `fixbiz-instagram-webhook-2024`
> Keep a note of this value — you will use it in the Developer Console.

### 6.2 Register the Webhook Callback URL

Go to Facebook Developer Console → **Webhooks** (Instagram webhooks are also managed here):

1. Click **Add Callback URL**
2. Enter the following:

| Field | Value |
|-------|-------|
| Callback URL | `https://experiment.liquid-themes.com/social-media/webhook/instagram` |
| Verify Token | The `Instagram Webhook Secret` value you set in the admin panel |

3. Click **Verify and Save**

### 6.3 Subscription — Which Events to Listen To

On the Webhooks page, find the **Instagram** object and subscribe to:

| Field | Description | Required |
|-------|-------------|----------|
| **`comments`** | Comments on posts | ✅ Yes — this is all you need |

> **Important:** Only `comments` is required. No other field subscriptions are needed.

---

## 7. Link Instagram Account to a Facebook Page

> This step is mandatory. The Instagram Graph API does not work without a linked Facebook Page.

**From the Instagram App:**
1. Go to your profile → tap **≡ (Menu)**
2. **Settings → Account → Switch to Professional Account** (if not already — choose Business or Creator)
3. **Settings → Account → Linked Accounts → Facebook**
4. Log in to Facebook and select your page

**From Facebook:**
1. Go to your Facebook page → **Settings → Instagram**
2. Connect your Instagram account

---

## 8. Connect to the System

1. Go to `Dashboard → User → Social Media`
2. Click **Connect** on the Instagram row
3. Log in with your **Facebook** account (not Instagram directly)
4. Select your linked Instagram account
5. Click **Allow** for all requested permissions
6. Your Instagram username should appear after successful connection

---

## 9. About Private Reply (DM)

Instagram automation responds to comments via **Private Reply**:

| Feature | Detail |
|---------|--------|
| Delivery method | Instagram Messenger API — Private Reply |
| Recipient | The person who left the comment |
| How it appears | Arrives as a DM; "Sent a private reply" note appears under the comment |
| First-time message | The recipient sees it as a "Message Request" and must accept it |
| Time limit | Private Replies can only be sent within **7 days** of the original comment |
| Daily limit | API rate limits apply; errors will occur if exceeded |

---

## 10. Create an Automation

1. Go to `Dashboard → User → Social Media Automation → Create`
2. Select your **Instagram** account
3. Set the **Trigger**:
   - `All Posts` → respond to comments on any post
   - `Specific Post` → respond to comments on a selected post
   - `Next Post` → respond to comments on your next post
4. Set **Keyword** filter (optional)
5. Add **Actions** (sent as DM):
   - `Text` → Text message
   - `Button` → Button with link
   - `Image` → Image attachment
   - `Quick Replies` → Predefined reply options
   - `Delay` → Wait before the next action
6. Optionally enable **Public Reply** (visible comment reply)
7. Set status to **Live**

---

## 11. How It Works

```
Someone comments on your Instagram post
        ↓
Instagram sends a POST request to your webhook URL
        ↓
/social-media/webhook/instagram is called
        ↓
Signature is verified (using INSTAGRAM_APP_SECRET)
        ↓
Payload is parsed: entry → changes → field=comments
        ↓
Matching Live automations are found
        ↓
Pending automation record is saved to the database
        ↓
Cron job processes the pending record (every minute)
        ↓
/{ig-user-id}/messages API sends the Private Reply
```

---

## 12. Testing

### Step 1: Confirm Cron Job is Configured
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Step 2: Post a Comment
Comment on one of your Instagram posts.

### Step 3: Check the Logs
```bash
tail -f storage/logs/laravel.log | grep -E "Instagram|webhook|automation"
```

### Step 4: Check Automation Logs
`Dashboard → User → Social Media Automation` → Open the relevant automation → **Logs** tab.

---

## Troubleshooting

| Issue | Likely Cause | Solution |
|-------|-------------|----------|
| "Personal profile" error | Using a personal account | Switch Instagram account to Business or Creator |
| Webhook verification fails | Token mismatch | `INSTAGRAM_WEBHOOK_SECRET` in admin panel must match Developer Console exactly |
| "Invalid signature" error | Wrong App Secret | Verify `INSTAGRAM_APP_SECRET` is correctly entered in the admin panel |
| DM not sent | Cron job not configured | Configure cron: `* * * * * cd /path && php artisan schedule:run` |
| Comments not captured | `comments` not subscribed | Developer Console → Webhooks → Instagram → subscribe to `comments` |
| No linked page | Instagram not connected to Facebook | Link Instagram account to a Facebook Page |
| Private Reply not sent | Comment is older than 7 days | Test with a fresh comment |
| Token expired | Tokens refresh every 60 days | Disconnect → Reconnect the account |
| Automation not triggering | Status not Live | Set automation status to **Live** |
