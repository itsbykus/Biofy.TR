# Mimari

Biofy.TR, paylaşımlı hostingde çalışacak şekilde tasarlanmış **modüler bir Laravel monolitidir**. Sürekli çalışan bir süreç (queue worker, websocket, Redis) gerektirmez.

## Çalışma ortamı varsayımları

| Konu | Karar | Gerekçe |
|---|---|---|
| Framework | Laravel 13 (PHP ^8.3) | Ekim 2026 itibarıyla güncel kararlı sürüm; güvenlik desteği 2028 Q1'e kadar. Laravel 12'nin güvenlik desteği Şubat 2027'de biter. |
| Veritabanı | MySQL 5.7+ / MariaDB 10.3+ (testlerde SQLite) | Paylaşımlı hostinglerde standart. Migration'lar MariaDB 11.4 üzerinde doğrulandı. |
| Kuyruk | `sync` | Worker yok; e-postalar istek içinde gönderilir, hatalar kullanıcı işlemini bozmaz (`App\Services\Mail\Mailer`). |
| Cache | `file` | Kurulum gerektirmez. Ayarlar ve yetkiler sürüm anahtarlı cache'lenir. |
| Oturum | `database` | Kullanıcı oturumlarını listeleyip sonlandırabilmek için. |
| Ön yüz | Blade + derlenmiş Tailwind 4 + vanilla JS modülleri | Sunucuda Node gerekmez; profil sayfası ~4 KB (gzip) CSS+JS. |
| Grafikler | Sunucu tarafında SVG (`App\Support\SvgChart`) | JS grafik kütüphanesi yok, CSP ile uyumlu. |
| Fontlar / ikonlar | Fontsource (OFL), Lucide (ISC), Simple Icons (CC0) — paket içinde | Ziyaretçiden üçüncü taraf isteği yok (KVKK). |
| Medya | `public/media` (yeniden kodlanmış görseller) | `storage:link` (symlink) gerektirmez. |
| Dağıtım | Paylaşımlı hosting (FTP + web sihirbazı) veya Coolify (Docker, `biofy:container-setup`) | Aynı kod; yalnızca kurulum/güncelleme giriş noktası farklı ([DEPLOYMENT.md](DEPLOYMENT.md)). |

## Modüller ve sorumluluklar

| # | Modül | Ana sınıflar |
|---|---|---|
| 1 | Kimlik doğrulama | `Http\Controllers\Auth\*`, `Support\Totp`, `Services\SecurityLogger` |
| 2 | Profiller / bio sayfaları | `Models\Profile`, `Services\ProfileService`, `Services\ProfileRenderer`, `ProfilePageController` |
| 3 | Bloklar ve bağlantılar | `Blocks\BlockRegistry`, `Blocks\Types\*` (20 tür), `Services\BlockService`, `Services\SocialLinkService` |
| 4 | Tema sistemi | `Themes\ThemeRegistry` (12 tema), `Themes\ThemeSettings` |
| 5 | Paketler ve yetkiler | `Services\Entitlements`, `Models\Package`, `FeatureDefinition`, `UserFeatureOverride`, `UserPackageAssignment` |
| 6 | Superadmin paneli | `Http\Controllers\Admin\*`, `Support\Permissions`, `Middleware\RequirePermission` |
| 7 | Kullanıcı paneli | `Http\Controllers\Dashboard\*` |
| 8 | Analitik | `Services\Analytics\AnalyticsRecorder`, `AnalyticsReporter` |
| 9 | SEO | `Site\SeoController`, profil `<head>` (canonical, OG, JSON-LD) |
| 10 | Özel alan adları | `Services\Domains\*`, `Middleware\HandleCustomDomains`, `CustomDomainController` |
| 11 | E-posta | `Services\Mail\*`, `Notifications\*`, `EmailTemplate` |
| 12 | Güvenlik ve denetim | `Services\AuditLogger`, `SecurityLogger`, `Middleware\SecurityHeaders` |
| 13 | Yedekleme | `Services\Backup\BackupService` |
| 14 | REST API | `Http\Controllers\Api\V1\*`, `Middleware\ApiAccess` |
| 15 | Yerelleştirme | `lang/tr`, `lang/en`, `Middleware\SetLocale` |
| 16 | Kurulum ve güncelleme | `install/*`, `Services\System\UpdateService`, `ReleaseVerifier` |
| 17 | Sistem ayarları ve marka | `Services\Settings`, `Support\SettingDefinitions`, `View\Composers\LayoutComposer` |

## İstek akışı

```
public/index.php
  ├─ PHP < 8.3 → okunabilir hata
  ├─ installed.lock yok → install/installer.php
  └─ Laravel
      Global: HandleCustomDomains → (TrustProxies…) → SecurityHeaders
        ├─ platform host'u → normal router
        ├─ doğrulanmış müşteri alan adı → yalnızca profil, /go, /vcard, robots (oturum/çerez yok)
        └─ bilinmeyen host → 404 (host header saldırılarına karşı)
      web: session, CSRF, SetLocale, CheckPendingUpdate, PlatformMaintenance, EnsureAccountIsActive
      api: auth:sanctum → ApiAccess (platform anahtarı, hesap, paket, kayıt) → throttle:api
```

## Taslak ↔ yayın modeli

- Editör her zaman **çalışma tablolarını** (`profiles`, `profile_blocks`, `profile_social_links`) değiştirir; `draft_revision` her değişiklikte artar.
- **Yayınla**, sayfanın tam bir anlık görüntüsünü (`profile_versions.snapshot`) üretir. Ziyaretçiler **yalnızca** bu anlık görüntüyü görür. Son 20 sürüm saklanır.
- **Değişiklikleri geri al**, çalışma tablolarını yayındaki anlık görüntüden yeniden kurar (blok kimlikleri korunur, analitik kopmaz).
- Her kaydetme isteği `revision` taşır. Uyuşmazlıkta `409` döner ve editör "başka sekmede değiştirildi" uyarısı gösterir. Ayrıca `BroadcastChannel` ile aynı sayfanın birden fazla sekmede açık olduğu algılanır.
- Zaman ayarlı bloklar (`starts_at`/`ends_at`) ve paket yetkileri **render sırasında** uygulanır. Bu yüzden cron gerekmez ve paket düşürülmesi anında yansır.

## Yetki çözümleme (Entitlements)

```
1. Platform kısıtı      feature_definitions.platform_enabled / platform_cap
2. Hesap durumu         askıda veya silme bekliyor → her şey kapalı (cache'ten bağımsız, her istekte)
3. Kullanıcıya özel     user_feature_overrides (süresi dolanlar yok sayılır)
4. Paket                geçerli user_package_assignments → yoksa varsayılan paket
5. Varsayılan           feature_definitions.default_value
```

Sonuçlar `entitlements:{user}:{user.entitlements_version}:{system.features_version}` anahtarıyla cache'lenir. Paket/özellik değişikliği global sürümü, kullanıcıya özel değişiklik kullanıcı sürümünü artırır. Böylece eski yetki asla kullanılmaz. Kontroller hem arayüzde hem servis/route/API katmanında yapılır.

## Kayıtlı mimari kararlar

1. **Embed'ler kullanıcıdan iframe kodu almaz.** URL'den kimlik (YouTube ID, Spotify türü/ID…) ayrıştırılır, iframe adresi izinli alan adında yeniden oluşturulur. Oynatıcılar ziyaretçi "yükle" demeden yüklenmez.
2. **Tıklama yönlendirmeleri** (`/go/{profil}/{blok}/{öğe}`) hedefi istekten değil yayındaki anlık görüntüden alır; açık yönlendirme mümkün değildir.
3. **Analitikte ham IP saklanmaz.** Ziyaretçi kimliği `HMAC(IP|UA|profil, günlük tuz)` ile üretilir. Tuz günlük değişir, eskiler 3 gün sonra silinir.
4. **Müşteri alan adları** oturum ve çerez başlatmaz; giriş/yönetim rotalarını hiç sunmaz (oltalama ve çerez çakışmasına karşı).
5. **Yedekler** SQL dökümü değil, tablo bazında JSON-lines'tır. Geri yükleme şemayı migration'larla kurar ve satırları parametreli sorgularla ekler; arşivden SQL metni çalıştırılmaz.
6. **Güncelleme** uzaktan kod indirmez. Dosyalar FTP ile yüklenir, panel imzalı manifesti doğrular ve migration'ları çalıştırır.
7. **Kullanıcı taklidi (impersonation)** bilinçli olarak dahil edilmemiştir (bkz. ROADMAP).
