# Değişiklik günlüğü

Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/) kurallarına uyar.

## [1.0.3] — 2026-10-24

Yeni ana sayfa, footer ve logo ayarları. Veritabanı değişikliği (migration) yoktur; 1.0.2 kurulumları [UPDATE.md](docs/UPDATE.md) ile güncellenir. Yeni ayarlar varsayılan değerleriyle gelir; görünüm, siz değiştirene kadar önceki sürüme yakındır.

### Eklenenler
- **Yönetim → Ayarlar → Alt bilgi (footer):** görünüm (açık / koyu / marka rengi), ayrı footer logosu ve yüksekliği, logonun yanındaki marka yazısı (göster/gizle, metin), açıklama metni, sosyal ikonları göster/gizle, iki bağlantı sütununun başlıkları, `{year}` ve `{site}` destekli telif metni. Bağlantılar her zamanki gibi Yönetim → Menüler'den yönetilir.
- **Yönetim → Ayarlar → Marka:** logo yüksekliği (16–96 px). Görsel logoya veya metin logoya uygulanır; üst menü buna göre büyür.
- **Yeniden tasarlanan ana sayfa (landing)** ve ayarları: giriş bölümü görünümü (ışıltılı / koyu / sade), rozet metni, ikinci buton (metin + bağlantı), "Nasıl çalışır?" bölümü, düzenlenebilir özellikler başlığı/metni, kapanış bölümü başlığı/metni; "Nasıl çalışır?", "Özellikler", "Temalar" ve "Fiyatlandırma" bölümleri ayrı ayrı açılıp kapatılabilir.

### Düzeltilenler
- **Sosyal ikonlarda e-posta (ve web sitesi) ikonu bozuktu:** çizgi ikonları içi dolu çiziliyordu.
- **Ayarlarda onay kutuları kapatılamıyordu:** işareti kaldırılan bir kutu "değiştirme" olarak yorumlanıp kaydedilmiyordu (ör. duyuru bandı, arama motoru dizinleme, Google ile giriş).
- **Yapılandırılmış veri (JSON-LD) bozuktu:** ana sayfa ve bio sayfalarında `@context` anahtarı Laravel'in Blade direktifi olarak derleniyordu; arama motorları geçersiz JSON görüyordu.
- **Güncelleme öncesi yedek başarısız oluyordu ("Undefined array key 0"):** veritabanı kullanıcısı başka veritabanlarını da görebiliyorsa (paylaşımlı hostingde sık görülür, ör. WordPress'inki) yedekleme o veritabanlarının tablolarını da almaya çalışıyordu. Bu da "Güncellemeyi uygula"yı durduruyordu. Artık yalnızca uygulamanın kendi veritabanı yedeklenir.
- **"Dosya bütünlüğünü doğrula" 30 saniyelik PHP sınırına takılıyordu** (500 hatası). Ayrıca süre dolup dosyaların bir kısmı kontrol edilmeden kalınca sonuç yine "ok" görünüyordu. Doğrulama artık izin verilen yerde süre sınırını uzatıyor, gerçek sınırdan önce duruyor ve tamamlanamazsa "yarıda kaldı" diyor.
- Ayarlar kaydedildikten sonra aynı istek döngüsünde eski site bilgileri kullanılabiliyordu.
- Analitik testleri UTC 21:00–24:00 arasında çalıştırıldığında başarısız oluyordu (test saati artık sabit).

## [1.0.2] — 2026-10-23

İlk canlı kurulumda bildirilen sorunlar. Veritabanı değişikliği (migration) yoktur; 1.0.1 kurulumları [UPDATE.md](docs/UPDATE.md) ile güncellenir.

### Düzeltilenler
- **Medya sayfası 500 hatası:** bir sayfa en az bir kez yayımlandıktan sonra Medya kütüphanesi (`/dashboard/media`) ve medya silme her kullanıcıda hata veriyordu (yayımlanmış sürümün içeriği yanlış okunuyordu).
- **Dil değiştirme 403 hatası:** paketin tamamı web kök dizinindeyken (yerleşim C) `/lang/en` adresi, uygulamanın `lang/` klasöründeki erişim engeline takılıyordu. Dil bağlantısı artık `/locale/en`. Aynı nedenle `bootstrap`, `config`, `database`, `resources` ve `routes` sayfa adresi olarak rezerve edildi ve kullanılmayan `/storage/...` rotası kapatıldı.
- **Yönetim → Ayarlar → Marka / SEO görselleri:** dosya alanlarının yerine "Görsel seç" düğmesi, seçilen görselin anında önizlemesi ve dosyanın "Kaydet" ile yükleneceğini söyleyen not geldi.
- Saat İstanbul'da 23:29'dan sonra çalıştırıldığında başarısız olan bir analitik testi düzeltildi.

### Eklenenler
- Sosyal hesaplar: **WhatsApp** (telefon numarası → `https://wa.me/…`) ve **E-posta** (`mailto:`).
- Tema: **sağa hizalama**; sosyal ikonlar için **biçim** (daire, yuvarlatılmış, kare) ve **renk** (tema renkleri, her platformun marka rengi, özel renk) seçenekleri.

### Değişenler
- Yazı tipi, ikincil metin rengi, hizalama, buton biçimi / köşe / kenarlık / gölge ve sosyal ikon ayarları artık **tüm paketlerde** (Basic dahil). Pro'da kalanlar: degrade ve görsel arka plan, kart stili, başlık yazı tipi ve yazı boyutu, profil resmi ve kapak görünümü, sayfa genişliği, boşluk, animasyon ve Pro temalar.

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
