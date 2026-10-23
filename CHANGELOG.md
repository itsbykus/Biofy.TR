# Değişiklik günlüğü

Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/) kurallarına uyar.

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
