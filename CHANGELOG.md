# Değişiklik günlüğü

Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/) kurallarına uyar.

## [1.1.0] — 2026-10-25

İzleme pikselleri, kısa bağlantı ve QR kod araçları, akıllı butonlar, SVG desteği ve blog SEO ayarları. **Veritabanı değişikliği (migration) içerir**; 1.0.4 kurulumları [UPDATE.md](docs/UPDATE.md) ile güncellenir, "Güncellemeyi uygula" yedek aldıktan sonra migration'ları kendisi çalıştırır.

### Eklenenler
- **İzleme pikselleri (Google Analytics 4, Google Ads, Google Tag Manager, Meta Pixel, TikTok Pixel):**
  - Site geneli: Yönetim → Ayarlar → İzleme etiketleri (ana sayfa, blog ve kurumsal sayfalar; paneller hariç).
  - Bio sayfası bazında: editör → SEO sekmesi → İzleme pikselleri (Pro ve Premium). Bağlantı tıklamaları olay olarak gönderilir.
  - Etiketler **yalnızca ziyaretçinin açık rızasıyla** yüklenir: kimlik eklendiğinde bilgilendirme bandı, "Kabul et" ve "Reddet" seçeneklerinin eşit sunulduğu bir onay bandına dönüşür; tercih 180 gün saklanır ve "Çerez tercihleri" bağlantısından değiştirilebilir. İzin site ve her bio sayfası için ayrı sorulur.
  - Sayfaya yalnızca sıkı doğrulanmış kimlikler yazılır; CSP yalnızca etiket kullanılan sayfada ve yalnızca ilgili sağlayıcının alan adlarıyla genişletilir.
- **Kısa bağlantılar** (panel → Kısa Bağlantılar): `/s/kod` adresleri, otomatik veya özel kod, tıklama sayısı (botlar ve önizlemeler hariç, IP saklanmaz), hedef değiştirme, QR kod indirme. Basic 10, Pro 200, Premium sınırsız. Yönetim → Kısa Bağlantılar'dan arama, devre dışı bırakma ve silme.
- **QR kod oluşturucu** (panel → QR Kod): her adres veya metin için renk seçimli, kontrast kontrollü QR kod; PNG veya SVG. Sunucuda üretilir, hiçbir şey saklanmaz.
- **Akıllı butonlar:** bağlantı adresinden otomatik marka simgesi (Instagram, YouTube, Spotify, WhatsApp, TikTok, Google Haritalar, Etsy, Notion… 70'ten fazla), istenirse başka bir marka veya simge ya da simgesiz; butona arka plan görseli.
- **SVG yükleme:** SVG dosyaları tarayıcıda resme dönüştürülüp yüklenir ve diğer görseller gibi WebP olarak saklanır (JPG, PNG ve GIF yüklemeleri zaten WebP'ye dönüştürülüyordu).
- **Blog SEO ayarları:** SEO başlığı, arama motoru açıklaması, ayrı sosyal paylaşım görseli (1200×630), kanonik adres, arama motorlarında gizleme ve canlı arama sonucu önizlemesi.
- **SEO:**
  - Yönetim → Ayarlar → SEO'da Google Search Console, Bing ve Yandex doğrulama kodları.
  - Tüm site sayfalarında Twitter/X kart başlığı, açıklaması ve görseli.
  - Ana sayfada `WebSite` + `Organization` yapılandırılmış verisi (logo, iletişim, sosyal hesaplar).
  - Blog: yazılarda `article:published_time` / `modified_time`, `BreadcrumbList`, yayıncı logosu; liste sayfasında `Blog` verisi, `prev`/`next` bağlantıları ve her sayfanın kendi kanonik adresi (önceden 2. sayfa 1. sayfayı gösteriyordu).
  - Kapak görsellerinde yazı başlığı alternatif metin olarak; site haritasında blog için son güncelleme tarihi.
- Ana sayfa ve Özellikler sayfası yeni özelliklerle; paket kartlarındaki özellik listeleri (değiştirilmemişse) güncellendi.

### Değişenler
- **Yasal sayfalar güncellenir:** Çerez Politikası, Gizlilik Politikası, KVKK Aydınlatma Metni, Kullanım Koşulları ve Yardım/SSS sayfalarına pikseller, kısa bağlantılar, QR kodlar ve görsel dönüşümü eklenir. Güncelleme yalnızca 1.0.4 sayfa paketindeki metinleri taşıyan sayfalara uygulanır; şirket ve iletişim bilgileri değişmez, elle değiştirilmiş sayfalara dokunulmaz.
- Panel içi yardım metinleri yeni özelliklerle güncellendi; tema ayarlarının paketlere dağılımı düzeltildi.

### Düzeltilenler
- Geri yükleme, yedekte bulunmayan (sonradan eklenmiş) paket özelliklerini silmiyor; migration'ların eklediği satırlar yedekteki satırlarla çakışmıyor.
- Etiketsiz marka ikonları ekran okuyuculardan gizleniyor (boş etiketli `role="img"` yerine).

## [1.0.4] — 2026-10-24

Blog, yeni ana sayfa görseli, marka ayarları ve kimlik onayı düzeltmeleri. **Bir veritabanı değişikliği (migration) içerir** (`blog_posts` tablosu). 1.0.3 kurulumları [UPDATE.md](docs/UPDATE.md) ile güncellenir; "Güncellemeyi uygula" migration'ı yedek aldıktan sonra kendisi çalıştırır.

### Eklenenler
- **Blog:** `/blog` ve `/blog/<adres>` sayfaları; Yönetim → Blog'dan yazı oluşturma, düzenleme ve silme (içerik yetkisi olan yöneticiler). Türkçe/İngilizce başlık, özet, Markdown içerik, 16:9 kapak görseli, arama motoru açıklaması, taslak ve ileri tarihli (zamanlanmış) yayın, okuma süresi, ilgili yazılar. Yazılar site haritasına girer, `BlogPosting` yapılandırılmış verisi ve paylaşım görseli (kapak) üretilir. Blog başlığı ve açıklaması: Ayarlar → Blog. Yeni kurulumlarda üst menüde "Blog" bağlantısı vardır; mevcut kurulumlarda Yönetim → Menüler'den `/blog` eklenebilir.
- **Ayarlar → Bio sayfası markası:** bio sayfalarının altındaki "… ile oluşturuldu" rozetinin metni (`{site}` destekli), logosu ve bağlantısı.
- **Ayarlar → Marka:** yönetim paneli logosu (koyu kenar çubuğu için; boşsa site logosu) ve panel başlığı. Panel artık sabit "b" işareti yerine yüklenen logoyu gösterir.
- **Yeni ana sayfa görseli:** telefon içinde örnek bir bio sayfası, analitik kartı, anlık tıklama, özel alan adı ve tema seçici. Ayarlar → Ana sayfa → "Giriş bölümü görseli" ile kendi görselinizle değiştirilebilir. Hareket azaltma tercihi olan ziyaretçilerde animasyonlar kapalıdır.
- **Ayarlar → Güvenlik:** kimlik onayının geçerlilik süresi (varsayılan 30 dakika; önceden sabit 15 dakikaydı).

### Düzeltilenler
- **Kimlik onayından sonra işlem yapılmıyordu:** onay süresi dolmuşken bir ayar kaydedildiğinde, güncelleme uygulandığında veya hassas bir işlem başlatıldığında onay sayfasından sonra işlem yapılmadan geri dönülüyordu (ör. "yedekleme yapmıyor", "kaydetmiyor"). Bekleyen form artık şifreli olarak birkaç dakika saklanır, onaydan sonra kendiliğinden yeniden gönderilir ve formun bulunduğu sayfaya dönülür. Dosya yüklemesi içeren işlemlerde (ör. yedek yükleme) işlemi tekrarlamanız gerektiği açıkça söylenir.
- **Parola alanlarının etiketi "Parola hatalı." görünüyordu** (giriş, kayıt ve kimlik onayı sayfaları): etiket için hata mesajının çeviri anahtarı kullanılıyordu.

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
