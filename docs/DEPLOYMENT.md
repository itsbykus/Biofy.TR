# Dağıtım

Biofy.TR iki dağıtım yöntemini destekler. İkisi de aynı kod tabanını ve aynı özellikleri kullanır; yalnızca kurulum ve güncelleme biçimi farklıdır.

| | Paylaşımlı (WordPress) hosting | Coolify |
|---|---|---|
| Ne yüklenir | `biofy-tr-<sürüm>.zip` (FTP) | GitHub deposu (`main` dalı) |
| Sunucuda gereken | PHP 8.3+, MySQL/MariaDB, Apache/LiteSpeed | Coolify'ın yönettiği Docker sunucusu |
| İlk kurulum | Web sihirbazı (kurulum anahtarı + veritabanı + superadmin) | Ortam değişkenleri, otomatik |
| Yapılandırma | Sihirbazın yazdığı `.env` | Coolify Environment Variables (`.env` dosyası yok) |
| Güncelleme | Dosyaları FTP ile yükle → panelde imza/bütünlük kontrolü → "Güncellemeyi uygula" | `git push` → otomatik derleme → yedek + migration → health check |
| Kalıcı veri | Hosting dosya sistemi + veritabanı | `storage`, `media`, `mysql` volume'leri |
| HTTPS | Hosting paneli (AutoSSL) | Coolify proxy (Let's Encrypt) |
| Cron | Hosting cron'u veya URL cron (isteğe bağlı) | Konteyner içinde `schedule:work` |
| Rehber | [SHARED_HOSTING.md](SHARED_HOSTING.md), [INSTALLATION.md](INSTALLATION.md), [UPDATE.md](UPDATE.md) | [COOLIFY.md](COOLIFY.md) |

Ortak işletim konuları (SMTP, Google girişi, yedekler, özel alan adları, production kontrol listesi): [OPERATIONS.md](OPERATIONS.md).

## Sürüm paketi üretme (paylaşımlı hosting için)

Geliştirici bilgisayarında (PHP 8.3+, Composer, Node 22):

```bash
php scripts/build-release.php     # → dist/biofy-tr-<sürüm>.zip ve .sha256
```

Paket `vendor/` ve derlenmiş `public/build/` içerir; `.env`, testler, geliştirme bağımlılıkları, `node_modules` ve kullanıcı yüklemeleri içermez (betik bunları bulursa durur). GitHub Actions'taki "Deployment checks" iş akışı da her `main` push'unda paketi üretir, test eder ve 14 gün süreyle indirilebilir tutar; bu test paketi her seferinde geçici bir anahtarla imzalanır.

**Resmi sürümler:** `config/biofy.php` sürümüyle aynı bir etiket (`v1.0.0` gibi) gönderildiğinde "Release" iş akışı çalışır:

- Docker imajını GitHub Packages'a yayımlar: `ghcr.io/itsbykus/biofy.tr:<sürüm>`, `:<major.minor>`, `:latest` (Actions → Release → "Run workflow" ile mevcut bir etiketin imajı yeniden yayımlanabilir)
- FTP paketini kalıcı anahtarla (`BIOFY_RELEASE_SIGNING_KEY` repo secret'ı) imzalar ve `.sha256` dosyasıyla birlikte GitHub Release'e ekler.

Etiket ile `config/biofy.php` sürümü uyuşmazsa veya secret yoksa iş akışı durur. Anahtarın yedeği bakımcının bilgisayarındadır (`~/.biofy-devtools/release-signing.key`); kaybolursa yeni paketler mevcut kurulumlarda `bad_signature` verir ([UPDATE.md](UPDATE.md#geliştiriciler-için-sürüm-üretme)).

## Gizli bilgiler

- Depoda ve pakette gerçek parola, anahtar veya `.env` bulunmaz (`.gitignore`, `.dockerignore` ve paketleme betiğindeki kontroller).
- Paylaşımlı hostingde sırlar sunucudaki `.env` dosyasında ve şifreli olarak veritabanında; Coolify'da ortam değişkenlerinde tutulur.
- `APP_KEY` her iki yöntemde de bir kez üretilir ve değiştirilmez; yedeğini güvenli bir yerde saklayın.
