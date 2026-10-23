# Biofy.TR

**Türkçe** · [English](README.md)

Çok kullanıcılı (multi-tenant) **link-in-bio SaaS** platformu. Kullanıcılar kayıt olur, hazır temalardan birini seçer, bağlantılarını ve içerik bloklarını ekler, mobil uyumlu sayfalarını `https://biofy.tr/kullaniciadi` adresinde veya kendi alan adlarında yayınlar ve ziyaret/tıklama istatistiklerini izler.

- **Teknoloji:** Laravel 13 · PHP 8.3+ · MySQL 5.7+ / MariaDB 10.3+ · Blade · Tailwind CSS 4 (derlenmiş) · bağımlılıksız JavaScript
- **Diller:** Türkçe (varsayılan) ve İngilizce
- **Dağıtım:** (1) paylaşımlı WordPress hosting — FTP + web sihirbazı, sunucuda SSH/Composer/Node gerekmez; (2) Coolify — GitHub'a her push'ta otomatik Docker dağıtımı
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

## Hızlı başlangıç

**Paylaşımlı (WordPress) hosting**

1. `biofy-tr-1.0.0.zip` paketini açın ve FTP ile bir alt alan adına yükleyin (yerleşimler: [docs/SHARED_HOSTING.md](docs/SHARED_HOSTING.md)).
2. Hosting panelinizde WordPress'ten ayrı, **boş** bir MySQL/MariaDB veritabanı oluşturun.
3. Alt alan adını açın; kurulum sihirbazı başlar. Adımlar: [docs/INSTALLATION.md](docs/INSTALLATION.md).

**Coolify**

1. Coolify'da bu depoyu "Docker Compose" build pack ile ekleyin, `app` servisine alan adı verin.
2. `APP_URL`, `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` ve ilk superadmin için `BIOFY_ADMIN_*` değişkenlerini girin, Deploy'a basın.
3. Ayrıntılar: [docs/COOLIFY.md](docs/COOLIFY.md).

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
| [SECURITY](docs/SECURITY.tr.md) | Tehdit modeli ve uygulanan önlemler |
| [DEPLOYMENT](docs/DEPLOYMENT.md) | İki dağıtım yönteminin karşılaştırması, sürüm paketi üretme |
| [SHARED_HOSTING](docs/SHARED_HOSTING.md) | WordPress hosting gereksinimleri, yerleşimler, dosya izinleri |
| [COOLIFY](docs/COOLIFY.md) | Coolify kurulumu, ortam değişkenleri, otomatik dağıtım, migration, kalıcı veri |
| [INSTALLATION](docs/INSTALLATION.md) · [UPDATE](docs/UPDATE.md) | Kurulum ve güncelleme kılavuzları |
| [OPERATIONS](docs/OPERATIONS.md) | Cron, e-posta, yedekleme/geri yükleme, alan adları, üretime alma kontrol listesi, bilinen sınırlamalar |
| [API](docs/API.md) · [openapi.yaml](docs/openapi.yaml) | REST API |
| [TESTING](docs/TESTING.md) | Test paketi, dağıtım testleri ve kapsam dışı kalanlar |
| [CHANGELOG](CHANGELOG.md) | Sürüm notları |
| [ROADMAP](docs/ROADMAP.md) | Ödeme altyapısı hazırlığı ve sonraki adımlar |

> Hukuki sayfalar (Gizlilik, Kullanım Koşulları, Çerez, KVKK) düzenlenebilir **başlangıç taslaklarıdır**; yayına almadan önce hukuk danışmanınıza inceletin.

## Lisans

[MIT](LICENSE)
