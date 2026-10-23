# Kurulum kılavuzu (FTP + web sihirbazı)

Sunucuda terminal, Composer, npm veya Node.js **gerekmez**. Paket; production bağımlılıklarını (`vendor/`) ve derlenmiş arayüz dosyalarını (`public/build/`) içerir.

## 1. Hazırlık

> Bu kılavuz paylaşımlı hosting içindir. Coolify için: [COOLIFY.md](COOLIFY.md).

1. Biofy.TR için bir **alt alan adı** (ör. `bio.site.com`) veya ayrı bir alan adı oluşturun; WordPress'in alt klasörüne kurulmaz. Document Root ayarı için [SHARED_HOSTING.md](SHARED_HOSTING.md#yerleşimler).
2. Bu alan adının PHP sürümünü **8.3 veya 8.4** yapın (WordPress'inkini değiştirmeniz gerekmez).
3. WordPress'ten ayrı bir **MySQL/MariaDB veritabanı** ve tüm yetkilere sahip bir kullanıcı oluşturun. Veritabanı **boş** olmalıdır.
3. Alan adı için SSL sertifikasını etkinleştirin (önerilir).
4. `biofy-tr-1.0.0.zip` dosyasının bütünlüğünü kontrol edin: `biofy-tr-1.0.0.zip.sha256` dosyasındaki değer, dosyanın SHA-256 özetiyle aynı olmalıdır.

## 2. Dosyaları yükleme

Arşivi bilgisayarınızda açın ve [SHARED_HOSTING.md](SHARED_HOSTING.md)'deki yerleşimlerden birine göre FTP veya dosya yöneticisiyle yükleyin. Dosya yöneticisi ZIP açabiliyorsa arşivi yükleyip sunucuda açmak çok daha hızlıdır.

> `.htaccess` gibi nokta ile başlayan dosyaların da yüklendiğinden emin olun. FileZilla'da "Sunucu → Gizli dosyaları göster" seçeneğini açın.

## 3. Kurulum sihirbazı

Site adresinizi tarayıcıda açın. Sihirbaz otomatik başlar.

| Adım | Ne yapılır |
|---|---|
| 1. Gereksinimler | PHP sürümü, eklentiler, yazma izinleri, `vendor` ve derlenmiş dosyalar kontrol edilir. Zorunlu bir madde eksikse devam edilemez. |
| 2. Kurulum anahtarı | `storage/app/private/install.key` dosyasına rastgele bir anahtar yazılır. FTP veya dosya yöneticisiyle bu dosyayı açın ve içeriğini girin. Böylece kurulumu yalnızca sunucuya erişimi olan kişi yapabilir. |
| 3. Veritabanı | Bağlantı test edilir; sunucu sürümü ve veritabanının boş olduğu doğrulanır. |
| 4. Site ve superadmin | Site adresi (alt klasörsüz), site adı, dil, saat dilimi ve ilk superadmin hesabı. Parola en az 10 karakter olmalı, harf ve rakam içermelidir. |
| 5. Kurulum | `.env` yazılır (APP_KEY üretilir, `APP_DEBUG=false`), migration ve seed'ler çalışır, superadmin oluşturulur, kilit dosyası (`storage/app/private/installed.lock`) yazılır ve kurulum anahtarı silinir. |

Kurulum bittikten sonra `public/index.php` sihirbazı bir daha **asla** çalıştırmaz. Yeniden kurmak için veritabanını boşaltıp `installed.lock` ve `.env` dosyalarını silmeniz gerekir (bu işlem veri kaybına yol açar).

## 4. Kurulum sonrası (önerilen sıra)

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
| "PHP 8.3 veya üzeri gerekli" | Panelden PHP sürümünü yükseltin. |
| Her sayfada 404 (ana sayfa hariç) | `mod_rewrite`/`.htaccess` çalışmıyor. `.htaccess` yüklendi mi, AllowOverride açık mı? |
| 500 hatası, boş sayfa | `storage/logs/installer.log` ve `storage/logs/laravel-*.log` dosyalarına bakın; klasör izinlerini kontrol edin. |
| "Bu veritabanında zaten bir kurulum var" | Boş bir veritabanı veya farklı bir tablo öneki kullanın. |
| Kurulum yarıda kaldı | `installer.log`'a bakın, sorunu giderip aynı adımdan tekrar deneyin. Migration'lar ve seed'ler tekrar çalıştırılabilir. |
| E-postalar gelmiyor | SMTP ayarları; "Bağlantıyı test et" hata mesajı; SPF/DKIM kayıtları. |
