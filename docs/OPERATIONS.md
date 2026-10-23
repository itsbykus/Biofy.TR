# İşletim kılavuzu

## Cron

**Coolify:** konteyner içinde `schedule:work` çalışır; aşağıdakilere gerek yoktur.

Paylaşımlı hostingde cron **zorunlu değildir**. Cron yokken: zaman ayarlı bloklar, paket değişiklikleri ve analitik zaten istek anında çalışır; temizlik görevleri ise bir yönetici paneli açtığında (en fazla 15 dakikada bir, hafif mod) çalışır. Zamanlanmış yedekler ve alan adı yeniden kontrolleri için cron gerekir.

Görevler (`biofy:maintenance`, her biri sınırlı partiler halinde): eski analitik olaylarını, günlük tuzları, güvenlik kayıtlarını, API loglarını, süresi dolan yönlendirmeleri ve token'ları silme; bekleme süresi dolan hesap silme taleplerini işleme; doğrulanmış alan adlarından 5 tanesini yeniden kontrol etme; zamanı gelmişse yedek alma.

**Seçenek 1: sistem cron'u (önerilen)**

```
* * * * * /usr/local/bin/php /home/kullanici/biofy/artisan schedule:run >> /dev/null 2>&1
```

veya doğrudan 15 dakikada bir:

```
*/15 * * * * /usr/local/bin/php /home/kullanici/biofy/artisan biofy:maintenance >> /dev/null 2>&1
```

PHP yolu hostinge göre değişir (`/opt/cpanel/ea-php84/root/usr/bin/php` gibi). Komut satırı PHP sürümünün de 8.3+ olduğundan emin olun.

**Seçenek 2: URL cron'u** (yalnızca "URL çağır" sunan hostlar veya harici servisler)

```
*/15 * * * * wget -q -O /dev/null "https://biofy.tr/cron/<CRON_ANAHTARI>"
```

Anahtar Yönetim → Sistem'de görünür ve yenilenebilir. Uç nokta dakikada 2 istekle sınırlıdır ve yanlış anahtar 404 döndürür. Son çalışma zamanı Sistem sayfasında gösterilir.

## E-posta (SMTP)

Yönetim → Ayarlar → E-posta. Varsayılan sürücü `log`'dur (e-postalar gönderilmez, `storage/logs`'a yazılır).

| Alan | Örnek |
|---|---|
| Sürücü | `smtp` |
| Sunucu / port | `mail.alanadi.com` / `587` (STARTTLS) veya `465` (SSL) |
| Şifreleme | `tls` (587), `ssl` (465), `none` (önerilmez) |
| Gönderen | `no-reply@alanadi.com` — SMTP hesabıyla aynı alan adında olmalı |

"Bağlantıyı test et" yalnızca bağlantı ve kimlik doğrulamayı dener; "Test e-postası gönder" gerçek bir e-posta yollar. Teslim edilebilirlik için alan adınızda SPF, DKIM ve DMARC kayıtlarını yapılandırın. Şablonlar (TR/EN) Yönetim → E-posta şablonları'ndan düzenlenir; değişkenler `{{ name }}` biçimindedir ve escape edilir.

E-postalar istek içinde gönderilir (kuyruk yok). SMTP hatası kullanıcının işlemini bozmaz; hata (sırlar temizlenerek) loglanır.

## Google ile giriş

1. [Google Cloud Console](https://console.cloud.google.com/) → API'ler ve Hizmetler → OAuth izin ekranı (External, uygulama adı, destek e-postası, yetkili alan adı).
2. Kimlik bilgileri → OAuth istemci kimliği → **Web uygulaması**.
3. Yetkilendirilmiş yönlendirme URI'si: `https://<site>/auth/google/callback` (Yönetim → Ayarlar → Google sayfasında birebir gösterilir).
4. Client ID ve Client Secret'ı yönetim paneline girin ve "Google ile giriş"i açın. Secret şifreli saklanır.

Davranış: yalnızca Google'ın doğruladığı e-postalar kabul edilir. Aynı e-posta ile yerel bir hesap varsa **otomatik birleştirme yapılmaz**; kullanıcı önce parolasıyla giriş yapıp Güvenlik sayfasından Google'ı bağlamalıdır. Yeni kullanıcı Google ile gelirse kullanıcı adı seçme adımına yönlendirilir.

> Bu entegrasyon testlerde Socialite sahte sağlayıcısıyla doğrulanmıştır; gerçek Google hesabıyla uçtan uca deneme, kendi Client ID'nizle kurulumdan sonra yapılmalıdır.

## Özel alan adları ve SSL

1. Yönetim → Ayarlar → API ve alan adları: CNAME hedefi (ör. `biofy.tr`) ve/veya A kaydı IP'si girin.
2. Kullanıcı panelden alan adını ekler; uygulama bir TXT kaydı üretir: `_biofy-verify.<alan-adı>` → `biofy-verify=<token>`.
3. Kullanıcı DNS'e TXT ve CNAME/A kaydını ekleyip "Doğrula"ya basar. Uygulama TXT kaydını ve yönlendirmeyi kontrol eder; ardından alan adına HTTPS ile `/.well-known/biofy-domain-check` isteği yaparak sertifikayı ve sunucuya gerçekten ulaşıldığını doğrular.
4. Sunucu tarafında alan adının siteye bağlanması ve sertifika üretilmesi **hosting panelinde** yapılır ([SHARED_HOSTING.md](SHARED_HOSTING.md#müşteri-alan-adları-için-sunucu-ayarı)). Uygulama SSL sertifikası üretmez; HTTPS çalışmıyorsa durumu dürüstçe "SSL bekleniyor" olarak gösterir. "HTTPS zorunlu" kapalıysa yalnızca DNS doğrulamasıyla HTTP üzerinden yayına alınabilir (önerilmez).

Bir alan adı aynı anda tek bir hesaba bağlı olabilir. IDN (Türkçe karakterli) alan adları v1'de desteklenmez; punycode biçimi (`xn--...`) girilebilir.

## Yedekleme ve geri yükleme

Yönetim → Yedekler.

- **İçerik:** tüm uygulama tabloları (oturum/cache/kuyruk tabloları hariç) tablo başına JSON-lines, isteğe bağlı olarak `public/media` ve bir manifest (sürüm, tablo sayıları, sağlama toplamları).
- **Şifreleme:** açıksa libsodium `secretstream` ile, `APP_KEY`'den türetilen anahtarla şifrelenir. **`APP_KEY` olmadan şifreli yedek açılamaz** — `.env`'nin bir kopyasını güvenli bir yerde tutun.
- **Konum:** `storage/app/private/backups` (web'den erişilemez, ayrıca `Require all denied`). Son N yedek saklanır (ayarlanabilir).
- **İndirme / yükleme:** parola onayı gerekir. Başka bir sunucudan alınmış yedek yüklenebilir (aynı `APP_KEY` gerekir).
- **Geri yükleme:** önce doğrulama (manifest, sağlama toplamları, sürüm), sonra otomatik `pre_restore` güvenlik yedeği, ardından şema migration'larla sıfırdan kurulur ve satırlar parametreli sorgularla eklenir. Arşivden hiçbir SQL veya PHP kodu çalıştırılmaz. Geri yükleme sonrası tüm oturumlar kapanır.
- **Zamanlanmış yedek:** Ayarlar → Yedekler → günlük/haftalık (cron gerektirir).
- **Sınırlar:** büyük veri ve medya klasörlerinde PHP `max_execution_time` aşılabilir. Bu durumda medyayı yedeğe katmayıp `public/media` klasörünü FTP ile ayrıca yedekleyin veya hosting panelinin yedek aracını kullanın.

Sunucu dışı kopya önerilir: yedeği düzenli olarak indirin.

## Bakım modu ve duyurular

- Yönetim → Sistem → **Bakım modu**: ziyaretçilere ve kullanıcılara bakım sayfası gösterilir; yöneticiler çalışmaya devam eder.
- **Duyuru**: panelin üstünde gösterilen TR/EN mesaj.

## Loglar ve izleme

| Kaynak | Yer |
|---|---|
| Uygulama hataları | `storage/logs/laravel-YYYY-MM-DD.log` (14 gün) |
| Kurulum | `storage/logs/installer.log` |
| Yönetici işlemleri | Yönetim → Denetim kaydı |
| Güvenlik olayları | Yönetim → Güvenlik olayları (başarısız girişler, 2FA, parola sıfırlama, askıya alma…) |
| Sistem sağlığı | Yönetim → Sistem (PHP, eklentiler, disk, yazma izinleri, cron, e-posta, bekleyen migration, hassas dosya testi) |

## Production kontrol listesi

- [ ] HTTPS aktif, `APP_URL` `https://` ile başlıyor
- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Hassas dosya erişim testi tamamen yeşil
- [ ] Superadmin hesabında 2FA açık; "yöneticiler için 2FA zorunlu" açık
- [ ] SMTP yapılandırıldı ve test e-postası ulaştı
- [ ] Cron (sistem veya URL) çalışıyor, Sistem sayfasında son çalışma zamanı görünüyor
- [ ] İlk yedek alındı, indirildi; `.env` kopyası güvenli yerde
- [ ] Hukuki sayfalar şirket bilgileriyle güncellendi ve hukuk incelemesinden geçti
- [ ] Marka (logo, favicon, renkler) ve iletişim e-postası ayarlandı
- [ ] Proxy/Cloudflare kullanılıyorsa `TRUSTED_PROXIES` ayarlandı
- [ ] HTTPS kalıcıysa HSTS açıldı (Ayarlar → Güvenlik)

## Bilinen sınırlamalar (v1.0.0)

- Ödeme alma yoktur; paketler yönetici tarafından atanır. Fiyatlar bilgilendirme amaçlıdır.
- Alt klasöre kurulum (`site.com/biofy`) desteklenmez; alan adı veya alt alan adı kullanın.
- IDN (Türkçe karakterli) alan adları desteklenmez.
- SSL sertifikası uygulama tarafından üretilmez; hosting paneli veya sunucu sağlar.
- Kuyruk kullanılmadığından toplu e-posta gönderimi yoktur; e-postalar tek tek, istek içinde gönderilir.
- Kullanıcı taklidi (impersonation) bulunmaz.
- `intl` eklentisi gerekmez; tarih/sayı biçimlendirme uygulama içinde yapılır.
- Yedek geri yükleme yalnızca aynı `APP_KEY` ile yapılabilir (şifreli yedekler ve şifreli ayarlar için).
- Çok büyük veri setlerinde yedekleme web isteği süresini aşabilir (yukarıya bakın).
- Ülke analitiği yalnızca Cloudflare `CF-IPCountry` başlığı açıkça etkinleştirildiğinde vardır; IP'den ülke tahmini yapılmaz.
