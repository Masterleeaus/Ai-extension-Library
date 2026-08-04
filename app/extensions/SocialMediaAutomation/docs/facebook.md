# Facebook Automation Kurulum Kılavuzu

## Gereksinimler

- Facebook Developer hesabı → [developers.facebook.com](https://developers.facebook.com)
- **Facebook Business Page** (Kişisel profil değil, Page olmalı)
- HTTPS destekli, public erişilebilir domain (webhook için)
- Admin panelinde Facebook App ID ve App Secret girilmiş olmalı
- Cron job yapılandırılmış olmalı (`php artisan schedule:run`)

---

## 1. Facebook Developer App Oluşturma

1. [developers.facebook.com](https://developers.facebook.com) adresine git
2. Sağ üstten **My Apps → Create App** tıkla
3. App type olarak **Business** seç
4. App adı ve iletişim e-postasını gir
5. **Create App** tıkla
6. Güvenlik doğrulamasını (captcha) geç

---

## 2. Gerekli Ürünleri Ekle

App Dashboard'da sol menüden **Add Product** tıkla ve şunları ekle:

| Ürün | Açıklama | Nasıl Eklenir |
|------|----------|---------------|
| **Messenger** | DM (private reply) göndermek için | Add Product → Messenger → Set Up |
| **Webhooks** | Yorum bildirimlerini almak için | Add Product → Webhooks → Set Up |
| **Facebook Login** | OAuth bağlantısı için | Add Product → Facebook Login → Set Up |

---

## 3. App Credentials

**Settings → Basic** sayfasına git:

| Alan | Değer | Açıklama |
|------|-------|----------|
| App ID | Sayfanın üstünde görünür | Admin panele girilecek |
| App Secret | **Show** butonuna tıkla | Admin panele girilecek |

Bu değerleri şu adrese gir:
`Dashboard → Admin → Social Media Settings → Facebook`

---

## 4. Facebook Login — OAuth Redirect URI

**Facebook Login → Settings** sayfasına git ve **Valid OAuth Redirect URIs** alanına ekle:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/facebook
```

**Save Changes** tıkla.

---

## 5. İzinler (Permissions)

**App Review → Permissions and Features** bölümünden aşağıdaki izinleri ekle:

| İzin | Açıklama | Zorunlu mu |
|------|----------|-----------|
| `pages_manage_posts` | Sayfa gönderilerini yönetme | ✅ Evet |
| `pages_show_list` | Sayfaları listeleme | ✅ Evet |
| `pages_read_user_content` | Kullanıcı içeriklerini okuma | ✅ Evet |
| `pages_read_engagement` | Etkileşim verilerini okuma | ✅ Evet |
| `pages_messaging` | Messenger DM gönderme | ✅ Evet |
| `pages_manage_metadata` | Webhook subscription için | ✅ Evet |
| `read_insights` | Sayfa istatistikleri | Opsiyonel |

> **Development Mode:** Test aşamasında App Review'a gerek yok, sadece kendi hesabın ve app'e Admin/Developer olarak eklenmiş hesaplar bu izinleri kullanabilir.
>
> **Production:** Herkese açmak için her izin için ayrı App Review başvurusu gerekir.

---

## 6. Webhook Kurulumu

### 6.1 Webhook Secret Belirle

Admin panelinde bir verify token belirle:

`Dashboard → Admin → Social Media Settings → Facebook → Facebook Webhook Secret`

> Örnek: `fixbiz-facebook-webhook-2024`
> Bu değeri bir yere not al, Developer Console'da aynısını kullanacaksın.

### 6.2 Webhook Callback URL Ekle

Facebook Developer Console → **Webhooks** sayfasına git:

1. **Add Callback URL** tıkla
2. Şu değerleri gir:

| Alan | Değer |
|------|-------|
| Callback URL | `https://experiment.liquid-themes.com/social-media/webhook/facebook` |
| Verify Token | Admin panelinde girdiğin `Facebook Webhook Secret` değeri |

3. **Verify and Save** tıkla
4. Facebook sistem otomatik olarak bu URL'yi çağırır ve token doğrulaması yapar
5. Doğrulama başarılıysa **"Webhooks saved"** mesajı görünür

> **Verify başarısız olursa:** Admin panelindeki token ile buradaki token'ın birebir aynı olduğunu kontrol et. Boşluk veya büyük/küçük harf farkı olmamalı.

### 6.3 Subscription — Hangi Olayları Dinleyeceğiz

Webhooks sayfasında **Page** nesnesinin altına in ve şu field'ı subscribe et:

| Field | Açıklama | Gerekli mi |
|-------|----------|-----------|
| **`feed`** | Yorumlar, beğeniler, yeni gönderiler | ✅ Evet — bu tek yeterli |

> **Önemli:** Sadece `feed` yeterlidir. Sistem gelen tüm feed olayları arasından otomatik olarak yalnızca `item = "comment"` olanları işler, diğerlerini (beğeniler, yeni gönderiler vb.) görmezden gelir. Başka field'lara subscription yapma gerekmez.

---

## 7. Facebook Hesabını Sisteme Bağlama

1. `Dashboard → User → Social Media` sayfasına git
2. Facebook satırında **Connect** tıkla
3. Açılan Facebook ekranında Business sayfanı seç
4. İstenen tüm izinlere **Allow/İzin Ver** tıkla
5. Başarılı bağlantı sonrası sayfa adın ve profil resmin görünmeli

> **Uyarı:** Daha önce bağladıysan ve `pages_manage_metadata` iznini vermediysen **Disconnect** yapıp yeniden bağla. Bu izin olmadan webhook subscription çalışmaz.

---

## 8. Automation Oluşturma

1. `Dashboard → User → Social Media Automation → Create` sayfasına git
2. **Facebook** hesabını seç
3. **Trigger** ayarla:
   - `All Posts` → Tüm gönderilerden gelen yorumlara tepki ver
   - `Specific Post` → Yalnızca seçilen gönderiye gelen yorumlara tepki ver
   - `Next Post` → Bundan sonra paylaşacağın ilk gönderiye gelen yorumlara
4. **Keyword** filtresi (opsiyonel):
   - Belirli bir kelime içeren yorumlara tepki ver (örn: "fiyat", "bilgi")
   - Belirli kelimeleri hariç tut
5. **Actions** ekle:
   - `Text` → DM olarak metin gönder
   - `Button` → Tıklanabilir buton ile link gönder
   - `Image` → Görsel gönder
   - `Quick Replies` → Hazır cevap seçenekleri
   - `Delay` → Sonraki action'dan önce bekle
6. **Public Reply** aktif edebilirsin (yorum altına public cevap)
7. Status'ü **Live** yap → Automation aktif olur

---

## 9. Nasıl Çalışır?

```
Biri gönderiye yorum yazar
        ↓
Facebook → Webhook URL'ye POST isteği gönderir
        ↓
/social-media/webhook/facebook çağrılır
        ↓
Signature doğrulanır (FACEBOOK_APP_SECRET ile)
        ↓
Payload işlenir: entry → changes → field=feed, item=comment
        ↓
Eşleşen Live automation'lar bulunur
        ↓
Bekleyen automation kaydı veritabanına yazılır
        ↓
Cron job bekleyen kaydı işler (her dakika)
        ↓
DM / Public Reply gönderilir
```

---

## 10. Test Etme

### Adım 1: Cron Job'ın Yapılandırıldığını Doğrula
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Adım 2: Yorum Yap
Facebook sayfandaki bir gönderiye yorum yaz.

### Adım 3: Log'ları Kontrol Et
```bash
tail -f storage/logs/laravel.log | grep -E "Facebook|webhook|automation"
```

### Adım 4: Automation Log'larını Kontrol Et
`Dashboard → User → Social Media Automation` → İlgili automation'ın **Logs** sekmesine bak.

### Webhook'u Manuel Test Etme
Facebook Developer Console → Webhooks → **Test** butonuna tıkla → `feed` seç → Send to My Server.

---

## Sık Karşılaşılan Sorunlar

| Sorun | Olası Neden | Çözüm |
|-------|-------------|-------|
| Webhook verify başarısız | Token uyuşmuyor | Admin panelindeki `Facebook Webhook Secret` ile Developer Console'daki Verify Token birebir aynı olmalı |
| "Invalid signature" hatası | App Secret yanlış | `FACEBOOK_APP_SECRET` admin panelinde doğru girilmeli |
| DM gönderilmiyor | Cron job yapılandırılmamış | Cron ekle: `* * * * * cd /path && php artisan schedule:run` |
| Yorum yakalanmıyor | `feed` subscribe edilmemiş | Developer Console → Webhooks → Page → `feed` subscribe et |
| "Permissions error" | İzin eksik | `pages_messaging` ve `pages_manage_metadata` izinlerinin verildiğini kontrol et |
| Bağlantı süresi doldu | Token expire oldu | `Dashboard → User → Social Media` → Disconnect → Reconnect |
| Automation tetiklenmiyor | Status Live değil | Automation status'ünü **Live** yap |
| Belirli bir post için çalışmıyor | Post ID yanlış | Automation'da seçilen post ID'sini kontrol et |
