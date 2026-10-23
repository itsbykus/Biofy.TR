# Kurulum kılavuzu (FTP + web sihirbazı)

Sunucuda terminal, SSH, Composer, npm veya Node.js **gerekmez**: ZIP'i yükleyip tarayıcıda sihirbazı tamamlamanız yeterlidir. Sürüm paketi, production bağımlılıklarını (`vendor/`) ve derlenmiş arayüz dosyalarını (`public/build/`) hazır olarak içerir.

> Composer ve npm yalnızca **paketi üreten** geliştirici bilgisayarında veya GitHub Actions'ta çalışır ([DEPLOYMENT.md](DEPLOYMENT.md#sürüm-paketi-üretme-paylaşımlı-hosting-için)). Hosting kullanıcısı bunları hiçbir zaman çalıştırmaz.
>
> Bu kılavuz paylaşımlı hosting içindir. Coolify için: [COOLIFY.md](COOLIFY.md).

## 1. Doğru dosyayı indirin

[GitHub → Releases](https://github.com/itsbykus/Biofy.TR/releases) sayfasında, sürümün **Assets** bölümünde şu dosyalar bulunur:

| Dosya | Ne işe yarar |
|---|---|
| **`biofy-tr-<sürüm>.zip`** (ör. `biofy-tr-1.0.1.zip`, ~11 MB) | **Kurulum paketi — bunu indirin.** `vendor/` ve `public/build/` dahildir, imzalıdır. |
| `biofy-tr-<sürüm>.zip.sha256` | Paketin SHA-256 özeti (indirmenin bozulmadığını kontrol etmek için) |
| `Source code (zip)` → `Biofy.TR-<sürüm>.zip`, `Source code (tar.gz)` | GitHub'ın her sürüme **otomatik** eklediği kaynak kodu. `vendor/` ve `public/build/` **yoktur**; hostingde çalışmaz. Yüklenirse sihirbaz ilk adımda durur ve doğru dosyayı söyler. |

İndirdiğiniz paketin özetini `.sha256` dosyasındaki değerle karşılaştırın; ikisi birebir aynı olmalıdır:

| Sistem | Komut |
|---|---|
| Windows (PowerShell) | `Get-FileHash .\biofy-tr-1.0.1.zip -Algorithm SHA256` |
| Windows (Komut İstemi) | `certutil -hashfile biofy-tr-1.0.1.zip SHA256` |
| macOS | `shasum -a 256 biofy-tr-1.0.1.zip` |
| Linux | `sha256sum -c biofy-tr-1.0.1.zip.sha256` (iki dosya aynı klasörde) |

## 2. Hazırlık

1. Biofy.TR için bir **alt alan adı** (ör. `bio.site.com`) veya ayrı bir alan adı oluşturun; WordPress'in alt klasörüne kurulmaz. Document Root ayarı için [SHARED_HOSTING.md](SHARED_HOSTING.md#yerleşimler).
2. Bu alan adının PHP sürümünü **8.3 veya 8.4** yapın (WordPress'inkini değiştirmeniz gerekmez).
3. WordPress'ten ayrı bir **MySQL/MariaDB veritabanı** ve tüm yetkilere sahip bir kullanıcı oluşturun. Veritabanı **boş** olmalıdır (veya kullanacağınız tablo önekiyle başlayan hiçbir tablo içermemelidir).
4. Alan adı için SSL sertifikasını etkinleştirin (önerilir).

## 3. Dosyaları yükleme

Arşivi bilgisayarınızda açın ve [SHARED_HOSTING.md](SHARED_HOSTING.md)'deki yerleşimlerden birine göre FTP veya dosya yöneticisiyle yükleyin. Dosya yöneticisi ZIP açabiliyorsa arşivi yükleyip sunucuda açmak çok daha hızlıdır. Arşivin içinde tek bir `biofy-tr-<sürüm>/` klasörü vardır; yerleşimlerde "paketin tamamı" bu klasörün **içeriği** demektir.

> `.htaccess` gibi nokta ile başlayan dosyaların da yüklendiğinden emin olun. FileZilla'da "Sunucu → Gizli dosyaları göster" seçeneğini açın. Sihirbaz, paketteki her dosyanın sunucuda olup olmadığını `release-manifest.json` ile kontrol eder; aktarım yarıda kaldıysa eksik dosyaları listeler.

## 4. Kurulum sihirbazı

Site adresinizi tarayıcıda açın. Sihirbaz otomatik başlar.

| Adım | Ne yapılır |
|---|---|
| 1. Gereksinimler | PHP sürümü, eklentiler, yazma izinleri, `vendor/`, `public/build/` (Vite manifesti ve referans verdiği her dosya) ve paketteki tüm dosyaların yüklendiği kontrol edilir. Zorunlu bir madde eksikse devam edilemez. |
| 2. Kurulum anahtarı | `storage/app/private/install.key` dosyasına rastgele bir anahtar (96 bit) yazılır. FTP veya dosya yöneticisiyle bu dosyayı açın ve içeriğini girin. Böylece kurulumu yalnızca sunucuya erişimi olan kişi yapabilir. |
| 3. Veritabanı | Bağlantı test edilir; sunucu sürümü doğrulanır. Seçilen tablo önekiyle başlayan **herhangi bir** tablo varsa (ör. önek boşken WordPress'in `wp_` tabloları) kurulum reddedilir. Tek istisna, bu sihirbazın yarıda kalmış bir denemesidir (aşağıya bakın). |
| 4. Site ve superadmin | Site adresi (alt klasörsüz), site adı, dil, saat dilimi ve ilk superadmin hesabı. Parola en az 10 karakter olmalı, harf ve rakam içermelidir. |
| 5. Kurulum | `.env` yazılır (`APP_KEY` üretilir, `APP_ENV=production`, `APP_DEBUG=false`, izin `640`), migration ve seed'ler çalışır, superadmin tek bir veritabanı işleminde (transaction) oluşturulur, kilit dosyası (`storage/app/private/installed.lock`) yazılır ve kurulum anahtarı silinir. |

Kurulum bittikten sonra `public/index.php` sihirbazı bir daha **asla** çalıştırmaz. Yeniden kurmak için veritabanını boşaltıp `installed.lock` ve `.env` dosyalarını silmeniz gerekir (bu işlem veri kaybına yol açar).

**Yarıda kalan kurulum:** Son adım bir hata, zaman aşımı veya kapanan tarayıcı yüzünden tamamlanmazsa kilit yazılmaz ve sihirbaz açık kalır. Sorunu giderip (`storage/logs/installer.log`) sihirbazı yeniden açın; tarayıcı oturumu kaybolduysa baştan başlayıp aynı veritabanını girin. Şema oluşmuş ama henüz hesap oluşturulmamışsa veritabanı kabul edilir, migration ve seed'ler kaldığı yerden devam eder ve önceki denemenin `APP_KEY`'i korunur (o sırada şifrelenmiş ayarlar okunabilir kalır).

## 5. Kurulum sonrası (önerilen sıra)

1. Superadmin hesabıyla giriş yapın → Güvenlik → **iki adımlı doğrulamayı** açın.
2. Yönetim → Sistem → **Hassas dosya erişim testi** (hepsi yeşil olmalı).
3. Yönetim → Ayarlar → **E-posta (SMTP)**: sunucu bilgilerini girin, "Bağlantıyı test et" ve "Test e-postası gönder" ile doğrulayın. SMTP olmadan doğrulama ve parola sıfırlama e-postaları gönderilmez (yalnızca log'a yazılır).
4. Yönetim → Ayarlar → **Marka**: logo, favicon ve renkler.
5. **Google ile giriş** (isteğe bağlı): Google Cloud Console'da OAuth istemci kimliği (Web) oluşturun, yönetimdeki "Yetkilendirilmiş yönlendirme URI" adresini ekleyin, Client ID/Secret'ı girin.
6. **Cron** (isteğe bağlı): [OPERATIONS.md](OPERATIONS.md#cron).
7. Yönetim → **Yedekler** → ilk yedeği alın. `.env` dosyasının bir kopyasını güvenli bir yerde saklayın: APP_KEY olmadan şifreli yedekler ve kayıtlı sırlar okunamaz.
8. Hukuki sayfaları (Yönetim → Sayfalar) şirket bilgilerinizle güncelleyin ve hukuk danışmanınıza inceletin.

## Sorun giderme

| Belirti | Çözüm |
|---|---|
| "vendor/ veya public/build/ klasörü bulunamadı" | GitHub'ın "Source code" arşivi yüklenmiş. Releases → Assets altındaki `biofy-tr-<sürüm>.zip` dosyasını yükleyin ([1. adım](#1-doğru-dosyayı-indirin)). Sunucuda Composer/npm çalıştırmayın. |
| "Paketteki tüm dosyalar yüklendi ✖" | FTP aktarımı eksik kalmış veya gizli dosyalar atlanmış. Satırda ilk eksik dosyalar yazar; paketi tekrar yükleyin. |
| "PHP 8.3 veya üzeri gerekli" | Panelden PHP sürümünü yükseltin. |
| Her sayfada 404 (ana sayfa hariç) | `mod_rewrite`/`.htaccess` çalışmıyor. `.htaccess` yüklendi mi, AllowOverride açık mı? |
| 500 hatası, boş sayfa | `storage/logs/installer.log` ve `storage/logs/laravel-*.log` dosyalarına bakın; klasör izinlerini kontrol edin. |
| "Bu veritabanında bu tablo önekiyle başlayan tablolar zaten var" | Veritabanında başka bir uygulamanın (ör. WordPress) veya tamamlanmış bir kurulumun tabloları var. Boş bir veritabanı veya farklı bir tablo öneki (ör. `bio_`) kullanın. |
| Kurulum yarıda kaldı | `installer.log`'a bakın, sorunu giderip sihirbazı yeniden açın (gerekirse baştan, aynı veritabanıyla). Ayrıntı: [yarıda kalan kurulum](#4-kurulum-sihirbazı). |
| E-postalar gelmiyor | SMTP ayarları; "Bağlantıyı test et" hata mesajı; SPF/DKIM kayıtları. |
