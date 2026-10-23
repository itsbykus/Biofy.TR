# Biofy.TR

Çok kullanıcılı (multi-tenant) **link-in-bio SaaS** platformu. Kullanıcılar kayıt olur, hazır temalardan birini seçer, bağlantılarını ve içerik bloklarını ekler, mobil uyumlu sayfalarını `https://biofy.tr/kullaniciadi` adresinde veya kendi alan adlarında yayınlar ve ziyaret/tıklama istatistiklerini izler.

- **Teknoloji:** Laravel 13 · PHP 8.3+ · MySQL 5.7+ / MariaDB 10.3+ · Blade · Tailwind CSS 4 (derlenmiş) · bağımlılıksız JavaScript
- **Diller:** Türkçe (varsayılan) ve İngilizce
- **Kurulum:** FTP + web sihirbazı. Sunucuda SSH, Composer, npm veya Node.js gerekmez.
- **Sürüm:** 1.0.0

## Öne çıkanlar

| Alan | Özet |
|---|---|
| Hesaplar | E-posta/parola, e-posta doğrulama, parola sıfırlama, Google ile giriş (OAuth/OIDC), TOTP iki adımlı doğrulama + kurtarma kodları, oturum yönetimi, güvenlik geçmişi, KVKK veri dışa aktarma ve hesap silme |
| Editör | 20 blok türü, sürükle-bırak ve klavye ile sıralama, canlı mobil/masaüstü önizleme, taslak ↔ yayın ayrımı, sürüm geçmişi, değişiklikleri geri alma, çoklu sekme/çakışma koruması, zamanlanmış bloklar |
| Temalar | 12 birbirinden farklı tema (düzen, tipografi, buton ve kart stilleri), güvenli özelleştirme (renk, degrade, görsel arka plan, yazı tipi, köşe, gölge, hizalama, animasyon) |
| Paketler | Basic / Pro / Premium; merkezi yetki motoru (platform kısıtı → hesap durumu → kullanıcıya özel izin → paket → varsayılan), kotalar, süreli atamalar |
| Analitik | Çerezsiz, IP saklamayan görüntülenme/tıklama sayımı, bot ve tekrar filtreleri, günlük toplulaştırma, kaynak/cihaz/tarayıcı/ülke kırılımları, CSV |
| Alan adları | DNS TXT ile sahiplik doğrulaması, CNAME/A kontrolü, HTTPS doğrulaması, bilinmeyen host reddi |
| Yönetim | Gerçek veriden dashboard, kullanıcı/sayfa moderasyonu, paket ve özellik kataloğu, CMS, menüler, e-posta şablonları, SMTP/Google ayarları, medya, yedekleme/geri yükleme, sistem sağlığı, güncelleme, denetim kayıtları |
| API | `/api/v1` REST API, Sanctum token'ları (hash'li), yetki kapsamları, token/kullanıcı bazlı hız sınırı, OpenAPI 3 dokümanı |

## Hızlı başlangıç (canlı sunucu)

1. `biofy-tr-1.0.0.zip` paketini açın ve FTP ile yükleyin (yerleşim seçenekleri: [docs/SHARED_HOSTING.md](docs/SHARED_HOSTING.md)).
2. Hosting panelinizde **boş** bir MySQL/MariaDB veritabanı oluşturun.
3. Site adresinizi açın; kurulum sihirbazı başlar. Adımlar: [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Geliştirme ortamı

```bash
composer install
npm ci && npm run build        # veya npm run dev
cp .env.example .env && php artisan key:generate
# .env içinde APP_ENV=local, APP_DEBUG=true ve veritabanı bilgileri
php artisan migrate --seed
touch storage/app/private/installed.lock   # web sihirbazını atlamak için
php artisan serve
php artisan test                # 116 test
php scripts/build-release.php   # FTP paketi -> dist/
```

## Dokümantasyon

| Doküman | İçerik |
|---|---|
| [ARCHITECTURE](docs/ARCHITECTURE.md) | Modüller, istek akışı, önemli kararlar |
| [DATABASE](docs/DATABASE.md) | Tablolar, ilişkiler, indeksler, tenant izolasyonu |
| [SECURITY](docs/SECURITY.md) | Tehdit modeli ve uygulanan önlemler |
| [DEPLOYMENT](docs/DEPLOYMENT.md) · [SHARED_HOSTING](docs/SHARED_HOSTING.md) | Dağıtım paketi ve hosting yerleşimleri, uyumluluk kontrol listesi |
| [INSTALLATION](docs/INSTALLATION.md) · [UPDATE](docs/UPDATE.md) | Kurulum ve güncelleme kılavuzları |
| [OPERATIONS](docs/OPERATIONS.md) | Cron, e-posta, yedekleme/geri yükleme, alan adları, üretime alma kontrol listesi, bilinen sınırlamalar |
| [API](docs/API.md) · [openapi.yaml](docs/openapi.yaml) | REST API |
| [TESTING](docs/TESTING.md) | Test paketi ve kapsamı |
| [ROADMAP](docs/ROADMAP.md) | Ödeme altyapısı hazırlığı ve sonraki adımlar |

> Hukuki sayfalar (Gizlilik, Kullanım Koşulları, Çerez, KVKK) düzenlenebilir **başlangıç taslaklarıdır**; yayına almadan önce hukuk danışmanınıza inceletin.
