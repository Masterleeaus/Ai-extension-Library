# Social Media Automation — Genel Kurulum

## Platform Dökümantasyonu

Her platform için detaylı kurulum kılavuzu:

| Platform | Dosya | Webhook URL |
|----------|-------|-------------|
| Facebook | [facebook.md](./facebook.md) | `/social-media/webhook/facebook` |
| Instagram | [instagram.md](./instagram.md) | `/social-media/webhook/instagram` |
| X (Twitter) | [x-twitter.md](./x-twitter.md) | `/social-media/automation/webhook/x` |
| TikTok | [tiktok.md](./tiktok.md) | `/social-media/automation/webhook/tiktok` |

---

## Platform Destek Matrisi

| Platform | Yorum Yakalama | DM Gönderme | Public Reply | Webhook Field |
|----------|:--------------:|:-----------:|:------------:|:-------------:|
| Facebook | ✅ | ✅ | ✅ | `feed` |
| Instagram | ✅ | ✅ (Private Reply) | ✅ | `comments` |
| X (Twitter) | ✅ (reply tweet) | ✅ | ✅ | Account Activity API |
| TikTok | ✅ | ❌ API desteklemiyor | ✅ | `comment.create` |

---

## Değiştirilen Dosyalar (v1 — İlk Release)

Bu özellik için aşağıdaki dosyalar oluşturuldu veya güncellendi:

| Dosya | Değişiklik |
|-------|-----------|
| `app/Extensions/SocialMedia/System/Helpers/Facebook.php` | `getPageFeed()` default fields'dan deprecated `type` field'i kaldırıldı (Facebook posts API 400 hatası düzeltildi) |
| `app/Extensions/SocialMedia/System/Enums/PlatformEnum.php` | `facebook_webhook_secret` uncomment edildi; `webhookUrl()` metodu eklendi |
| `app/Extensions/SocialMedia/resources/views/setting/index.blade.php` | Webhook URI tüm desteklenen platformlarda gösteriliyor |
| `app/Extensions/SocialMedia/System/Http/Controllers/Oauth/FacebookController.php` | `pages_manage_metadata` OAuth scope'u eklendi |
| `app/Extensions/SocialMediaAutomation/System/Http/Controllers/WebhookController.php` | `facebook()` ve `instagram()` metodları eklendi |
| `app/Extensions/SocialMediaAutomation/System/SocialMediaAutomationServiceProvider.php` | Facebook ve Instagram webhook route'ları eklendi |

---

## Sunucu Gereksinimleri

### Cron Job (Zorunlu)

Bekleyen automation'ları işlemek için cron job **mutlaka yapılandırılmış olmalı**. Cron job olmadan yorum gelse bile DM veya reply gönderilmez.

Sunucunun crontab'ına (`crontab -e`) aşağıdaki satırı ekle:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Bu her dakika çalışır ve şunları yapar:
- Bekleyen automation'ları işler (`social-media-automation:process-pending`) — her dakika
- Eski tamamlanmış/başarısız kayıtları temizler (`social-media-automation:cleanup-pending`) — günlük

**cPanel kullanıcıları:** `cPanel → Cron Jobs` bölümüne gidin ve yukarıdaki komutu "Her Dakika" frekansıyla ekleyin.

---

## Admin Panel Ayarları

`Dashboard → Admin → Social Media Settings` sayfasında her platform için credentials girilmeli:

### Facebook
| Alan | Açıklama |
|------|----------|
| `FACEBOOK_APP_ID` | Facebook Developer → Settings → Basic → App ID |
| `FACEBOOK_APP_SECRET` | Facebook Developer → Settings → Basic → App Secret |
| `FACEBOOK_WEBHOOK_SECRET` | Senin belirlediğin verify token — Developer Console'daki ile aynı olmalı |

### Instagram
| Alan | Açıklama |
|------|----------|
| `INSTAGRAM_APP_ID` | Facebook Developer → Settings → Basic → App ID (aynı app) |
| `INSTAGRAM_APP_SECRET` | Facebook Developer → Settings → Basic → App Secret |
| `INSTAGRAM_WEBHOOK_SECRET` | Senin belirlediğin verify token — Developer Console'daki ile aynı olmalı |

### X (Twitter)
| Alan | Açıklama |
|------|----------|
| `X_API_KEY` | Developer Portal → Keys and tokens → API Key |
| `X_API_SECRET` | Developer Portal → Keys and tokens → API Key Secret |
| `X_ACCESS_TOKEN` | Developer Portal → Keys and tokens → Access Token |
| `X_ACCESS_TOKEN_SECRET` | Developer Portal → Keys and tokens → Access Token Secret |
| `X_CLIENT_ID` | User authentication settings → Client ID |
| `X_CLIENT_SECRET` | User authentication settings → Client Secret |

### TikTok
| Alan | Açıklama |
|------|----------|
| `TIKTOK_APP_ID` | App Detail → Client Key |
| `TIKTOK_APP_KEY` | App Detail → Client Key (aynı değer) |
| `TIKTOK_APP_SECRET` | App Detail → Client Secret |

---

## Kurulum Sırası (Önerilen)

1. **Admin panelinde platform credentials gir** (App ID, Secret vb.)
2. **Platform Developer Console'unda webhook URL'ini kaydet ve verify et**
3. **Webhook subscription'larını aktif et** (feed, comments, comment.create vb.)
4. **Kullanıcı hesabını sisteme bağla** (Connect butonu)
5. **Cron job'ın yapılandırıldığını doğrula**
6. **Automation oluştur ve Live'a al**
7. **Test yorumu yaparak sistemi doğrula**

---

## Log Takibi

**Gerçek zamanlı webhook log'ları:**
```bash
tail -f storage/logs/laravel.log | grep -E "webhook|automation|DM|reply"
```

**Başarısız automation'ları görüntüle (Tinker ile):**
```bash
php artisan tinker --execute="echo App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation::where('status','failed')->get();"
```

**Bekleyen automation sayısını kontrol et:**
```bash
php artisan tinker --execute="echo App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation::where('status','pending')->count();"
```

---

## Sık Sorulan Sorular

**S: Webhook verify başarısız — ne yapmalıyım?**
Admin panelindeki Webhook Secret değerinin Developer Console'daki Verify Token ile birebir aynı olduğunu kontrol et. Boşluk veya büyük/küçük harf farkı olmamalı.

**S: Yorum geliyor ama DM/reply gitmiyor.**
Cron job'ın yapılandırıldığından emin ol: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`. Yapılandırılmışsa `storage/logs/laravel.log` dosyasında hata mesajı ara ve `ext_sm_pending_automations` tablosundaki failed kayıtları kontrol et.

**S: Automation log'larında "failed" görünüyor.**
Log'daki `error_message` alanını oku. Genellikle API credential hatası veya expire olmuş token'dır. Platform bağlantısını yenile (Disconnect → Reconnect).

**S: Sadece belirli yorumlara tepki vermek istiyorum.**
Automation oluştururken Keyword filter kullan. Belirli kelimeler içeren yorumlara tepki verecek şekilde ayarla.

**S: TikTok için DM gönderebilir miyim?**
Hayır. TikTok, üçüncü taraf uygulamalara DM API erişimi vermez. Sadece public yorum yanıtlama (reply) desteklenir.
