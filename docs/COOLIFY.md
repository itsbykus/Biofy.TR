# Coolify ile otomatik dağıtım

Depodaki `Dockerfile` ve `docker-compose.yml` ile Coolify, ana dala (`main`) yapılan her push'ta imajı yeniden derler ve yayına alır. Uygulama ve MySQL aynı compose dosyasında çalışır; veriler kalıcı volume'lerdedir.

## Neler hazır

| Konu | Nasıl |
|---|---|
| İmaj | PHP 8.4 + Apache (mod_php), çok aşamalı derleme: Vite/Tailwind (Node 22, `npm ci`) → Composer `--no-dev` (composer.lock) → çalışma imajı. İmajda `.env`, test, `node_modules`, geliştirme paketi veya geliştirme dosyası yok (CI her push'ta imajın içini kontrol eder). |
| FTP paketinden bağımsız | `vendor/` ve `public/build/` imajın içinde derlenir. Depodaki yerel `vendor/`, `public/build/` ve `dist/` (paylaşımlı hosting ZIP'i) `.dockerignore` ile derleme bağlamına hiç girmez; Coolify için paket üretmeniz gerekmez. |
| Veritabanı | `mysql:8.4` servisi, bağlantı `DB_*` değişkenleriyle |
| Kalıcı veri | `biofy-storage` → `storage/` (yedekler, loglar, cache, kurulum kilidi) · `biofy-media` → `public/media/` (yüklenen görseller) · `biofy-mysql` → veritabanı |
| İlk kurulum | Web sihirbazı yerine `biofy:container-setup` komutu: migration + seed + `BIOFY_ADMIN_*` ile superadmin. Web kurulum sihirbazı konteynerde kapalıdır. |
| Güncelleme / migration | Her açılışta bekleyen migration varsa önce `storage/app/private/backups` altına `pre_update` yedeği alınır, sonra `migrate --force` kilitli (tek konteyner) çalışır. Hata olursa konteyner başlamaz; Coolify eski sürümü çalışır durumda bırakır. |
| Laravel cache | Açılışta `config`, `route`, `view`, `event` cache'leri o anki ortam değişkenleriyle yeniden oluşturulur. OPcache açık. |
| Dosya izinleri | Kod `root` sahipli ve web kullanıcısı için salt okunur; yalnızca `storage/`, `bootstrap/cache/`, `public/media/` `www-data`'ya aittir. |
| HTTPS | TLS'i Coolify'ın proxy'si (Traefik) sonlandırır. `TRUSTED_PROXIES=*` ile uygulama `X-Forwarded-*` başlıklarına güvenir; `SESSION_SECURE_COOKIE=true`. |
| Health check | `/up` (Laravel + Apache + PHP), 30 sn aralık, 180 sn başlangıç süresi |
| Zamanlanmış görevler | Konteyner içinde `schedule:work` (temizlik, hesap silme, alan adı kontrolü, zamanlanmış yedek). Kapatmak için `BIOFY_SCHEDULER=false`. |

## Kurulum (bir kez)

1. **Coolify → Projects → + New → Public/Private Repository (with GitHub App)** — GitHub App'i kurun ve `Biofy.TR` deposuna erişim verin. (Deploy key yöntemi de olur, ama otomatik dağıtım için webhook'u elle eklemeniz gerekir.)
2. Depo: `itsbykus/Biofy.TR`, dal: `main`, **Build Pack: Docker Compose**, compose dosyası: `/docker-compose.yml`.
3. **Domains:** `app` servisine alan adını girin, örn. `https://biofy.example.com`. (Uygulama konteyner içinde 80 portunu dinler.) `mysql` servisine alan adı **vermeyin**.
4. **Environment Variables** — en az şunları girin (hepsini "secret/locked" işaretleyebilirsiniz):

   | Değişken | Örnek / açıklama |
   |---|---|
   | `APP_URL` | `https://biofy.example.com` (3. adımdaki alan adıyla aynı, alt klasörsüz) |
   | `APP_KEY` | `base64:` + 32 rastgele baytın base64'ü. Üretmek için: `openssl rand -base64 32` → başına `base64:` ekleyin. **Bir kez belirleyin, asla değiştirmeyin** (kayıtlı sırları ve yedekleri şifreler). |
   | `DB_PASSWORD` | Uzun rastgele parola (`openssl rand -hex 24`) |
   | `DB_ROOT_PASSWORD` | Ayrı bir uzun rastgele parola |
   | `BIOFY_ADMIN_USERNAME` | İlk superadmin kullanıcı adı (`admin`, `root` gibi rezerve kelimeler olmaz) |
   | `BIOFY_ADMIN_EMAIL` | İlk superadmin e-postası |
   | `BIOFY_ADMIN_PASSWORD` | En az 10 karakter, harf + rakam |

   İsteğe bağlı: `APP_NAME` (site adı, varsayılan `Biofy.TR`), `APP_LOCALE` (`tr`/`en`), `BIOFY_TIMEZONE` (`Europe/Istanbul`), `DB_DATABASE`/`DB_USERNAME` (varsayılan `biofy`), `BIOFY_BACKUP_BEFORE_MIGRATE` (`true`), `BIOFY_SCHEDULER` (`true`), `BIOFY_ADMIN_NAME` (`Site Admin`), `TRUSTED_PROXIES` (`*`; Coolify proxy'si tek giriş noktası olduğu için), `SESSION_SECURE_COOKIE` (`true`; yalnızca HTTPS'siz test ortamında `false`), `DB_CONNECTION`/`DB_HOST`/`DB_PORT` (varsayılan: aynı compose dosyasındaki `mysql` servisi; harici veritabanı için değiştirin). Yukarıdakilerle birlikte bu liste `docker-compose.yml`'deki `environment` bölümünün tamamıdır; orada olmayan bir değişken konteynere aktarılmaz.
5. **General → Auto Deploy** açık olsun (GitHub App ile varsayılan olarak açıktır).
6. **Deploy.** İlk derleme birkaç dakika sürer. Loglarda `Installed. Sign in at …/login` görünür; `https://biofy.example.com/login` adresinden giriş yapın.
7. İlk girişten sonra: `BIOFY_ADMIN_PASSWORD` değişkenini silebilirsiniz (yalnızca ilk kurulumda kullanılır), 2FA'yı açın ve [kurulum sonrası adımları](INSTALLATION.md#5-kurulum-sonrası-önerilen-sıra) uygulayın (SMTP, marka, yedek indirme).

## Sonraki dağıtımlar

`main` dalına push → Coolify imajı derler → yeni konteyner açılırken yedek + migration → health check geçince trafik yeni konteynere geçer. Health check geçmezse (ör. migration hatası) yeni sürüm yayına alınmaz; nedeni Coolify'daki dağıtım/konteyner loglarındadır.

Sürüm numarasını (`config/biofy.php → version`) yeni sürümlerde artırın; Yönetim → Sistem sayfası kayıtlı sürümü gösterir. Konteynerde güncelleme adımları otomatik olduğundan panelden "Güncellemeyi uygula" gerekmez.

**Migration kuralı:** Yeni konteyner migration'ı çalıştırırken eski konteyner kısa bir süre trafiğe yanıt vermeye devam eder. Bu yüzden migration'lar geriye uyumlu olmalı (önce sütun ekle, sonraki sürümde eskiyi kaldır).

## Yedekler ve geri dönüş

- Uygulama yedekleri (Yönetim → Yedekler) `biofy-storage` volume'ündedir; **indirip sunucu dışında saklayın**.
- Coolify'ın kendi volume/veritabanı yedekleme özelliklerini de ek koruma olarak kullanabilirsiniz.
- Önceki sürüme dönmek: Coolify'da önceki commit'i yeniden dağıtın; şema değiştiyse Yönetim → Yedekler'den ilgili `pre_update` yedeğini geri yükleyin.
- `APP_KEY` değişirse şifreli yedekler ve kayıtlı sırlar (SMTP parolası vb.) okunamaz.

## Özel alan adları (kullanıcı domainleri)

Kullanıcıların kendi alan adlarının çalışması için Coolify'ın o alan adlarını da bu servise yönlendirmesi ve sertifika alması gerekir: doğrulanan her müşteri alan adını `app` servisinin **Domains** alanına virgülle ekleyip yeniden dağıtın. Uygulama doğrulanmamış alan adlarına gelen istekleri her durumda reddeder. Bu adım şu an elle yapılır (Coolify API ile otomasyon v1 kapsamında değildir).

## Sorun giderme

| Belirti | Neden / çözüm |
|---|---|
| Dağıtım "APP_KEY / APP_URL / DB_PASSWORD … required" ile duruyor | Değişken eksik (4. adım). |
| Log: `APP_KEY is missing or invalid` | Değer `base64:` ile başlamalı ve 32 bayt olmalı. Logda örnek bir değer önerilir. |
| Log: `BIOFY_ADMIN_… is required` | İlk kurulumda superadmin değişkenleri zorunlu. |
| Log: `The database is not empty …` | Başka bir uygulamanın verisi olan veritabanı kullanılıyor; boş veritabanı verin. |
| Log: `Update failed` | Migration hatası; veritabanı "güncellendi" olarak işaretlenmez. Hatayı düzeltip yeniden dağıtın; gerekirse `pre_update` yedeğini geri yükleyin. |
| 404 "bilinmeyen alan adı" sayfası | Açtığınız adres `APP_URL` ile aynı değil. |
| Giriş sonrası tekrar giriş sayfası | HTTPS olmadan erişiliyor (çerez `Secure`). Alan adını `https://` ile girin. |
