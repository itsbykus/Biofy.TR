# Değişiklik günlüğü

Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/) kurallarına uyar.

## [1.0.1] — 2026-10-23

Kurulum ve dağıtım paketi düzeltmeleri. Veritabanı değişikliği (migration) yoktur; 1.0.0 kurulumları [UPDATE.md](docs/UPDATE.md) ile güncellenir.

### Düzeltilenler
- **Kurulum sihirbazı, yarıda kalan bir kurulumu devam ettirebilir.** Şema oluşturulup henüz hesap oluşturulmadan kesilen bir denemeden sonra (ör. kapanan tarayıcı, zaman aşımı) veritabanı "zaten kurulum var" diye reddediliyordu; artık kabul edilir ve kurulum kaldığı yerden sürer (konteyner kurulumuyla aynı kural).
- Yeniden denemede önceki denemenin `APP_KEY`'i artık korunur. Daha önce tırnaklı yazılan anahtar tanınmadığı için her denemede yeni anahtar üretiliyor, seed sırasında şifrelenen ayarlar (cron anahtarı) okunamaz hale gelebiliyordu.
- Superadmin, ayarlar ve paket ataması tek bir veritabanı işleminde (transaction) oluşturulur; bu adımda bir hata yarım hesap bırakmaz. Kilit dosyası yazılamazsa kurulum başarılı sayılmaz, açık bir hata gösterilir.
- Tablo öneki boşken sihirbaz başka bir uygulamanın (ör. WordPress) tablolarını içeren veritabanını kabul ediyordu (belgelerde reddedildiği yazıyordu). Artık seçilen önekle başlayan herhangi bir tablo varsa reddedilir.

### Eklenenler
- Sihirbaz, GitHub'ın otomatik "Source code" arşivi (`Biofy.TR-<sürüm>.zip`, `vendor/` ve `public/build/` yok) yüklendiğinde doğru paketi (`biofy-tr-<sürüm>.zip`) açıkça söyler; Vite manifestindeki her dosyayı ve `release-manifest.json` ile paketteki tüm dosyaların yüklendiğini (eksik FTP aktarımı) kontrol eder.
- `scripts/verify-release.php`: paketi yayımlamadan veya kurmadan önce denetler (vendor/, public/build/ ve manifest dosyaları, kurulum dosyaları, geliştirme paketleri, `.env`/sır taraması, imza, manifest–ZIP eşleşmesi, SHA-256). `build-release.php` bunu otomatik çalıştırır ve başarısız paketi siler; derleme ayrıca `composer validate` ve `composer check-platform-reqs --no-dev` çalıştırır.
- Release iş akışı paketi sabitlenmiş yayın anahtarıyla doğrular, yüklenen varlıkları geri indirip yeniden doğrular ve sürüm notlarında "Source code" arşivleri için uyarı verir.
- CI: PHP sözdizimi kontrolü, `composer validate`, Docker imajı içerik kontrolleri, kaynak kodu arşiviyle kurulum denemesi; sihirbaz için birim testleri.
- Belgeler: hangi dosyanın indirileceği, SHA-256 doğrulama komutları, geliştirme ortamı (Composer/npm) ile hosting kullanıcısı arasındaki fark, sürüm çıkarma adımları.

## [1.0.0] — 2026-10-23

İlk sürüm.

### Eklenenler
- E-posta/parola ile kayıt ve giriş, e-posta doğrulama, parola sıfırlama, TOTP iki adımlı doğrulama, kurtarma kodları, oturum yönetimi, Google ile giriş (otomatik hesap birleştirme olmadan).
- Kullanıcı adı/sayfa adresi kuralları, rezerve kelimeler, adres değişikliğinde yönlendirme.
- 20 blok türlü editör, canlı önizleme, taslak/yayın ayrımı, sürüm geçmişi, çok sekme ve eşzamanlılık koruması, zamanlanmış bloklar.
- 12 tema ve güvenli tema özelleştirme.
- Basic, Pro ve Premium paketleri; merkezi özellik/limit sistemi ve kullanıcıya özel istisnalar.
- Gizlilik odaklı analitik (ham IP saklanmaz), günlük özetler, CSV dışa aktarma.
- Özel alan adları: DNS TXT doğrulaması, HTTPS kontrolü, host header koruması.
- Superadmin paneli: kullanıcılar, sayfalar, paketler, özellik kataloğu, temalar, ayarlar, marka, CMS sayfaları, menüler, e-posta şablonları, medya, alan adları, iletişim mesajları, denetim ve güvenlik kayıtları, yedekler, sistem durumu.
- SMTP yapılandırması ve TR/EN e-posta şablonları.
- Şifreli yedekleme ve geri yükleme.
- REST API v1 (hash'li token'lar, yetki kapsamları, hız sınırı, OpenAPI şeması).
- Türkçe ve İngilizce arayüz.
- **Paylaşımlı (WordPress) hosting:** FTP paketi, web kurulum sihirbazı, imzalı sürüm manifesti ve panelden güncelleme.
- **Coolify:** Dockerfile, docker-compose.yml, ortam değişkenleriyle kurulum, migration öncesi otomatik yedek, health check, kalıcı volume'ler.
- CI: PHPUnit (PHP 8.3/8.4) ve iki dağıtım yönteminin uçtan uca testleri.
