# REST API v1

- **Temel adres:** `https://<site>/api/v1`
- **Biçim:** JSON (`Accept: application/json`), UTF-8, tarihler ISO 8601
- **Makine okunur şema:** [openapi.yaml](openapi.yaml) (OpenAPI 3.1)

## Erişim koşulları

1. Yönetim → Ayarlar → API'de **API açık** olmalıdır (kapalıysa `503 unavailable`).
2. Kullanıcının paketinde **API erişimi** özelliği olmalıdır (varsayılan olarak Pro ve Premium; yoksa `403 feature_unavailable`).
3. Hesap aktif olmalıdır (askıdaki hesapların token'ları silinir).

## Kimlik doğrulama

Panel → **API** sayfasından token oluşturun (parola onayı istenir). Token **yalnızca bir kez** gösterilir; veritabanında SHA-256 özeti saklanır. İsteğe bağlı olarak son kullanma tarihi verilebilir.

```http
GET /api/v1/me
Authorization: Bearer 12|kG3...
Accept: application/json
```

### Yetki kapsamları (abilities)

| Kapsam | İzin verir |
|---|---|
| `profiles:read` | Sayfaları listeleme/görüntüleme |
| `profiles:write` | Sayfa oluşturma, düzenleme, yayınlama/yayından kaldırma |
| `links:read` | Bağlantıları listeleme |
| `links:write` | Bağlantı ekleme, düzenleme, silme |
| `analytics:read` | Analitik özetleri |
| `domains:read` | Özel alan adları ve durumları |

Eksik kapsam `403 forbidden` döndürür. Yönetici (superadmin) işlemleri API'de **bilinçli olarak** yoktur.

## Hız sınırı

Kullanıcı başına dakikalık limit paketten gelir (`api_rate_limit_per_minute`; varsayılan Pro 60, Premium 600; Pro'da en fazla 5, Premium'da 20 token). Aşıldığında `429 rate_limited` ve `Retry-After` başlığı döner. Yanıtlarda `X-RateLimit-Limit` / `X-RateLimit-Remaining` başlıkları bulunur.

## Hata zarfı

```json
{ "error": { "code": "validation_failed", "message": "Girilen bilgiler geçersiz.", "details": { "slug": ["…"] } } }
```

| HTTP | `code` |
|---|---|
| 401 | `unauthenticated` |
| 403 | `forbidden`, `feature_unavailable` |
| 404 | `not_found` (başka bir hesabın kaydı da 404 döner) |
| 409 | sayfa başka yerde değiştirildi (`revision` uyuşmazlığı) |
| 422 | `validation_failed`, `unprocessable` (ör. paket limiti, rezerve adres) |
| 429 | `rate_limited` |
| 503 | `unavailable` |

## Sayfalama

Liste uç noktaları `?page=` ve `?per_page=` (1–100, varsayılan 20) alır:

```json
{ "data": [ … ], "meta": { "current_page": 1, "per_page": 20, "total": 3, "last_page": 1 } }
```

## Uç noktalar

| Yöntem | Yol | Kapsam | Açıklama |
|---|---|---|---|
| GET | `/me` | — | Hesap, paket, limitler, token bilgisi |
| GET | `/profiles` | profiles:read | Sayfalar (`?status=draft|published|archived|suspended`) |
| GET | `/profiles/{id}` | profiles:read | Tek sayfa (taslak alanları) |
| POST | `/profiles` | profiles:write | `{display_name, slug}`; sayfa limiti ve adres kuralları uygulanır |
| PATCH | `/profiles/{id}` | profiles:write | Taslağı günceller. İsteğe bağlı `revision` gönderilirse eşzamanlılık kontrolü yapılır |
| POST | `/profiles/{id}/publish` | profiles:write | Taslağı yayınlar, yeni sürüm numarasını döndürür |
| POST | `/profiles/{id}/unpublish` | profiles:write | Yayından kaldırır |
| GET | `/profiles/{id}/links` | links:read | Bağlantı türündeki bloklar |
| POST | `/profiles/{id}/links` | links:write | `{type?, data, starts_at?, ends_at?, position?}` |
| PATCH | `/links/{id}` | links:write | `data` kısmi birleştirilir; `visible` boolean |
| DELETE | `/links/{id}` | links:write | 204 |
| GET | `/profiles/{id}/analytics` | analytics:read | `?from=YYYY-MM-DD&to=YYYY-MM-DD` (paketteki geçmiş süresiyle sınırlı) |
| GET | `/domains` | domains:read | Özel alan adları |
| GET | `/domains/{id}` | domains:read | DNS kaydı ve doğrulama durumu |

API ile yapılan değişiklikler editördekiyle aynı servislerden geçer: aynı doğrulama, URL güvenliği, paket limitleri ve denetim kayıtları uygulanır. Değişiklikler **taslağa** yazılır; ziyaretçiler `publish` çağrılana kadar görmez.

### Bağlantı türleri ve `data` alanları

| `type` | Zorunlu alanlar | İsteğe bağlı |
|---|---|---|
| `link` (varsayılan) | `url` (http/https), `title` | `description`, `media_id`, `new_tab`, `highlight` |
| `email` | `email` | `label`, `subject` |
| `phone` | `phone` | `label` |
| `whatsapp` | `phone` | `message`, `label` |
| `map`, `product`, `donation`, `calendar`, `link_group` | Paket özelliği gerektirir; alanlar editördeki formla aynıdır | |

## Örnekler

```bash
TOKEN="…"
BASE="https://biofy.tr/api/v1"

# Bağlantı ekle
curl -s -X POST "$BASE/profiles/5/links" -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"data":{"title":"Web sitem","url":"https://ornek.com"}}'

# Yayınla
curl -s -X POST "$BASE/profiles/5/publish" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# Son 7 günün analitiği
curl -s "$BASE/profiles/5/analytics?from=2026-10-16&to=2026-10-22" -H "Authorization: Bearer $TOKEN"
```

## Sürümleme

`/api/v1` altındaki yanıt şekilleri geriye dönük uyumlu tutulur (yalnızca yeni alan eklenir). Uyumsuz değişiklikler `/api/v2` altında yayınlanır.
