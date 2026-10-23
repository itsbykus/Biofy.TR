# Veritabanı

İlişkisel şema; tüm yabancı anahtarlar, benzersiz kısıtlar ve indeksler migration'larda tanımlıdır (`database/migrations`). Temiz bir MariaDB 11.4 ve SQLite üzerinde `migrate:fresh --seed` doğrulanmıştır. Motor InnoDB, karakter seti utf8mb4'tür.

## Tablolar

| Grup | Tablo | Açıklama |
|---|---|---|
| Hesap | `users` | `username` (benzersiz, küçük harf), `status` (active/suspended), 2FA alanları (şifreli), `deletion_requested_at`, `entitlements_version` |
| | `social_accounts` | Google bağlantısı: `(provider, provider_user_id)` benzersiz; e-postayla eşleştirme yapılmaz |
| | `password_reset_tokens`, `sessions` | Laravel standart; oturumlar veritabanında |
| | `personal_access_tokens` | Sanctum API token'ları (SHA-256 hash), yetki kapsamları, son kullanım/süre |
| RBAC | `roles`, `permissions`, `permission_role`, `role_user` | `superadmin` (tüm yetkiler), `moderator`; atamayı yapan kişi saklanır |
| Ayarlar | `system_settings` | anahtar/değer; gizli değerler `is_encrypted=1` ile APP_KEY'le şifreli |
| Paketler | `packages` | Çevrilebilir ad/açıklama (JSON), fiyat (bilgi amaçlı), görünürlük, Premium iletişim mesajı, kullanıcıya özel paket |
| | `feature_definitions` | Özellik kataloğu: tür (boolean/integer), varsayılan, `platform_enabled`, `platform_cap` |
| | `package_features` | `(package_id, feature_key)` benzersiz |
| | `user_package_assignments` | Paket geçmişi: başlangıç/bitiş/iptal, kaynak, atayan |
| | `user_feature_overrides` | Kullanıcıya özel izin/limit (süreli) |
| Profil | `profiles` | `slug` benzersiz, durum (draft/published/suspended/archived), tema anahtarı + seyrek tema ayarları (JSON), `draft_revision`, `published_version_id` |
| | `profile_versions` | Yayınlanan anlık görüntüler (son 20) |
| | `profile_blocks` | Tür + doğrulanmış `data` (JSON), sıra, görünürlük, zamanlama |
| | `profile_social_links`, `profile_slug_redirects`, `reserved_usernames` | |
| Medya | `media_files` | Sahip, amaç, MIME, boyut, sha256; dosya yolu rastgele |
| Tema | `themes` | Tema kullanılabilirliği / Pro durumu / sıra (tasarım kodda) |
| Alan adı | `custom_domains`, `domain_verifications` | `hostname` benzersiz; DNS/HTTPS kontrol geçmişi (son 30) |
| Analitik | `analytics_events` | Ham olaylar (IP yok; 32 karakterlik takma kimlik); `(profile_id, created_at)` ve tekrar-kontrol indeksi |
| | `analytics_daily_summaries` | `(profile_id, block_id, date)` benzersiz sayaçlar; panel sorguları buradan |
| | `analytics_salts` | Günlük tuzlar (3 gün saklanır) |
| Denetim | `audit_logs`, `security_events`, `api_request_logs` | Sırlar yazılmaz (`AuditLogger::sanitize`) |
| İçerik | `pages`, `email_templates`, `contact_messages` | CMS sayfaları (TR/EN), e-posta şablonları, iletişim kutusu |
| Yedek | `backup_records` | Dosya adı, tür, durum, sağlama toplamı, manifest |
| Laravel | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations` | |

## Tenant izolasyonu

- Kullanıcıya ait her kayıt `user_id` ile (doğrudan veya `profiles` üzerinden) bağlanır ve kullanıcı silindiğinde `cascade` ile temizlenir.
- Panel: profil işlemleri `ProfilePolicy` ile, alt nesneler (blok, sosyal bağlantı, medya, alan adı, API token) sahibin kimliğiyle sorgulanır. Başka bir kullanıcının kimliği verildiğinde 403/404 döner.
- API: her sorgu `where('user_id', auth()->id())` ile başlar; başka tenant'ın kaydı **404** döner (varlığı sızdırılmaz).
- İstemciden gelen `user_id` hiçbir yerde kullanılmaz.
- Testler: `ProfileLifecycleTest::test_users_cannot_touch_other_users_pages`, `ApiTest::test_profile_and_link_crud_with_tenant_isolation`, `AnalyticsTest`, `CustomDomainTest::test_domain_can_belong_to_one_account_only`.

## Performans notları

- Panel analitikleri ham olay tablosunu taramaz; günlük özet tablosu kullanılır. Ayrıntılı kırılımlar yalnızca seçili, en fazla 366 günlük aralıkta ve indeksli sorgularla çalışır.
- Ayarlar tek bir cache girdisinden okunur; tema kullanılabilirliği ve rezerve kelimeler de cache'lidir.
- Yayındaki profil sayfası tek bir anlık görüntüden render edilir (blok/medya için ek sorgu yok).
- Eski analitik olayları, güvenlik kayıtları ve API logları bakım görevleriyle sınırlı partiler halinde silinir.
