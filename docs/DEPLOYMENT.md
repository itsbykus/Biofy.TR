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

## Kim ne çalıştırır

| | Geliştirici / GitHub Actions (paketi üretir) | Hosting kullanıcısı (paketi kurar) |
|---|---|---|
| Composer, npm, Node.js | Evet: `composer install --no-dev`, `npm ci`, `npm run build` | **Hayır**, hiçbir zaman |
| Terminal / SSH | Evet | Hayır (FTP veya dosya yöneticisi + tarayıcı) |
| Çıktı | `biofy-tr-<sürüm>.zip` (+ `.sha256`) | Çalışan kurulum (web sihirbazı) |

Coolify ise paketi hiç kullanmaz: `Dockerfile` bağımlılıkları ve arayüz dosyalarını **imajın içinde** kendi derler. Depodaki `vendor/`, `public/build/` ve `dist/` `.dockerignore` ile imaj bağlamının dışındadır; iki yöntem birbirinin çıktısına bağlı değildir.

## Sürüm paketi üretme (paylaşımlı hosting için)

Geliştirici bilgisayarında (PHP 8.3+ ve `sodium`, `zip` eklentileri, Composer 2, Node 22):

```bash
php scripts/build-release.php     # → dist/biofy-tr-<sürüm>.zip ve .sha256
# Composer PATH'te değilse: COMPOSER_BIN="php /yol/composer.phar" php scripts/build-release.php
```

Betik sırasıyla: `npm ci` (package-lock.json'a göre) → ikonlar → `npm run build` → çalışma dosyalarını temiz bir klasöre kopyalar → `composer validate` → `composer install --no-dev --prefer-dist --optimize-autoloader` (composer.lock'a göre) → `composer check-platform-reqs --no-dev` → web erişim engelleri (`.htaccess`) → SHA-256 manifesti + Ed25519 imzası → ZIP + `.sha256` → **`scripts/verify-release.php`**.

`scripts/verify-release.php` herhangi bir paketi de (ör. GitHub'dan indirilen) denetler ve bir kontrol başarısız olursa çıkış kodu 1 verir; derleme betiği bu durumda ZIP'i ve `.sha256` dosyasını siler, yani başarısız bir paket `dist/` içinde kalmaz:

| Kontrol | Ayrıntı |
|---|---|
| Arşiv | ZIP tutarlı; tek üst klasör `biofy-tr-<sürüm>/`, sürüm `config/biofy.php` ile aynı |
| Kurulum dosyaları | `public/index.php`, `.htaccess` dosyaları, `install/*`, `artisan`, `bootstrap/`, `config/`, `routes/`, migration'lar, görünümler, belgeler, çalışma klasörleri |
| `vendor/` | `vendor/autoload.php` var; composer.lock'taki her production paketi kurulu; `--no-dev`; hiçbir geliştirme paketi yok |
| `public/build/` | `manifest.json` geçerli; manifestin referans verdiği her JS/CSS/font dosyası pakette |
| Sırlar | `.env` (yalnızca `.env.example` serbest), `auth.json`, anahtar/sertifika, SQLite, log, yedek, `storage/` verisi, kurulum kilidi/anahtarı, `node_modules`, `tests`, geliştirme dosyaları yok; metin dosyalarında APP_KEY, özel anahtar ve bilinen token biçimleri aranır; imzalama anahtarının içeriği hiçbir dosyada yok |
| İmza | `release-manifest.json` imzası geçerli; manifest ile ZIP içeriği birebir aynı (eksik, fazla veya değişmiş dosya yok). `--public-key=<anahtar>` ile beklenen yayın anahtarı da zorunlu kılınır. |
| Özet | `.sha256` dosyası var ve ZIP'in gerçek SHA-256 değeriyle eşleşiyor |

```bash
php scripts/verify-release.php dist/biofy-tr-1.0.1.zip
php scripts/verify-release.php indirilen/biofy-tr-1.0.1.zip --public-key=mUsYHBi0/DkwZChdck9pa7Hv2WzbAE6CV/6XXniqqEI=
```

GitHub Actions'taki "Deployment checks" iş akışı da her `main` push'unda paketi üretir, doğrular, FTP ile simüle edilmiş bir hostinge kurar ve 14 gün süreyle indirilebilir tutar; bu test paketi her seferinde geçici bir anahtarla imzalanır (kurulumlar için **resmi sürüm** değildir).

**ZIP repoda tutulmaz.** `dist/` `.gitignore`'dadır: her sürüm ~11 MB ve 8.800+ dosyadır, Git geçmişini büyütür ve kaynak kodun kopyasıdır. Resmi paketler yalnızca GitHub Releases'ta (Assets) yayımlanır; repoda yalnızca onları üreten betikler bulunur.

**Resmi sürümler:** `config/biofy.php` sürümüyle aynı bir etiket (`v1.0.0` gibi) gönderildiğinde "Release" iş akışı çalışır:

- Docker imajını GitHub Packages'a yayımlar: `ghcr.io/itsbykus/biofy.tr:<sürüm>`, `:<major.minor>`, `:latest` (Actions → Release → "Run workflow" ile mevcut bir etiketin imajı yeniden yayımlanabilir)
- FTP paketini kalıcı anahtarla (`BIOFY_RELEASE_SIGNING_KEY` repo secret'ı) imzalar, doğrular (kurulumların sabitlediği açık anahtar `release.yml` içindeki `RELEASE_PUBLIC_KEY` ile karşılaştırılır) ve `.sha256` dosyasıyla birlikte GitHub Release'e ekler. Ardından yüklenen varlıkları kullanıcıların indireceği gibi geri indirip yeniden doğrular. Sürüm notları, GitHub'ın otomatik "Source code" arşivlerinin hostingde kullanılmaması gerektiğini belirtir.

Etiket ile `config/biofy.php` sürümü uyuşmazsa, secret yoksa veya doğrulama başarısız olursa iş akışı durur. Yayımlanmış bir sürümün dosyaları değiştirilmez; düzeltme her zaman yeni bir sürüm numarasıyla (`1.0.0` → `1.0.1`) yayımlanır.

**Sürüm çıkarma adımları:** `config/biofy.php` → `version` ve `CHANGELOG.md` güncellenir, `main`'e push edilir, "Tests" ve "Deployment checks" yeşil olduktan sonra etiket gönderilir: `git tag v1.0.1 && git push origin v1.0.1`. Anahtarın yedeği bakımcının bilgisayarındadır (`~/.biofy-devtools/release-signing.key`); kaybolursa yeni paketler mevcut kurulumlarda `bad_signature` verir ([UPDATE.md](UPDATE.md#geliştiriciler-için-sürüm-üretme)).

## Gizli bilgiler

- Depoda ve pakette gerçek parola, anahtar veya `.env` bulunmaz (`.gitignore`, `.dockerignore` ve paketleme betiğindeki kontroller).
- Paylaşımlı hostingde sırlar sunucudaki `.env` dosyasında ve şifreli olarak veritabanında; Coolify'da ortam değişkenlerinde tutulur.
- `APP_KEY` her iki yöntemde de bir kez üretilir ve değiştirilmez; yedeğini güvenli bir yerde saklayın.
