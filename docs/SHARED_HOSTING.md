# Paylaşımlı hosting rehberi ve uyumluluk kontrol listesi

## Uyumluluk kontrol listesi

Kurulum sihirbazının ilk adımı bunları otomatik kontrol eder.

| Gereksinim | Zorunlu | Not |
|---|---|---|
| PHP **8.3+** (8.4 önerilir) | ✔ | cPanel: "Select PHP Version" / "MultiPHP Manager" |
| Eklentiler: pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, dom, ctype, json, fileinfo, **gd**, **zip**, session, filter | ✔ | Çoğu hostingde varsayılan açık |
| Eklentiler: curl, sodium, intl, bcmath | önerilen | curl: HTTPS alan adı kontrolü, Google; sodium: yedek şifreleme |
| GD WebP desteği | önerilen | Yoksa görseller PNG kaydedilir |
| MySQL 5.7+ / MariaDB 10.3+, InnoDB, utf8mb4 | ✔ | Boş bir veritabanı |
| Apache + `mod_rewrite` + `.htaccess` (AllowOverride) | ✔ | LiteSpeed uyumludur. Nginx için aşağıdaki örneğe bakın. |
| Yazılabilir: `storage/*`, `bootstrap/cache`, `public/media`, ana klasör (`.env` için) | ✔ | Genelde 755 klasör / 644 dosya yeterli |
| `memory_limit` ≥ 128M, `max_execution_time` ≥ 60 | önerilen | Yedekleme/geri yükleme için daha yüksek değerler iyidir |
| `dns_get_record` fonksiyonu | önerilen | Özel alan adı doğrulaması için |
| SSL sertifikası (Let's Encrypt/AutoSSL) | önerilen | Ana alan adı ve müşteri alan adları için |
| Cron | isteğe bağlı | Yoksa uygulama çalışır; bakım görevleri yönetici paneli ziyaretlerinde hafif modda çalışır |

**Desteklenmeyen durumlar:** PHP 8.2 ve altı · alt klasöre kurulum (`site.com/biofy`) · `.htaccess`'in yok sayıldığı Apache yapılandırmaları (Nginx dışında) · yalnızca PostgreSQL sunan hostlar. Sihirbaz bu durumlarda durur ve sebebini gösterir.

## Yerleşim A: web kökü `public/` (önerilen)

Hosting paneliniz alan adının "Document Root" ayarını değiştirmeye izin veriyorsa bu yerleşimi kullanın.

```
/home/kullanici/biofy/          ← paketin tüm içeriği
/home/kullanici/biofy/public    ← alan adının Document Root'u
```

- cPanel: Domains → ilgili alan adı → Document Root: `biofy/public`
- Plesk: Hosting Settings → Document root: `/biofy/public`

## Yerleşim B: uygulama dışarıda, yalnızca `public` içeriği `public_html`'de

Document Root değiştirilemiyorsa uygulamayı web kökünün **dışına** koyun:

```
/home/kullanici/biofy/           ← paketin tüm içeriği (klasör adı tam olarak "biofy" olmalı)
/home/kullanici/public_html/     ← biofy/public klasörünün İÇERİĞİ (index.php, .htaccess, build/, media/)
```

`public_html/index.php`, uygulamayı otomatik olarak `../biofy` içinde bulur. Kurulum sihirbazı bu durumu algılar ve `bootstrap/public-path.php` dosyasını yazar; cron/CLI komutları da doğru medya klasörünü kullanır.

## Yerleşim C: her şey `public_html` altında (son çare)

Ana klasöre erişim hiç yoksa paketin tamamını `public_html` içine yükleyin. Kök `.htaccess` tüm istekleri `public/`'e yönlendirir; her hassas klasörde erişimi engelleyen bir `.htaccess` bulunur. Kurulumdan sonra **mutlaka** Yönetim → Sistem → "Hassas dosya erişim testi"ni çalıştırın ve tüm satırların yeşil olduğunu doğrulayın. Bu yerleşim, sunucu `.htaccess` dosyalarını uyguladığı sürece güvenlidir.

## Nginx (VPS / bazı yönetilen hostlar)

```nginx
server {
    server_name biofy.tr *.biofy.tr;      # müşteri alan adları için default_server da kullanılabilir
    root /var/www/biofy/public;
    index index.php;
    client_max_body_size 8m;

    location ~ /\.(?!well-known) { deny all; }
    location /media/ { location ~ \.php$ { deny all; } try_files $uri =404; }
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}
```

## Müşteri alan adları için sunucu ayarı

Kullanıcıların alan adları sizin sunucunuza yönlendiğinde, web sunucusunun bu alan adlarını aynı `public/` klasörüne yönlendirmesi gerekir:

- **cPanel:** her müşteri alan adını "Alias / Parked Domain" olarak ekleyin (Document Root aynı olur) ve AutoSSL'in sertifika üretmesini bekleyin. Bu işlem hosting paneli tarafındadır; uygulama sertifika üretmez.
- **VPS:** varsayılan sunucu bloğu (catch-all) + Caddy "on-demand TLS" veya certbot.
- Yönetim → Ayarlar → API ve alan adları bölümüne kullanıcılara gösterilecek CNAME hedefini ve/veya A kaydı IP'sini girin.

Uygulama, bilinmeyen alan adlarına gelen istekleri her durumda reddeder.
