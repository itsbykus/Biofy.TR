# Yol haritası

v1.0.0'ın bilinçli olarak dışında bırakılan veya sonraki sürümlere ayrılan konular.

## Ödeme altyapısı (v1'de yok)

v1 ödeme almaz; paketler yönetici tarafından atanır ve fiyatlar bilgilendirme amaçlıdır. Sonraki entegrasyon için zemin hazırdır:

- `user_package_assignments` başlangıç/bitiş/iptal tarihlerini, kaynağı (`admin`, `system`, `registration`) ve atayanı tutar. Bir ödeme sağlayıcısı (iyzico, PayTR, Stripe) yeni bir `source` değeriyle atama oluşturabilir.
- Yetkiler atama tarihlerinden render ve istek anında hesaplanır; süre dolunca cron olmadan varsayılan pakete dönülür.
- Önerilen yapı: `App\Services\Billing\PaymentProvider` arayüzü (`createCheckout`, `handleWebhook`, `cancel`), imzası doğrulanan webhook uç noktası, `payments` ve `invoices` tabloları, e-fatura/e-arşiv entegrasyonu için ayrı bir servis. Webhook işlemleri idempotent olmalıdır.

## Planlanan

- Müşteri alan adlarının Coolify API veya cPanel UAPI ile otomatik eklenmesi
- IDN (Türkçe karakterli) alan adı desteği
- Kuyruk (queue worker) desteği olan sunucular için e-postaların arka planda gönderilmesi ve toplu duyuru e-postası
- Profil sayfaları için tam sayfa önbelleği
- Ek ödeme yöntemleri ve faturalama (yukarıya bakın)
- Ekip/ajans hesapları (bir hesabın birden fazla kullanıcıyla yönetilmesi)
- WebAuthn / passkey ile giriş

## Bilinçli olarak dahil edilmeyenler

- **Kullanıcı taklidi (impersonation):** Destek için kullanışlı olsa da kötüye kullanım ve KVKK riskleri nedeniyle eklenmedi. Eklenirse: varsayılan kapalı, ayrı izin, kullanıcıya bildirim, salt okunur mod, süre sınırı ve ayrıntılı denetim kaydı ile.
- Kullanıcıların özel HTML/JavaScript/CSS eklemesi (XSS ve oltalama riski).
- Uzak sunucudan otomatik kod indirip kuran güncelleyici (bkz. [UPDATE.md](UPDATE.md)).
- Alt klasöre kurulum (`site.com/biofy`).
- Bu iki dağıtım yöntemi dışındaki platformlar (Kubernetes, sunucusuz vb.).
