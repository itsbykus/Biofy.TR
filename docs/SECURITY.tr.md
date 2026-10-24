# Güvenlik

**Türkçe** · [English](SECURITY.md)

## Kimlik doğrulama

| Konu | Uygulama |
|---|---|
| Parolalar | bcrypt (12 tur); politika: en az 10 karakter (ayarlanabilir), harf + rakam, production'da sızdırılmış parola kontrolü (Have I Been Pwned k-anonymity) |
| Giriş | E-posta+IP başına dakikada 5, IP başına 20 deneme; bilinmeyen kullanıcı ile hatalı parola aynı yanıtı ve benzer süreyi alır; başarılı girişte oturum kimliği yenilenir (fixation) |
| 2FA | RFC 6238 TOTP (testlerle doğrulandı), aynı zaman adımının tekrar kullanımı engellenir, 8 tek kullanımlık kurtarma kodu (hash'li), yöneticiler için zorunlu kılınabilir |
| Parola sıfırlama | Laravel token'ları (60 dk), hesap var/yok bilgisini sızdırmayan yanıt, başarılı sıfırlamada tüm oturumlar kapanır |
| Bağlantılar | E-postadaki tüm adresler `APP_URL`'den üretilir; bilinmeyen `Host` başlığı uygulamaya hiç ulaşmaz (host header poisoning) |
| Google | Yalnızca doğrulanmış e-postalar; eşleştirme Google `sub` kimliğiyle; aynı e-postalı yerel hesapla **otomatik birleştirme yok** |
| Yeniden kimlik doğrulama | Hesap dışa aktarma/silme, 2FA değişiklikleri, oturum kapatma, API token oluşturma, ayar değişiklikleri, yedek indirme/geri yükleme, rol atama: 15 dakikalık onay penceresi |
| Askıya alma | Etkisi bir sonraki istekte (beni-hatırla dahil) başlar; oturumlar ve API token'ları silinir |

## Yetkilendirme

- Superadmin yalnızca kurulumda veya başka bir superadmin tarafından (parola onayı + denetim kaydı) atanabilir. Son superadmin kaldırılamaz veya silinemez.
- Yönetim rotaları izin bazlı middleware (`permission:*`) ile korunur. Her izin aynı zamanda bir Gate yeteneğidir (`@can`).
- Nesne bazlı erişim (IDOR/BOLA): policy + sahiplik sorguları, API'de 404.
- Paket özellikleri arayüzde gizlenmenin yanında servis, route (`feature:` middleware) ve API seviyesinde de uygulanır.

## İçerik güvenliği

- Kullanıcı içeriği her yerde escape edilerek basılır; blok metinleri düz metindir (HTML/Markdown yorumlanmaz).
- URL'ler `SafeUrl` ile doğrulanır: yalnızca `http/https`; `javascript:`, `data:`, `vbscript:`, `file:`, `//`, kimlik bilgisi içeren ve kontrol karakterli adresler reddedilir.
- Embed'ler yalnızca izinli alan adlarından (`config/biofy.php → embed_hosts`) ve uygulamanın ürettiği adreslerle yüklenir; iframe'ler `sandbox` ile ve tıklamayla yüklenir.
- Tema ayarları yalnızca `#RRGGBB` renk, sınırlandırılmış sayı veya sabit liste değeri kabul eder; CSS enjeksiyonu mümkün değildir.
- CMS (yönetici) Markdown'ı ham HTML escape edilerek ve güvensiz bağlantılar kapatılarak render edilir.
- **Content-Security-Policy:** `script-src 'self' 'nonce-…'`, `style-src 'self' 'nonce-…'`, `object-src 'none'`, `frame-ancestors 'self'`; profil sayfalarında yalnızca izinli oynatıcı alan adları `frame-src`'e eklenir. Google / Meta / TikTok etiketi taşıyan sayfalar (Yönetim → Ayarlar → İzleme etiketleri veya Pro kullanıcının bio sayfası pikselleri) yalnızca ilgili sağlayıcının alan adlarını `script-src`, `img-src`, `connect-src` ve `frame-src`'e ekler; sayfada yalnızca sıkı doğrulanmış kimlikler bulunur ve sağlayıcı betikleri ziyaretçi "Kabul et" dedikten sonra uygulamanın kendi yükleyicisiyle eklenir. Ek başlıklar: `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, isteğe bağlı HSTS.
- CSRF: tüm web formları (Laravel `PreventRequestForgery`); oturum çerezleri `HttpOnly`, HTTPS'te `Secure`, `SameSite=Lax`.

## Dosya yükleme

- Yalnızca JPEG/PNG/WebP/GIF. Tür, dosya adından değil **içerikten** (`finfo::buffer`) belirlenir. SVG kabul edilmez.
- 5 MB üst sınır, 40 megapiksel sınırı (decompression bomb), kullanıcı kotası.
- Her görsel GD ile **yeniden kodlanır** (meta veri ve eklenmiş içerik atılır) ve rastgele 32 karakterlik bir adla `public/media/{amaç}/{yıl}/{ay}/` altına yazılır.
- SVG yüklemeleri tarayıcıda resme dönüştürülür (`<img>` üzerinden çizildiği için dosyadaki betikler çalışmaz) ve PNG olarak gelir; sunucuya ulaşan ham SVG reddedilir. Tüm görseller GD ile yeniden kodlanır ve WebP olarak saklanır.
- Kısa bağlantılar (`/s/{kod}`) yalnızca düz http(s) hedeflerini kabul eder (kimlik bilgisi, `javascript:` yok), sitenin başka bir kısa bağlantısına yönlendirilemez, `X-Robots-Tag: noindex` ile gönderilir ve yöneticiler tarafından kapatılabilir.
- `public/media/.htaccess` PHP/CGI çalıştırmayı kapatır, yalnızca görsel uzantılarını sunar ve `sandbox` CSP ekler.
- Kullanımdaki dosya silinemez; başka kullanıcının dosyası seçilemez.

## Sırlar ve loglar

- `.env` dağıtım paketine dahil edilmez; kurulumda `0640` izinle yazılır.
- SMTP parolası, Google client secret ve cron anahtarı veritabanında APP_KEY ile şifrelenir, arayüzde gösterilmez (yalnızca "kayıtlı" bilgisi).
- Denetim ve güvenlik kayıtlarında `password`, `secret`, `token`, `key` gibi alanlar `[redacted]` olarak yazılır. Gönderilemeyen e-posta hataları da temizlenerek loglanır.
- Production'da `APP_DEBUG=false`; kullanıcıya stack trace gösterilmez, API hataları standart JSON zarfıyla döner.

## Web kök dizini

- Önerilen kurulumda web kökü `public/`'tir; uygulama kodu erişilemez.
- Tüm paketin `public_html` altına konması gerekiyorsa kök `.htaccess` her isteği `public/`'e yönlendirir. Ek olarak `app`, `bootstrap`, `config`, `database`, `install`, `lang`, `resources`, `routes`, `storage`, `vendor` ve `docs` klasörlerinde `Require all denied` vardır; nokta ile başlayan dosyalar reddedilir.
- **Doğrulama:** Yönetim → Sistem → "Hassas dosya erişim testi", `.env`, loglar, `vendor`, `composer.json`, kurulum dosyaları ve kilit dosyası için gerçek HTTP istekleri yapar.

## KVKK / gizlilik

- Ziyaretçiler için çerez yok; analitikte IP saklanmaz, günlük değişen takma kimlik kullanılır. Ülke bilgisi yalnızca açıkça etkinleştirilen Cloudflare başlığından alınır; tahmin yapılmaz.
- Fontlar ve ikonlar sunucudan sunulur; üçüncü taraf oynatıcılar yalnızca ziyaretçinin onayıyla yüklenir.
- Kullanıcı kendi verilerini JSON olarak indirebilir ve hesap silme talebi oluşturabilir (bekleme süresi ayarlanabilir).
- Saklama süreleri yönetimden ayarlanır (analitik, güvenlik kaydı).

## Bilinen sınırlamalar ve öneriler

- Sitenin HTTPS ile kurulması zorunlu değildir ama **şiddetle önerilir**; HTTPS olmadan `Secure` çerez bayrağı kullanılamaz.
- Sunucu Cloudflare veya proxy arkasındaysa `.env`'de `TRUSTED_PROXIES` ayarlanmalıdır; aksi halde hız sınırları proxy IP'sine göre çalışır.
- 2FA yöneticiler için varsayılan olarak zorunlu değildir; kurulumdan sonra açılması önerilir (Ayarlar → Güvenlik).
- Bağımlılık güvenlik güncellemeleri için yeni sürüm paketlerini düzenli takip edin.
