# Testler

## Otomatik testler (PHPUnit)

```bash
composer install
npm ci && npm run build          # görünüm testleri derlenmiş Vite manifest'ini kullanır
cp .env.example .env && php artisan key:generate
php vendor/bin/phpunit
```

Testler bellek içi SQLite ile çalışır (`phpunit.xml`); harici servis gerekmez. Google OAuth, DNS, HTTPS ve SMTP sahte (fake) uygulamalarla test edilir.

| Dosya | Kapsam |
|---|---|
| `Unit/TotpTest` | RFC 6238 test vektörleri, zaman penceresi |
| `Unit/SafeUrlTest` | `javascript:`, `data:`, `//`, kimlik bilgili ve kontrol karakterli URL'lerin reddi |
| `Unit/ThemeSettingsTest` | Tema ayarı temizleme, CSS değişkenleri |
| `Feature/AuthenticationTest` | Kayıt, giriş, hız sınırı, e-posta doğrulama, parola sıfırlama, 2FA, oturum yenileme, askıya alma |
| `Feature/GoogleOAuthTest` | Doğrulanmamış e-posta reddi, otomatik birleştirme yapılmaması, Google kimliğiyle (sub) giriş, yapılandırılmamışken kapalı olması |
| `Feature/ProfileLifecycleTest` | Oluşturma, adres kuralları/rezerve kelimeler, yönlendirmeler, yayınla/geri al/arşivle, tenant izolasyonu |
| `Feature/EditorTest` | Blok CRUD ve sıralama, güvensiz URL reddi, gelişmiş blok/zamanlama yetkisi, cron gerektirmeyen zamanlama, eşzamanlılık (409), medya sahipliği, sosyal bağlantılar, tema seçimi |
| `Feature/EntitlementsTest` | Çözümleme sırası, limitler, paket düşürme, askıya alınan hesap |
| `Feature/AnalyticsTest` | Bot filtreleme, tekrar sayımı önleme, önizlemenin sayılmaması, tıklama yönlendirmesi, CSV (formül enjeksiyonu), paket özellikleri, tenant izolasyonu |
| `Feature/CustomDomainTest` | DNS/HTTPS doğrulama (sahte), host header güvenliği, tek hesaba bağlılık |
| `Feature/AdminTest` | Yetkiler, son superadmin koruması, paket/özellik yönetimi, ayarlar, denetim kaydı |
| `Feature/ApiTest` | Token yetki kapsamları, hız sınırı, tenant izolasyonu (404), hata zarfı |
| `Feature/BackupTest` | Yedek oluşturma, doğrulama, erişim koruması, geri yükleme ve güvenlik kopyası |
| `Feature/SecurityTest` | CSRF, kayıtlı/yansıyan XSS, SQL enjeksiyonu, dosya yükleme saldırıları ve yeniden kodlama, kota, açık yönlendirme, `.htaccess` korumaları, bakım modu, cron anahtarı, production debug kapalı |
| `Feature/PageRenderTest` | Genel site, kullanıcı paneli, yönetim ve hata sayfalarının hatasız render edilmesi |
| `Feature/TranslationCompletenessTest` | TR/EN anahtar eşitliği |
| `Unit/InstallerTest` | Kurulum sihirbazı: kaynak kodu arşivinin (vendor/build yok) tanınması, Vite manifestindeki her dosyanın kontrolü, `release-manifest.json` ile eksik FTP yüklemesinin tespiti (yerleşim B dahil), `.env` üretimi (`APP_DEBUG=false`, güvenli tırnaklama, Laravel'in dotenv ayrıştırıcısıyla okuma), yarıda kalan denemede `APP_KEY`'in korunması, yalnızca hesapsız yarım kurulumun devam ettirilebilmesi; `verify-release.php`'nin kaynak arşivini reddetmesi |

| `Feature/SiteCustomizationTest` | Footer ayarları (görünüm, logo yüksekliği, marka yazısı, sütun başlıkları, telif metni, sosyal ikonlar), logo yüksekliği doğrulaması, ana sayfa bölümleri/metinleri ve güvenli ikinci buton bağlantısı, işareti kaldırılan onay kutularının kaydedilmesi, geçerli JSON-LD, e-posta ikonunun çizgi ikonu olarak çizilmesi |
| `Feature/SharedHostingLayoutTest` | Yerleşim C (paketin tamamı web kökünde): hiçbir rota uygulama klasörü adıyla (`lang`, `config`, `vendor`…) başlamaz, bu adlar sayfa adresi olarak rezervedir, liste `build-release.php` ile tutarlıdır |

CI ayrıca tüm PHP dosyalarında sözdizimi kontrolü (`php -l`) ve `composer validate` çalıştırır.

## Dağıtım testleri (GitHub Actions)

`.github/workflows/deployment-checks.yml`, her `main` push'unda iki dağıtım yöntemini gerçek sunucu yazılımıyla uçtan uca dener:

**Coolify (Docker)** — `scripts/ci/docker-smoke.sh`
- `docker-compose.yml` ile imaj derlenir; önünde Coolify'ın Traefik'i yerine HTTPS sonlandıran Caddy çalışır.
- İlk açılışta ortam değişkenleriyle kurulum, health check, `Secure`/`HttpOnly` çerez, https URL üretimi, superadmin girişi, görsel yükleme.
- İmaj içeriği: `vendor/autoload.php`, `public/build/manifest.json` ve manifestteki her dosya var; `.env`, `.git`, `node_modules`, `tests`, geliştirme paketleri yok; `vendor/` `--no-dev` kurulmuş.
- Hassas yolların (`.env`, `vendor`, `storage`…) erişilemezliği, `media/` içinde PHP çalışmaması, bilinmeyen Host reddi, kodun web kullanıcısı için salt okunur olması.
- Konteynerler yeniden derlenip oluşturulduktan sonra veritabanı ve yüklenen görselin korunması.
- Yeni migration içeren bir sonraki açılışta önce yedek alınması, sonra migration'ın uygulanması.

**Paylaşımlı WordPress hosting** — `scripts/ci/shared-hosting-smoke.sh`
- `scripts/build-release.php` ile FTP paketi üretilir ve `scripts/verify-release.php` ile doğrulanır (ayrıntı: [DEPLOYMENT.md](DEPLOYMENT.md#sürüm-paketi-üretme-paylaşımlı-hosting-için)).
- GitHub'ın "Source code" arşivi yüklendiğinde sihirbazın doğru paketi söyleyip kuruluma izin vermemesi ve doğrulayıcının bu arşivi reddetmesi.
- Apache + PHP 8.3 + MariaDB 10.6 üzerinde çalışan bir WordPress kurulumu hazırlanır (PHP, cPanel'deki gibi hesap kullanıcısıyla çalışır).
- Paket **FTP ile** (lftp → pyftpdlib) yüklenir; sunucuda hiçbir komut çalıştırılmaz.
- Web sihirbazıyla üç yerleşimde kurulum: A (`public_html` dışında), B (yalnızca public dosyalar alt alan adı klasöründe), C (paketin tamamı WordPress'in `public_html`'i içinde).
- Her yerleşimde giriş, yönetim paneli, görsel yükleme, `media/` içinde PHP çalışmaması, hassas dosyaların (ve C'de WordPress alan adı üzerinden) erişilemezliği, `.env` izinleri, CLI/cron komutu.
- Kurulumlardan sonra WordPress'in ana sayfası, kendi URL yönlendirmesi ve giriş sayfasının çalışmaya devam etmesi.

Yerelde çalıştırmak için Docker gerekir: `bash scripts/ci/docker-smoke.sh` ve (önce `php scripts/build-release.php`, `lftp` kurulu olmalı) `bash scripts/ci/shared-hosting-smoke.sh`.

## Bu testlerin kapsamadığı şeyler

- Gerçek bir **Coolify** örneği ve GitHub webhook'u ile otomatik dağıtım (Coolify'ın kendisi CI'da yok; imaj, compose dosyası ve proxy davranışı test edilir).
- Gerçek bir ticari hosting paneli (cPanel/Plesk), LiteSpeed sunucu ve AutoSSL.
- Gerçek Google OAuth, gerçek SMTP teslimatı, gerçek DNS kayıtları ve müşteri alan adları için sertifika üretimi.

Bunlar kurulumdan sonra kendi hesaplarınızla doğrulanmalıdır ([OPERATIONS.md](OPERATIONS.md#production-kontrol-listesi)).
