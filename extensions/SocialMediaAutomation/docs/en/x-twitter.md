# X (Twitter) Automation Setup Guide

## Requirements

- X Developer account → [developer.twitter.com](https://developer.twitter.com)
- **Basic or Pro plan** (Free plan does not include Account Activity API access)
- An X App created in the developer portal
- Publicly accessible domain with HTTPS (required for webhooks)
- X credentials entered in the admin panel
- Cron job must be configured (`php artisan schedule:run`)

> **Plan Comparison:**
> - Free: No Account Activity API
> - Basic (~$100/month): 1 dev environment, limited webhook access
> - Pro (~$5,000/month): Full access

---

## 1. Create an App in X Developer Portal

1. Go to [developer.twitter.com](https://developer.twitter.com)
2. Click **Developer Portal → Projects & Apps → New App**
3. Enter a name → **Complete**
4. **Immediately save** the following (they will not be shown again):
   - **API Key** (Consumer Key)
   - **API Key Secret** (Consumer Secret)
   - **Bearer Token**

---

## 2. Set App Permissions

On the App page, go to **App permissions** and select:

**Read and write and Direct message**

Click **Save**.

> ⚠️ Without this setting, DMs cannot be sent. After changing this, you must regenerate your Access Tokens.

---

## 3. OAuth 2.0 Settings

App Dashboard → **User authentication settings → Set up**:

| Field | Value |
|-------|-------|
| App permissions | Read and write and Direct message |
| Type of App | Web App, Automated App or Bot |
| Callback URI / Redirect URL | `https://experiment.liquid-themes.com/social-media/oauth/callback/x` |
| Website URL | `https://experiment.liquid-themes.com` |

Click **Save**. Note the generated **Client ID** and **Client Secret**.

---

## 4. Access Tokens

Go to App Dashboard → **Keys and tokens**:

| Value | Section | Notes |
|-------|---------|-------|
| API Key | Consumer Keys | Already available |
| API Key Secret | Consumer Keys | Already available |
| Access Token | Authentication Tokens → Generate | Acts on behalf of your account |
| Access Token Secret | Authentication Tokens → Generate | Generated together with Access Token |
| Client ID | OAuth 2.0 Client ID and Client Secret | From User authentication settings |
| Client Secret | OAuth 2.0 Client ID and Client Secret | From User authentication settings |

> **Note:** After changing App permissions, you must click **Regenerate** on the Access Token and Access Token Secret.

---

## 5. Enter Credentials in Admin Panel

Go to `Dashboard → Admin → Social Media Settings → X` and fill in:

| Admin Panel Field | Source | Value |
|-------------------|--------|-------|
| X API Key | Consumer Keys → API Key | `xxxxxxxxxxxx` |
| X API Secret | Consumer Keys → API Key Secret | `xxxxxxxxxxxx` |
| X Access Token | Authentication Tokens → Access Token | `xxxxxxxxxxxx` |
| X Access Token Secret | Authentication Tokens → Access Token Secret | `xxxxxxxxxxxx` |
| X Client ID | OAuth 2.0 → Client ID | `xxxxxxxxxxxx` |
| X Client Secret | OAuth 2.0 → Client Secret | `xxxxxxxxxxxx` |

---

## 6. Webhook (Account Activity API) Setup

> **Important:** This feature requires a **Basic plan or higher**.

### 6.1 Create a Dev Environment

X Developer Portal → **Products → Premium → Dev Environments**:

1. Click **Add dev environment**
2. Enter a label (e.g., `production`)
3. Select your app → **Complete Setup**

### 6.2 Register the Webhook URL

On the dev environment page:

| Field | Value |
|-------|-------|
| Webhook URL | `https://experiment.liquid-themes.com/social-media/automation/webhook/x` |

Click **Register**.

X will automatically send a **CRC (Challenge Response Check)** GET request to verify the URL:
- X sends a request with a `crc_token` query parameter
- The system signs it with HMAC-SHA256 and returns the result
- X validates the signature and confirms the webhook

> CRC verification is handled automatically — no extra steps needed.

### 6.3 Add a Subscription

After registering the webhook, specify which X account's events to listen to:

```
POST https://api.twitter.com/1.1/account_activity/all/{env_name}/subscriptions.json
```

This request must be authenticated with OAuth 1.0a using your Access Token. You can use Postman or curl:

```bash
curl -X POST \
  "https://api.twitter.com/1.1/account_activity/all/production/subscriptions.json" \
  --header "Authorization: OAuth ..."
```

---

## 7. How It Works

X treats replies as "tweet replies":

```
Someone replies to your tweet
        ↓
X sends a POST request to your webhook URL
        ↓
/social-media/automation/webhook/x is called
        ↓
tweet_create_events with non-null in_reply_to_status_id are processed
        ↓
Matching Live automations are found
        ↓
Pending automation record is saved to the database
        ↓
Cron job processes the pending record (every minute)
        ↓
X API v2 /dm_conversations/with/{id}/messages sends the DM
```

---

## 8. Connect Your X Account

1. Go to `Dashboard → User → Social Media`
2. Click **Connect** on the X row
3. Log in to your X account and authorize

---

## 9. Create an Automation

1. Go to `Dashboard → User → Social Media Automation → Create`
2. Select your **X** account
3. Set the **Trigger** (All Posts or Specific Post)
4. Add optional **Keyword** filter
5. Add **Actions**:
   - `Text` → Send a DM text message
   - `Button` → Message with link and label
   - `Quick Replies` → Predefined reply options
6. Optionally enable **Public Reply** (reply tweet)
7. Set status to **Live**

---

## 10. Testing

### Step 1: Confirm Cron Job is Configured
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Step 2: Reply to a Tweet
Reply to one of your tweets from a different account.

### Step 3: Check the Logs
```bash
tail -f storage/logs/laravel.log | grep -E "X DM|webhook|automation"
```

---

## Troubleshooting

| Issue | Likely Cause | Solution |
|-------|-------------|----------|
| CRC challenge fails | URL not accessible | Verify webhook URL is public and HTTPS |
| Webhook cannot be registered | Free plan | Upgrade to Basic plan |
| DM cannot be sent | Wrong permission | Set App permission to "Read, write and DM" and regenerate tokens |
| Replies not captured | No subscription | Activate Account Activity API subscription |
| "403 Forbidden" | Token expired | Regenerate Access Token |
| Automation not processing | Cron job not configured | Configure cron: `* * * * * cd /path && php artisan schedule:run` |
