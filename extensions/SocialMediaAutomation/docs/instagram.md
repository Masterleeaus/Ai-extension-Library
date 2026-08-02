# Instagram Automation Kurulum Kılavuzu

## Gereksinimler

- Facebook Developer hesabı → [developers.facebook.com](https://developers.facebook.com) (Instagram API Meta altyapısı üzerinden çalışır)
- **Instagram Professional hesabı** — Business veya Creator (Kişisel profil desteklenmez)
- Instagram Professional hesabının bir **Facebook Business Page'e bağlı** olması
- HTTPS destekli, public erişilebilir domain (webhook için)
- Admin panelinde Instagram App ID ve App Secret girilmiş olmalı
- Cron job yapılandırılmış olmalı (`php artisan schedule:run`)

---

## 1. Facebook Developer App Oluşturma

> Facebook ve Instagram için aynı Meta app'i kullanabilirsin. Zaten bir app varsa bu adımı geç.

1. [developers.facebook.com](https://developers.facebook.com) adresine git
2. **My Apps → Create App** tıkla
3. App type: **Business**
4. App adını gir → **Create App**

---

## 2. Instagram Graph API Ürününü Ekle

App Dashboard → **Add Product → Instagram Graph API → Set Up**

Ayrıca şunları da ekle:
- **Webhooks** → Add Product → Webhooks → Set Up
- **Facebook Login** → Add Product → Facebook Login → Set Up

---

## 3. App Credentials

**Settings → Basic** sayfasından:

| Alan | Değer | Nerede |
|------|-------|--------|
| App ID | Sayfanın üstünde | Admin panele gir |
| App Secret | **Show** tıkla | Admin panele gir |

Şu adrese gir:
`Dashboard → Admin → Social Media Settings → Instagram`

---

## 4. OAuth Redirect URI

**Facebook Login → Settings → Valid OAuth Redirect URIs** alanına ekle:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/instagram
```

**Save Changes** tıkla.

---

## 5. İzinler (Permissions)

**App Review → Permissions and Features** bölümünden şunları ekle:

| İzin | Açıklama | Zorunlu mu |
|------|----------|-----------|
| `instagram_basic` | Temel profil bilgileri | ✅ Evet |
| `instagram_content_publish` | İçerik yayınlama | ✅ Evet |
| `instagram_manage_comments` | Yorumları okuma ve yanıtlama | ✅ Evet |
| `instagram_manage_messages` | DM gönderme (Private Reply) | ✅ Evet |
| `instagram_manage_insights` | İstatistikler | Opsiyonel |
| `pages_read_engagement` | Bağlı Facebook sayfası okuma | ✅ Evet |
| `pages_show_list` | Sayfaları listeleme | ✅ Evet |
| `business_management` | Business Manager erişimi | ✅ Evet |

> **Development Mode:** Test aşamasında izinler için App Review gerekmez, sadece app'e eklenmiş hesaplar kullanabilir.

---

## 6. Webhook Kurulumu

### 6.1 Webhook Secret Belirle

Admin panelinde bir verify token belirle:

`Dashboard → Admin → Social Media Settings → Instagram → Instagram Webhook Secret`

> Örnek: `fixbiz-instagram-webhook-2024`
> Bu değeri not al, Developer Console'da aynısını kullanacaksın.

### 6.2 Webhook Callback URL Ekle

Facebook Developer Console → **Webhooks** sayfasına git (Instagram webhookları da buradan yönetilir):

1. **Add Callback URL** tıkla
2. Şu değerleri gir:

| Alan | Değer |
|------|-------|
| Callback URL | `https://experiment.liquid-themes.com/social-media/webhook/instagram` |
| Verify Token | Admin panelinde girdiğin `Instagram Webhook Secret` değeri |

3. **Verify and Save** tıkla
4. Doğrulama başarılıysa devam et

### 6.3 Subscription — Hangi Olayları Dinleyeceğiz

Webhooks sayfasında **Instagram** nesnesinin altına in ve şu field'ı subscribe et:

| Field | Açıklama | Gerekli mi |
|-------|----------|-----------|
| **`comments`** | Gönderilere yapılan yorumlar | ✅ Evet — bu tek yeterli |

> **Önemli:** Sadece `comments` subscribe etmek yeterlidir. `mentions`, `messages` veya başka field'lara subscription yapma gerekmez.

---

## 7. Instagram Hesabını Facebook Sayfasına Bağlama

> Bu adım zorunludur. Instagram Professional hesabı bir Facebook sayfasına bağlı olmadan API çalışmaz.

**Instagram Uygulamasından:**
1. Profil sayfana git → **Ayarlar (☰)**
2. **Hesap → Profesyonel Hesaba Geç** (henüz değilse Business veya Creator seç)
3. **Hesap → Bağlı Hesaplar → Facebook**
4. Facebook hesabına giriş yap ve sayfanı seç

**Facebook'tan:**
1. Facebook sayfana git → **Ayarlar → Instagram**
2. Instagram hesabını bağla

---

## 8. Sisteme Bağlama

1. `Dashboard → User → Social Media` sayfasına git
2. Instagram satırında **Connect** tıkla
3. Facebook hesabınla giriş yap (Instagram değil, Facebook ile)
4. Bağlı Instagram hesabını seç
5. İstenen tüm izinlere **Allow/İzin Ver** tıkla
6. Başarılı bağlantı sonrası Instagram kullanıcı adın görünmeli

---

## 9. Private Reply (DM) Hakkında

Instagram automation, yoruma **Private Reply** yöntemiyle yanıt verir:

| Özellik | Detay |
|---------|-------|
| Gönderim yöntemi | Instagram Messenger API — Private Reply |
| Alıcı | Yorumu yapan kişi |
| Görünüm | Kullanıcının DM kutusuna düşer, yorum altında "Gizli mesaj gönderildi" ibaresi çıkar |
| İlk mesajsa | Kullanıcı "Mesaj isteği" olarak görür, kabul etmesi gerekir |
| Zaman kısıtı | Yorum yapıldıktan sonra **7 gün** içinde gönderilebilir |
| Günlük limit | API'nin rate limit'ini aşarsa hata döner |

---

## 10. Automation Oluşturma

1. `Dashboard → User → Social Media Automation → Create` sayfasına git
2. **Instagram** hesabını seç
3. **Trigger** ayarla:
   - `All Posts` → Tüm gönderilerden gelen yorumlara
   - `Specific Post` → Seçilen gönderiye gelen yorumlara
   - `Next Post` → Sonraki paylaşıma gelen yorumlara
4. **Keyword** filtresi (opsiyonel):
   - Belirli kelimeler içeren yorumlara tepki ver
5. **Actions** ekle (DM olarak gönderilir):
   - `Text` → Metin mesajı
   - `Button` → Link buton
   - `Image` → Görsel
   - `Quick Replies` → Hızlı yanıt seçenekleri
   - `Delay` → Bekle
6. **Public Reply** aktif edebilirsin (yorum altına public cevap)
7. Status'ü **Live** yap

---

## 11. Nasıl Çalışır?

```
Biri gönderiye yorum yazar
        ↓
Instagram → Webhook URL'ye POST isteği gönderir
        ↓
/social-media/webhook/instagram çağrılır
        ↓
Signature doğrulanır (INSTAGRAM_APP_SECRET ile)
        ↓
Payload işlenir: entry → changes → field=comments
        ↓
Eşleşen Live automation'lar bulunur
        ↓
Bekleyen automation kaydı veritabanına yazılır
        ↓
Cron job bekleyen kaydı işler (her dakika)
        ↓
/{ig-user-id}/messages API'si ile Private Reply gönderilir
```

---

## 12. Test Etme

### Adım 1: Cron Job'ın Yapılandırıldığını Doğrula
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Adım 2: Yorum Yap
Instagram gönderine yorum yaz.

### Adım 3: Log'ları Kontrol Et
```bash
tail -f storage/logs/laravel.log | grep -E "Instagram|webhook|automation"
```

### Adım 4: Automation Log'larını Kontrol Et
`Dashboard → User → Social Media Automation` → İlgili automation'ın **Logs** sekmesine bak.

---

## Sık Karşılaşılan Sorunlar

| Sorun | Olası Neden | Çözüm |
|-------|-------------|-------|
| "Personal profile" hatası | Kişisel hesap kullanılıyor | Instagram hesabını Business veya Creator'a çevir |
| Webhook verify başarısız | Token uyuşmuyor | `INSTAGRAM_WEBHOOK_SECRET` admin panelindekiyle aynı olmalı |
| "Invalid signature" hatası | App Secret yanlış | `INSTAGRAM_APP_SECRET` admin panelinde doğru girilmeli |
| DM gönderilmiyor | Cron job yapılandırılmamış | Cron ekle: `* * * * * cd /path && php artisan schedule:run` |
| Yorum yakalanmıyor | `comments` subscribe edilmemiş | Developer Console → Webhooks → Instagram → `comments` subscribe et |
| "Facebook sayfasına bağlı değil" | Instagram-Facebook bağlantısı yok | Instagram ayarlarından Facebook sayfasına bağla |
| Private Reply gitmiyor | Yorum 7 günden eski | Daha yeni bir yorumla test et |
| Token süresi doldu | 60 günde bir yenilenir | Disconnect → Reconnect yap |
| Automation tetiklenmiyor | Status Live değil | Automation'ı Live'a al |
