# TikTok Automation Kurulum Kılavuzu

## Gereksinimler

- TikTok Developer hesabı → [developers.tiktok.com](https://developers.tiktok.com)
- **TikTok Business veya Creator hesabı** (Personal hesap desteklenmez)
- Onaylanmış TikTok Developer App
- HTTPS destekli, public erişilebilir domain (webhook için)
- Admin panelinde TikTok credentials girilmiş olmalı
- Cron job yapılandırılmış olmalı (`php artisan schedule:run`)

> **⚠️ Önemli Kısıtlama:** TikTok, üçüncü taraf uygulamalara **DM gönderme izni vermez**. Bu nedenle TikTok automation yalnızca **public yorum yanıtlama** destekler.

---

## 1. TikTok Developer App Oluşturma

1. [developers.tiktok.com](https://developers.tiktok.com) adresine git
2. Sağ üstten giriş yap → **Manage Apps** tıkla
3. **Create app** tıkla
4. Şu bilgileri doldur:

| Alan | Değer |
|------|-------|
| App name | Projenin adı |
| App description | Kısa açıklama |
| Category | Business |
| Platform | Web |

5. **Submit** tıkla → App onay sürecine girer

> TikTok app'leri onay gerektirir. Temel scope'lar hızlı onaylanır, hassas olanlar daha uzun sürebilir.

---

## 2. App Credentials

App onaylandıktan sonra **App Detail** sayfasına git:

| Alan | Nerede | Admin Panel Alanı |
|------|--------|-------------------|
| Client Key (App ID) | App Detail → Client Key | `TIKTOK_APP_ID` ve `TIKTOK_APP_KEY` |
| Client Secret | App Detail → Client Secret | `TIKTOK_APP_SECRET` |

Bu değerleri admin paneline gir:
`Dashboard → Admin → Social Media Settings → TikTok`

> **Not:** `TIKTOK_APP_ID` ve `TIKTOK_APP_KEY` alanlarının ikisine de aynı **Client Key** değerini gir.

---

## 3. OAuth Redirect URI

**App Detail → Redirect URI** alanına ekle:

```
https://experiment.liquid-themes.com/social-media/oauth/callback/tiktok
```

**Save** tıkla.

---

## 4. İzinler (Scopes)

**App Detail → Scopes** bölümünden şu scope'ları ekle ve onay iste:

| Scope | Açıklama | Zorunlu mu |
|-------|----------|-----------|
| `user.info.basic` | Temel kullanıcı bilgileri | ✅ Evet |
| `video.list` | Video listesi — postları getirmek için | ✅ Evet |
| `comment.list` | Yorum listesi okuma | ✅ Evet |
| `comment.create` | Yorum yanıtlama (reply) | ✅ Evet |

> **DM scope yok:** TikTok DM API (`message.send` vb.) kamuya açık değildir. Sadece yorum yanıtlama yapılabilir.

---

## 5. Domain Verification (Zorunlu)

TikTok, webhook kullanmadan önce domain doğrulaması ister.

1. **App Detail → Domain Verification** bölümüne git
2. Doğrulama dosyasını indir (örn: `tiktokXXXXXXXX.txt`)
3. Admin panelinde yükle:
   `Dashboard → Admin → Social Media Settings → TikTok → Verification File`
4. TikTok Developer Portal'da **Verify** tıkla
5. Domain doğrulandı mesajı görünmeli

---

## 6. Webhook Kurulumu

### 6.1 Webhook URL Ekle

**App Detail → Event Subscriptions** veya **Webhook** bölümüne git:

| Alan | Değer |
|------|-------|
| Endpoint URL | `https://experiment.liquid-themes.com/social-media/automation/webhook/tiktok` |

**Save** tıkla.

TikTok, URL'yi doğrulamak için challenge isteği gönderir:
- POST isteği içinde `challenge` parametresi gelir
- Sistem aynı `challenge` değerini JSON olarak döner
- Doğrulama başarılı olursa webhook kaydedilir

> Bu doğrulama otomatik yapılır, ekstra bir şey yapman gerekmez.

### 6.2 Event Subscription — Hangi Olayları Dinleyeceğiz

| Event | Açıklama | Gerekli mi |
|-------|----------|-----------|
| **`comment.create`** | Yeni yorum oluşturulduğunda | ✅ Evet |

> Sadece `comment.create` yeterlidir.

---

## 7. Webhook Nasıl Çalışır?

TikTok webhook şu payload formatını kullanır:

```json
{
  "event": "comment.create",
  "data": {
    "video_id": "7123456789012345678",
    "comment_id": "7123456789012345679",
    "user_id": "6123456789012345678",
    "username": "kullanici_adi",
    "display_name": "Kullanıcı Adı",
    "text": "Bu videodaki ürünün fiyatı nedir?"
  }
}
```

```
Biri videoya yorum yazar
        ↓
TikTok → Webhook URL'ye POST isteği gönderir
        ↓
/social-media/automation/webhook/tiktok çağrılır
        ↓
event = "comment.create" kontrol edilir
        ↓
Eşleşen Live automation'lar bulunur
        ↓
Bekleyen automation kaydı veritabanına yazılır
        ↓
Cron job bekleyen kaydı işler (her dakika)
        ↓
/v2/comment/reply/create/ API'si ile yorum yanıtlanır
```

---

## 8. TikTok Hesabını Sisteme Bağlama

1. `Dashboard → User → Social Media` sayfasına git
2. TikTok satırında **Connect** tıkla
3. TikTok hesabına giriş yap
4. İstenen scope'ları onayla

---

## 9. DM Kısıtlaması — Önemli

| Özellik | Durum |
|---------|-------|
| Public yorum yanıtlama | ✅ Desteklenir |
| DM gönderme | ❌ TikTok API izin vermiyor |
| Özel mesaj | ❌ Desteklenmez |

Automation oluştururken:
- **DM / Text / Button / Image action** → TikTok için çalışmaz
- **Public Reply action** → Desteklenir, yorum altına yanıt olarak görünür

---

## 10. Automation Oluşturma

1. `Dashboard → User → Social Media Automation → Create` sayfasına git
2. **TikTok** hesabını seç
3. **Trigger** ayarla:
   - `All Posts` → Tüm videolara gelen yorumlara
   - `Specific Post` → Seçilen videoya gelen yorumlara
4. **Keyword** filtresi ekle (opsiyonel):
   - Örn: "fiyat", "link", "nereden" gibi kelimeler içeren yorumlara tepki ver
5. **Public Reply** action ekle (DM action TikTok'ta çalışmaz)
6. Status'ü **Live** yap

---

## 11. Test Etme

### Adım 1: Cron Job'ın Yapılandırıldığını Doğrula
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Adım 2: Yorum Yap
TikTok videona yorum yaz.

### Adım 3: Log'ları Kontrol Et
```bash
tail -f storage/logs/laravel.log | grep -E "TikTok|tiktok|webhook|automation"
```

### Adım 4: Automation Log'larını Kontrol Et
`Dashboard → User → Social Media Automation` → İlgili automation'ın **Logs** sekmesi.

---

## Sık Karşılaşılan Sorunlar

| Sorun | Olası Neden | Çözüm |
|-------|-------------|-------|
| Webhook challenge başarısız | URL erişilemiyor | URL'nin public ve HTTPS olduğunu doğrula |
| Domain verification başarısız | Dosya yüklenmemiş | Admin panelinden verification dosyasını yükle |
| Yorum yakalanmıyor | `comment.create` subscribe edilmemiş | Event subscription'da `comment.create` seç |
| Yanıt gönderilemiyor | `comment.create` scope'u yok | App scope'larına `comment.create` ekle ve onaylat |
| DM çalışmıyor | TikTok kısıtlaması | DM yerine Public Reply action kullan |
| App onaylanmadı | İnceleme bekliyor | TikTok onay sürecini bekle |
| Automation işlenmiyor | Cron job yapılandırılmamış | Cron ekle: `* * * * * cd /path && php artisan schedule:run` |
