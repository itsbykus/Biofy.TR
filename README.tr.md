<div align="center">

<a href="https://biofy.tr"><img src="https://biofy.tr/media/favicon/2026/10/g1fwviz7ub9zv8dfdddvmfldvmpa00kp.png" width="84" height="84" alt="Biofy.TR logosu"></a>

# Biofy.TR

**Tek bağlantı. Tüm dünyan.**<br>
Kendi sunucunuzda çalışan, çok kullanıcılı link-in-bio platformu: temalar, içerik blokları, analitik ve özel alan adları.

[![Canlı site](https://img.shields.io/badge/canl%C4%B1-biofy.tr-FFCF00?style=for-the-badge&labelColor=111111)](https://biofy.tr)
[![Son sürüm](https://img.shields.io/github/v/release/itsbykus/Biofy.TR?style=for-the-badge&labelColor=111111&color=444444&label=s%C3%BCr%C3%BCm)](https://github.com/itsbykus/Biofy.TR/releases/latest)

[![Tests](https://github.com/itsbykus/Biofy.TR/actions/workflows/tests.yml/badge.svg)](https://github.com/itsbykus/Biofy.TR/actions/workflows/tests.yml)
[![Deployment checks](https://github.com/itsbykus/Biofy.TR/actions/workflows/deployment-checks.yml/badge.svg)](https://github.com/itsbykus/Biofy.TR/actions/workflows/deployment-checks.yml)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
[![Lisans: MIT](https://img.shields.io/badge/lisans-MIT-22c55e)](LICENSE)

**Türkçe** · [English](README.md)

**[🌐 Canlı olarak inceleyin: biofy.tr](https://biofy.tr)** · [İndir](https://github.com/itsbykus/Biofy.TR/releases/latest) · [Kurulum](docs/INSTALLATION.md) · [Değişiklikler](CHANGELOG.md)

<br>

<a href="https://biofy.tr"><img src=".github/assets/landing.png" alt="Biofy.TR ana sayfası" width="900"></a>

</div>

## Canlı olarak inceleyin

**[https://biofy.tr](https://biofy.tr)** bu depodaki kodla çalışır. Ana sayfayı, temaları ve fiyatlandırmayı gezebilir ya da ücretsiz kayıt olup kendi sayfanızı oluşturabilirsiniz. Sayfanız `https://biofy.tr/kullaniciadi` adresinde yayına girer.

<table>
  <tr>
    <td width="36%" align="center" valign="top">
      <img src=".github/assets/profile.png" alt="Dark Pro temalı yayımlanmış bir bio sayfası" width="300"><br>
      <sub><b>Yayımlanmış bir bio sayfası</b>: Dark Pro teması, sosyal ikonlar, öne çıkan bağlantı</sub>
    </td>
    <td width="64%" align="center" valign="top">
      <img src=".github/assets/themes.png" alt="Tema galerisi" width="560"><br>
      <sub><b>12 farklı tema</b>: her birinin kendi düzeni, tipografisi ve buton stili var</sub>
    </td>
  </tr>
</table>

## Neden Biofy.TR

- **Mevcut hostinginizde çalışır.** ZIP'i FTP ile paylaşımlı (WordPress dahil) hostinge yükleyin, kurulumu web sihirbazında tamamlayın. Sunucuda SSH, Composer veya Node.js gerekmez. İsterseniz Coolify'da Docker ile dağıtın.
- **Önce gizlilik.** Yerleşik analitik çerez kullanmaz ve IP adresi saklamaz. Pazarlama pikselleri (Google, Meta, TikTok) isteğe bağlıdır ve yalnızca ziyaretçi kabul ettikten sonra yüklenir.
- **Varsayılan olarak güvenli.** Nonce'lu sıkı CSP, TOTP iki adımlı doğrulama, host header koruması ve yönetim panelinin yüklenen dosyaları karşılaştırdığı imzalı sürüm manifesti.
- Arayüz, e-postalar ve belgeler baştan sona **Türkçe ve İngilizce**.

## Özellikler

| | Alan | Neler var |
|:-:|---|---|
| 👤 | **Hesaplar** | E-posta/parola, e-posta doğrulama, parola sıfırlama, Google ile giriş (OAuth/OIDC), TOTP iki adımlı doğrulama + kurtarma kodları, oturum yönetimi, güvenlik geçmişi, KVKK veri dışa aktarma ve hesap silme |
| 🧱 | **Editör** | 20 blok türü, sürükle-bırak ve klavye ile sıralama, canlı mobil/masaüstü önizleme, taslak ↔ yayın, sürüm geçmişi, çoklu sekme çakışma koruması, zamanlanmış bloklar |
| 🔘 | **Akıllı butonlar** | 70'ten fazla bağlantı türü için otomatik marka simgesi veya seçilen simge; butonlarda küçük görsel ve arka plan görseli |
| 🎨 | **Temalar** | 12 tema; renk, degrade, görsel arka plan, yazı tipi, hizalama (sol/orta/sağ), buton biçimi/kenarlık/gölge, sosyal ikon biçimi ve marka renkleri, animasyon |
| 📦 | **Paketler** | Basic / Pro / Premium; merkezi yetki motoru (platform kısıtı → hesap durumu → kullanıcıya özel izin → paket → varsayılan), kotalar, süreli atamalar |
| 📈 | **Analitik** | Çerezsiz görüntülenme/tıklama sayımı, bot ve tekrar filtreleri, günlük toplulaştırma, kaynak/cihaz/tarayıcı/ülke kırılımları, CSV |
| 🌍 | **Alan adları** | DNS TXT ile sahiplik doğrulaması, CNAME/A kontrolü, HTTPS doğrulaması, bilinmeyen host reddi |
| 🛠️ | **Yönetim** | Canlı dashboard, kullanıcı/sayfa moderasyonu, paket ve özellik kataloğu, CMS sayfaları, SEO ayarlı blog ve menüler, ana sayfa (landing) ve footer düzenleme, marka, logo boyutu, panel logosu ve bio sayfası rozeti, e-posta şablonları, SMTP/Google ayarları, medya, yedekleme/geri yükleme, sistem sağlığı, panelden güncelleme, denetim kayıtları |
| 🔗 | **Kısa bağlantı ve QR** | Tıklama sayılı, özel kodlu `/s/kod` kısa bağlantıları; her adres veya metin için sunucuda üretilen renkli QR kodlar (PNG/SVG) |
| 🎯 | **İzleme pikselleri** | Google Analytics 4, Google Ads, Tag Manager, Meta ve TikTok — site geneli ve bio sayfası bazında (Pro); yalnızca izinle yüklenir, CSP yalnızca kullanılan sayfada genişler |
| 🔎 | **SEO** | Sayfa bazında başlık, açıklama ve paylaşım görseli (bio sayfaları, blog yazıları, CMS sayfaları), kanonik adresler, site haritaları, robots.txt, yapılandırılmış veri (WebSite, Organization, ProfilePage, BlogPosting, BreadcrumbList), Search Console / Bing / Yandex doğrulaması |
| 🖼️ | **Medya** | JPG, PNG, GIF ve SVG yüklemeleri boyutlandırılır ve WebP olarak saklanır, EXIF/GPS silinir |
| 🔌 | **API** | `/api/v1` REST API, hash'li Sanctum token'ları, yetki kapsamları, token/kullanıcı bazlı hız sınırı, OpenAPI 3 dokümanı |

**Teknoloji:** Laravel 13 · PHP 8.3+ · MySQL 5.7+ / MariaDB 10.3+ · Blade · Tailwind CSS 4 (derlenmiş) · bağımlılıksız JavaScript

## Hızlı başlangıç

<table>
  <tr>
    <th width="50%">🗂️ Paylaşımlı (WordPress) hosting</th>
    <th width="50%">🐳 Coolify / Docker</th>
  </tr>
  <tr>
    <td valign="top">

1. [Son sürümün](https://github.com/itsbykus/Biofy.TR/releases/latest) **Assets** bölümünden **`biofy-tr-<sürüm>.zip`** dosyasını indirin ve `.sha256` dosyasıyla karşılaştırın.
2. FTP ile bir (alt) alan adına yükleyin ([yerleşimler](docs/SHARED_HOSTING.md)).
3. WordPress'ten ayrı, **boş** bir MySQL/MariaDB veritabanı oluşturun.
4. Alan adını açın; kurulum sihirbazı başlar ([adımlar](docs/INSTALLATION.md)).

> ⚠️ GitHub'ın otomatik "Source code" arşivlerini (`Biofy.TR-<sürüm>.zip`) kullanmayın: onlarda `vendor/` ve `public/build/` yoktur.

</td>
    <td valign="top">

1. Coolify'da bu depoyu **Docker Compose** build pack ile ekleyin, `app` servisine alan adı verin.
2. `APP_URL`, `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` ve ilk superadmin için `BIOFY_ADMIN_*` değişkenlerini girin.
3. **Deploy**'a basın. GitHub'a her push yeniden dağıtır; migration öncesi otomatik yedek alınır ([ayrıntılar](docs/COOLIFY.md)).

</td>
  </tr>
</table>

Mevcut bir kurulumu güncellemek için: [docs/UPDATE.md](docs/UPDATE.md).

<details>
<summary><b>💻 Geliştirme ortamı</b></summary>

<br>

Composer, npm ve Node.js yalnızca burada (veya GitHub Actions'ta) gerekir, hosting hesabında asla.

```bash
composer install
npm ci && npm run build        # veya npm run dev
cp .env.example .env && php artisan key:generate
# .env içinde APP_ENV=local, APP_DEBUG=true ve veritabanı bilgileri
php artisan migrate --seed
touch storage/app/private/installed.lock   # web sihirbazını atlamak için
php artisan serve
php artisan test                # 170 test
php scripts/build-release.php   # FTP paketi -> dist/, scripts/verify-release.php ile doğrulanır
```

</details>

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

> [!NOTE]
> Hukuki sayfalar (Gizlilik, Kullanım Koşulları, Çerez, KVKK) düzenlenebilir **başlangıç taslaklarıdır**; yayına almadan önce hukuk danışmanınıza inceletin.

## Lisans

[MIT](LICENSE) · **[biofy.tr](https://biofy.tr)**
