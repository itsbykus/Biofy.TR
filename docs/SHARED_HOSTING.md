# Paylaşımlı (WordPress) hosting

Biofy.TR, WordPress çalıştıran sıradan bir paylaşımlı hosting hesabında (cPanel, Plesk, DirectAdmin; Apache veya LiteSpeed) **WordPress'ten ayrı bir uygulama olarak** çalışır. Kurulum yalnızca FTP/dosya yöneticisi ve tarayıcıyla yapılır; terminal, SSH, Composer, npm veya Node.js gerekmez. Adım adım kurulum: [INSTALLATION.md](INSTALLATION.md).

Yüklenecek dosya GitHub Releases'taki **`biofy-tr-<sürüm>.zip`** paketidir (`vendor/` ve `public/build/` dahil). GitHub'ın otomatik eklediği "Source code" arşivleri (`Biofy.TR-<sürüm>.zip`) hostingde çalışmaz ([ayrıntı](INSTALLATION.md#1-doğru-dosyayı-indirin)).

## Gereksinimler

Kurulum sihirbazının ilk adımı bunları otomatik kontrol eder.

| Gereksinim | Zorunlu | Not |
|---|---|---|
| PHP **8.3+** (8.4 önerilir) | ✔ | Alan adı başına seçilebilir (cPanel → MultiPHP Manager). WordPress'in PHP sürümünü değiştirmeniz gerekmez. |
| Eklentiler: pdo_mysql, mbstring, openssl, tokenizer, xml, dom, ctype, json, fileinfo, **gd**, **zip**, session, filter | ✔ | WordPress hostlarında genellikle hepsi açıktır |
| Eklentiler: curl, sodium, intl, bcmath | önerilen | curl: alan adı HTTPS kontrolü, Google girişi; sodium: yedek şifreleme |
| MySQL 5.7+ / MariaDB 10.3+ (InnoDB, utf8mb4) | ✔ | **WordPress'ten ayrı, boş bir veritabanı** ve ayrı bir kullanıcı |
| Apache veya LiteSpeed, `mod_rewrite`, `.htaccess` | ✔ | WordPress kalıcı bağlantıları çalışıyorsa bu koşul zaten sağlanıyordur |
| Yazılabilir klasörler: `storage/`, `bootstrap/cache/`, `public/media/`, uygulama klasörü (`.env` için) | ✔ | Varsayılan izinler yeterli (aşağıya bakın) |
| `memory_limit` ≥ 128M, `max_execution_time` ≥ 60, `upload_max_filesize` ≥ 6M | önerilen | Yedekleme için daha yüksek değerler iyidir |
| SSL (AutoSSL / Let's Encrypt) | önerilen | Alt alan adı için de etkinleştirin |
| Cron | isteğe bağlı | [OPERATIONS.md](OPERATIONS.md#cron) |

**Desteklenmeyen:** WordPress'in bulunduğu alan adının alt klasörüne kurulum (`site.com/biofy`). Biofy.TR bir **alt alan adında** (`bio.site.com`) veya **ayrı bir alan adında** çalışmalıdır.

## WordPress ile birlikte çalışma

| Konu | Durum |
|---|---|
| Dosyalar | Biofy.TR kendi klasöründedir; WordPress dosyalarına dokunmaz. Önerilen yerleşimde `public_html` dışındadır. |
| Veritabanı | Ayrı veritabanı kullanılır. Sihirbaz, seçilen tablo önekiyle başlayan herhangi bir tablo bulunan veritabanını reddeder; önek boşken WordPress'in veritabanı da reddedilir. Ayrı veritabanı açılamıyorsa aynı veritabanı farklı bir önekle (ör. `bio_`) kullanılabilir, ancak ayrı veritabanı önerilir (yedekler ve yetkiler ayrı kalır). |
| `.htaccess` | Biofy.TR klasörlerindeki kendi `.htaccess` dosyaları `RewriteEngine On` içerir; bu yüzden üst klasördeki WordPress yönlendirme kuralları devralınmaz. WordPress'in `.htaccess` dosyası değiştirilmez. |
| Çerezler / oturum | Alt alan adındaki Biofy.TR çerezleri yalnızca o alan adına yazılır; WordPress oturumlarıyla çakışmaz. |
| PHP sürümü | Alan adı başına ayrı seçilebilir. |
| Önbellek eklentileri | LiteSpeed Cache, WP Rocket vb. yalnızca WordPress yanıtlarını önbelleğe alır. Uygulama `public_html` dışında olduğunda (yerleşim A/B) WordPress'in `.htaccess` ve `.user.ini` ayarlarından hiç etkilenmez. |

## Yerleşimler

### A — Önerilen: uygulama `public_html` dışında, alt alan adının kökü `public/`

```
/home/KULLANICI/public_html/        ← WordPress (değişmez)
/home/KULLANICI/biofy/              ← Biofy.TR paketinin tamamı (ZIP'teki biofy-tr-<sürüm>/ klasörünün içeriği)
/home/KULLANICI/biofy/public        ← bio.site.com'un Document Root'u
```

- cPanel: **Domains → Create A New Domain** (veya Subdomains) → `bio.site.com`, "Share document root" işaretini kaldırın, Document Root: `biofy/public`.
- Plesk: **Add Subdomain** → Document root: `/biofy/public`.
- DirectAdmin: alt alan adının klasörünü `biofy/public` olarak ayarlayın (panel izin veriyorsa); yoksa B'yi kullanın.

Uygulama kodu, `.env` ve yedekler web'den hiçbir şekilde erişilemez.

### B — Document Root değiştirilemiyorsa: yalnızca `public/` içeriği alt alan adı klasöründe

```
/home/KULLANICI/bio.site.com/       ← panelin oluşturduğu klasör: biofy/public İÇERİĞİ (index.php, .htaccess, build/, media/)
/home/KULLANICI/biofy/              ← paketin tamamı (klasör adı tam olarak "biofy")
```

`index.php` uygulamayı `../biofy` içinde bulur; sihirbaz bunu algılayıp `bootstrap/public-path.php` dosyasını yazar (cron ve yedekler aynı medya klasörünü kullanır). Alt alan adı klasörü `public_html` **dışında** olmalıdır; aksi halde C'yi kullanın.

### C — Son çare: alt alan adı klasörü `public_html` içinde

Bazı paneller alt alan adlarını `public_html/bio.site.com` gibi bir klasöre bağlar ve bunu değiştirmeye izin vermez. Bu durumda paketin **tamamını** o klasöre yükleyin. Kök `.htaccess` her isteği `public/`'e yönlendirir; hassas klasörlerin her birinde erişimi engelleyen `.htaccess` vardır; nokta ile başlayan dosyalar reddedilir. Klasöre WordPress alan adı üzerinden (`site.com/bio.site.com/...`) gelen istekler de aynı kurallarla engellenir ve uygulama bilinmeyen alan adına yanıt vermez.

Kurulumdan sonra **mutlaka** Yönetim → Sistem → **Hassas dosya erişim testi**ni çalıştırın; tüm satırlar yeşil olmalıdır. Bu yerleşim, sunucu `.htaccess` dosyalarını uyguladığı sürece güvenlidir (Apache/LiteSpeed paylaşımlı hostlarda uygular).

## Dosya izinleri

Paylaşımlı hostlarda PHP, hesap kullanıcısıyla (suEXEC / LSAPI / PHP-FPM) çalışır; FTP ile yüklenen dosyalar zaten bu kullanıcıya aittir.

| Öğe | İzin |
|---|---|
| Klasörler | `755` |
| Dosyalar | `644` |
| `.env` | `640` (sihirbaz böyle yazar; `600` da olur) |
| `storage/`, `bootstrap/cache/`, `public/media/` | `755` — 777 **kullanmayın** |

Sihirbaz yazma izni eksikse hangi klasör olduğunu gösterir.

## Müşteri alan adları (özel domain)

Kullanıcıların kendi alan adlarını kullanabilmesi için her alan adının hosting hesabınıza "Alias / Parked Domain" olarak eklenmesi ve Document Root'unun Biofy.TR ile aynı olması gerekir (yerleşim A'da `biofy/public`). SSL'i AutoSSL üretir; uygulama sertifika üretmez. Yönetim → Ayarlar → API ve alan adları bölümüne kullanıcılara gösterilecek CNAME hedefini ve/veya A kaydı IP'sini girin. Ayrıntılar: [OPERATIONS.md](OPERATIONS.md#özel-alan-adları-ve-ssl).
