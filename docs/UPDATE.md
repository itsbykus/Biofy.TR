# Güncelleme

Biofy.TR uzaktan kod **indirmez** ve yüklenen ZIP'lerden kod çalıştırmaz. Güncelleme dosyaları FTP ile yüklenir; panel bütünlüğü doğrular ve veritabanını günceller.

## Sürüm bütünlüğü

Her sürüm paketi şunları içerir:

| Dosya | Amaç |
|---|---|
| `release-manifest.json` | Paketteki tüm dosyaların SHA-256 özetleri ve sürüm numarası |
| `release-manifest.sig` | Manifestin Ed25519 imzası (base64) |
| `release-public-key.txt` | İmzayı doğrulayan açık anahtar |

İlk kurulumda açık anahtar `.env` dosyasına (`BIOFY_RELEASE_PUBLIC_KEY`) **sabitlenir**. Sonraki güncellemelerde paketle gelen yeni `release-public-key.txt` dikkate alınmaz; manifest kurulumda sabitlenen anahtarla doğrulanmalıdır. Böylece farklı bir anahtarla imzalanmış (değiştirilmiş) bir paket "imza geçersiz" olarak raporlanır. İmzalama anahtarı yalnızca geliştiricinin bilgisayarındadır, pakette bulunmaz.

Yönetim → Sistem → **Dosya bütünlüğünü doğrula** şu sonuçlardan birini verir:

| Durum | Anlamı |
|---|---|
| `ok` | İmza geçerli, tüm dosyalar manifestle aynı |
| `files_mismatch` | Eksik veya değiştirilmiş dosyalar var (liste gösterilir) — eksik yükleme veya müdahale |
| `bad_signature` | Manifest sabitlenmiş anahtarla imzalanmamış — **paketi kullanmayın** |
| `unsigned` | Anahtar veya imza yok (yalnızca özetler kontrol edilir) |
| `no_manifest` | Manifest dosyası yüklenmemiş |

## Adımlar

1. **Yedek alın:** Yönetim → Yedekler → "Şimdi yedekle" ve dosyayı indirin. `.env` dosyasının bir kopyasını saklayın.
2. Yeni paketin `.sha256` değerini kontrol edin ve arşivi açın.
3. Yeni dosyaları FTP ile mevcut kurulumun **üzerine** yükleyin. Şunlara **dokunmayın**:
   - `.env`
   - `storage/` (yedekler, loglar, kurulum kilidi)
   - `public/media/` (kullanıcı görselleri)
   - `bootstrap/public-path.php` (varsa)
4. Yükleme sürerken ve bitince, yeni kod sürümü veritabanı sürümünden büyükse genel site otomatik olarak "Güncelleniyor" sayfası (503, `Retry-After`) gösterir. Yöneticiler giriş yapmaya devam edebilir.
5. Yönetici olarak giriş yapın → Yönetim → Sistem. Bekleyen migration'lar listelenir.
6. **Dosya bütünlüğünü doğrula** → sonuç `ok` olmalı.
7. **Güncellemeyi uygula** (parola onayı ister): önce otomatik bir `pre_update` yedeği alınır; yedek başarısız olursa güncelleme durur. Ardından migration'lar çalışır, önbellekler temizlenir ve yeni sürüm kaydedilir. İşlem denetim kaydına yazılır.
8. Site normale döner. Değişiklikler için `CHANGELOG.md`'ye bakın.

## Sorun olursa

- Migration hata verirse işlem durur, hata denetim kaydına yazılır ve kullanılan yedeğin adı gösterilir.
- **Geri dönüş:** önceki sürümün dosyalarını yeniden yükleyin, ardından Yönetim → Yedekler'den `pre_update` yedeğini geri yükleyin. (Geri yükleme veritabanını yedekteki hâline getirir; yedekten sonraki veriler kaybolur.)
- Dosya yüklemesi yarıda kaldıysa: eksik dosyaları tekrar yükleyin ve bütünlüğü yeniden doğrulayın.

## Geliştiriciler için: sürüm üretme

```bash
php scripts/build-release.php            # npm ci + ikonlar + vite build + composer --no-dev + manifest + imza + zip
php scripts/build-release.php --no-npm   # derlenmiş public/build zaten güncelse
```

- İmza anahtarı `BIOFY_SIGNING_KEY` ortam değişkeninden veya `~/.biofy-devtools/release-signing.key` dosyasından okunur; yoksa oluşturulur. **Bu dosyayı kaybetmeyin ve paylaşmayın:** kaybolursa sonraki paketler mevcut kurulumlarda `bad_signature` verir (kurulumdaki `.env` anahtarı elle güncellenmedikçe).
- Sürüm numarası `config/biofy.php → version` değeridir. Yeni sürümde artırılmalıdır; aksi halde panel güncelleme beklediğini algılamaz.
- Betik pakette `.env`, SQLite, log, `node_modules`, `tests`, PHPUnit veya Faker bulunursa hata verip durur.
