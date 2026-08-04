# X (Twitter) Automation Kurulum Kılavuzu

## Gereksinimler

- X Developer hesabı → [developer.twitter.com](https://developer.twitter.com)
- **Basic veya Pro plan** (Free plan Account Activity API'ye erişim sağlamaz)
- X App oluşturulmuş olması
- HTTPS destekli, public erişilebilir domain (webhook için)
- Admin panelinde X credentials girilmiş olmalı
- Cron job yapılandırılmış olmalı (`php artisan schedule:run`)

> **Plan Karşılaştırması:**
> - Free: Account Activity API yok
> - Basic (~$100/ay): 1 dev environment, sınırlı webhook
> - Pro (~$5000/ay): Tam erişim

---

## 1. X Developer Portal'da App Oluşturma

1. [developer.twitter.com](https://developer.twitter.com) adresine git
2. **Developer Portal → Projects & Apps → New App** tıkla
3. Uygun bir isim gir → **Complete**
4. Açılan sayfada şu değerleri **hemen kaydet** (bir daha gösterilmez):
   - **API Key** (Consumer Key)
   - **API Key Secret** (Consumer Secret)
   - **Bearer Token**

---

## 2. App Permissions Ayarı

App sayfasında **App permissions** bölümüne git:

**Read and write and Direct message** seçeneğini seç → **Save**

> ⚠️ Bu ayar olmadan DM gönderilemez. Değiştirdikten sonra Access Token'ları yeniden oluşturman gerekir.

---

## 3. OAuth 2.0 Ayarları

App Dashboard → **User authentication settings → Set up** tıkla:

| Alan | Değer |
|------|-------|
| App permissions | Read and write and Direct message |
| Type of App | Web App, Automated App or Bot |
| Callback URI / Redirect URL | `https://experiment.liquid-themes.com/social-media/oauth/callback/x` |
| Website URL | `https://experiment.liquid-themes.com` |

**Save** tıkla. Oluşan **Client ID** ve **Client Secret** değerlerini kaydet.

---

## 4. Access Token Oluşturma

App Dashboard → **Keys and tokens** sayfasına git:

| Değer | Bölüm | Açıklama |
|-------|-------|----------|
| API Key | Consumer Keys | Zaten var |
| API Key Secret | Consumer Keys | Zaten var |
| Access Token | Authentication Tokens → Generate | Hesabın adına işlem yapar |
| Access Token Secret | Authentication Tokens → Generate | Yukarıyla birlikte oluşur |
| Client ID | OAuth 2.0 Client ID and Client Secret | User authentication'dan gelir |
| Client Secret | OAuth 2.0 Client ID and Client Secret | User authentication'dan gelir |

> **Not:** App permissions değiştirdikten sonra Access Token ve Access Token Secret'ı **Regenerate** etmen gerekir.

---

## 5. Admin Paneline Girme

`Dashboard → Admin → Social Media Settings → X` sayfasına git ve doldur:

| Admin Panel Alanı | Kaynak | Değer |
|-------------------|--------|-------|
| X API Key | Consumer Keys → API Key | `xxxxxxxxxxxx` |
| X API Secret | Consumer Keys → API Key Secret | `xxxxxxxxxxxx` |
| X Access Token | Authentication Tokens → Access Token | `xxxxxxxxxxxx` |
| X Access Token Secret | Authentication Tokens → Access Token Secret | `xxxxxxxxxxxx` |
| X Client ID | OAuth 2.0 → Client ID | `xxxxxxxxxxxx` |
| X Client Secret | OAuth 2.0 → Client Secret | `xxxxxxxxxxxx` |

---

## 6. Webhook (Account Activity API) Kurulumu

> **Önemli:** Bu özellik **Basic plan veya üzeri** gerektirir.

### 6.1 Dev Environment Oluştur

X Developer Portal → **Products → Premium → Dev Environments** sayfasına git:

1. **Add dev environment** tıkla
2. Environment label gir (örn: `production`)
3. Uygulamanı seç → **Complete Setup**

### 6.2 Webhook URL'yi Kaydet

Dev environment sayfasında:

| Alan | Değer |
|------|-------|
| Webhook URL | `https://experiment.liquid-themes.com/social-media/automation/webhook/x` |

**Register** tıkla.

Sistem otomatik olarak bu URL'yi çağırır ve **CRC (Challenge Response Check)** doğrulaması yapar:
- X, URL'e `crc_token` parametresiyle GET isteği atar
- Sistem `HMAC-SHA256` ile token'ı imzaleyip döner
- X imzayı doğrularsa webhook kaydedilir

> CRC doğrulaması otomatik yapılır, ekstra bir şey yapman gerekmez.

### 6.3 Subscription Ekle

Webhook kaydedildikten sonra hangi X hesabının olaylarını dinleyeceğini belirt:

```
POST https://api.twitter.com/1.1/account_activity/all/{env_name}/subscriptions.json
```

Bu isteği **Access Token ile OAuth 1.0a** olarak authenticated yapman gerekir. Araç olarak Postman veya curl kullanabilirsin:

```bash
curl -X POST \
  "https://api.twitter.com/1.1/account_activity/all/production/subscriptions.json" \
  --header "Authorization: OAuth ..."
```

---

## 7. Webhook Nasıl Çalışır?

X, "yorumları" reply tweet olarak değerlendirir:

```
Biri tweet'ine reply atar
        ↓
X → Webhook URL'ye POST isteği gönderir
        ↓
/social-media/automation/webhook/x çağrılır
        ↓
tweet_create_events içinde in_reply_to_status_id dolu olanlar işlenir
        ↓
Eşleşen Live automation'lar bulunur
        ↓
Bekleyen automation kaydı veritabanına yazılır
        ↓
Cron job bekleyen kaydı işler (her dakika)
        ↓
X API v2 /dm_conversations/with/{id}/messages ile DM gönderilir
```

---

## 8. X Hesabını Sisteme Bağlama

1. `Dashboard → User → Social Media` sayfasına git
2. X satırında **Connect** tıkla
3. X hesabına giriş yap
4. İzinleri onayla

---

## 9. Automation Oluşturma

1. `Dashboard → User → Social Media Automation → Create` sayfasına git
2. **X** hesabını seç
3. **Trigger** ayarla (All Posts veya Specific Post)
4. **Keyword** filtresi ekle (opsiyonel)
5. **Actions** ekle:
   - `Text` → DM olarak metin gönder
   - `Button` → Link + label içeren mesaj
   - `Quick Replies` → Hazır cevap seçenekleri
6. **Public Reply** aktif edebilirsin (tweet'e reply at)
7. Status'ü **Live** yap

---

## 10. Test Etme

### Adım 1: Cron Job'ın Yapılandırıldığını Doğrula
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Adım 2: Reply At
X hesabındaki bir tweet'e başka hesaptan reply at.

### Adım 3: Log'ları Kontrol Et
```bash
tail -f storage/logs/laravel.log | grep -E "X DM|webhook|automation"
```

---

## Sık Karşılaşılan Sorunlar

| Sorun | Olası Neden | Çözüm |
|-------|-------------|-------|
| CRC challenge başarısız | URL erişilemiyor | Webhook URL'nin public ve HTTPS olduğunu doğrula |
| Webhook kaydedilemiyor | Free plan | Basic plana yükselt |
| DM gönderilemiyor | Permission yanlış | App permission'ı "Read, write and DM" yap, token'ları yenile |
| Reply yakalanmıyor | Subscription yok | Account Activity API subscription'ını aktif et |
| "403 Forbidden" | Token expire | Access Token'ı yeniden generate et |
| Automation işlenmiyor | Cron job yapılandırılmamış | Cron ekle: `* * * * * cd /path && php artisan schedule:run` |
