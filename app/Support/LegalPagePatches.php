<?php

namespace App\Support;

/**
 * 1.1.0 additions to the system pages (cookies, privacy, KVKK, terms, help): consent-gated
 * measurement tags, short links / QR codes, smart buttons, WebP images.
 *
 * Applied in place to the texts operators published from the 1.0.4 legal page pack: only the
 * sentences and sections below are changed, everything else (company name, contacts, hosting...)
 * stays as the operator wrote it. A page whose text does not contain every anchor (edited by
 * hand, or still the starter draft) is left untouched and must be updated in the admin panel.
 */
final class LegalPagePatches
{
    public const VERSION = '1.1.0';

    private const DATE = ['tr' => '25 Ekim 2026', 'en' => '25 October 2026'];

    /**
     * New text for one page body, or null when the page is not the expected text or is already updated.
     */
    public static function apply(string $slug, string $locale, string $body): ?string
    {
        $operations = self::operations()[$slug][$locale] ?? null;

        if ($operations === null) {
            return null;
        }

        // Textareas submit CRLF; the anchors use LF.
        $text = str_replace("\r\n", "\n", $body);

        if (str_contains($text, $operations[0][2])) {
            return null; // already applied
        }

        foreach ($operations as [$type, $anchor, $value]) {
            if (substr_count($text, $anchor) !== 1) {
                return null; // not the text this patch was written for
            }

            $text = match ($type) {
                'replace' => str_replace($anchor, $value, $text),
                'before' => str_replace($anchor, $value.$anchor, $text),
                'after' => str_replace($anchor, $anchor.$value, $text),
            };
        }

        return (string) preg_replace(
            ['/^\*Son güncelleme: [^*]+\*/u', '/^\*Last updated: [^*]+\*/u'],
            ['*Son güncelleme: '.self::DATE['tr'].'*', '*Last updated: '.self::DATE['en'].'*'],
            $text,
        );
    }

    /** slug => locale => list of [replace|before|after, anchor, text]. The first text marks the patch as applied. */
    private static function operations(): array
    {
        return [
            'cookies' => [
                'tr' => [
                    ['replace', "- Yalnızca sitenin çalışması için **kesinlikle gerekli** çerezleri kullanıyoruz.\n- **Reklam, pazarlama veya takip çerezi kullanmıyoruz.** Google Analytics, Meta (Facebook) Pixel, Hotjar ya da benzeri üçüncü taraf ölçüm ve reklam araçları sitede yoktur.\n",
                        "- Varsayılan olarak yalnızca sitenin çalışması için **kesinlikle gerekli** çerezleri kullanıyoruz.\n- **Ölçüm ve reklam araçları (Google, Meta, TikTok) yalnızca açık rızanızla yüklenir.** Sayfanın altındaki bantta \"Kabul et\" demediğiniz sürece bu araçlardan hiçbiri çalışmaz ve tarayıcınıza çerez bırakmaz.\n"],
                    ['replace', "- Kesinlikle gerekli çerezler açık rızaya bağlı değildir. Sayfanın altında gördüğünüz bant bu nedenle bir onay formu değil, bilgilendirmedir.\n",
                        "- Kesinlikle gerekli çerezler açık rızaya bağlı değildir. Ölçüm araçlarının kullanılmadığı sayfalarda alttaki bant yalnızca bilgilendirmedir; kullanıldığı sayfalarda ise **Kabul et** ve **Reddet** seçeneklerinin eşit şekilde sunulduğu bir onay bandıdır.\n"],
                    ['before', "## Yerel depolama (localStorage)\n", <<<'MD'
## Ölçüm ve reklam araçları (yalnızca izninizle)

Aşağıdaki araçlar iki durumda kullanılabilir:

- **biofy.tr'nin kendi sayfalarında** (ana sayfa, blog, kurumsal sayfalar), bu araçlar yönetim panelinden etkinleştirildiyse.
- **Bir bio sayfasında**, sayfanın sahibi kendi piksel kimliğini eklediyse. Bu durumda toplanan veriler bakımından veri sorumlusu sayfa sahibidir.

Her iki durumda da araçlar siz çerez bandında **Kabul et** demeden yüklenmez; reddederseniz bu sağlayıcılara hiçbir istek gönderilmez. İzin biofy.tr için ve her bio sayfası için ayrı ayrı sorulur.

| Sağlayıcı | Örnek çerezler | Amaç | Süre |
|---|---|---|---|
| Google Analytics, Google Ads, Google Tag Manager (Google LLC) | `_ga`, `_ga_*`, `_gcl_au` | Ziyaret istatistikleri, reklam dönüşümlerinin ölçülmesi | 90 gün – 2 yıl |
| Meta Pixel (Meta Platforms Ireland Ltd.) | `_fbp`, `fr` | Reklam ölçümü ve hedefleme | 90 güne kadar |
| TikTok Pixel (TikTok) | `_ttp`, `ttcsid` | Reklam ölçümü | 13 aya kadar |

Bu araçlar verileri Google, Meta ve TikTok'un yurt dışındaki sunucularına gönderir ve ilgili şirketlerin kendi gizlilik politikalarına tabidir: [Google](https://policies.google.com/privacy), [Meta](https://www.facebook.com/privacy/policy/), [TikTok](https://www.tiktok.com/legal/privacy-policy). Bir bio sayfasında bağlantı tıklamaları da bu araçlara olay olarak bildirilir.


MD],
                    ['after', "| `biofy.cookie-notice` | Çerez bilgilendirme bandını **Anladım** ile kapattığınızı hatırlar, böylece bant her sayfada yeniden çıkmaz. Yalnızca `1` değerini içerir, kişisel veri içermez. | Tarayıcı verilerinizi silene kadar |\n",
                        "| `biofy.consent.*` | Ölçüm ve reklam araçları için verdiğiniz izni veya reddi saklar (biofy.tr ve her bio sayfası için ayrı). | 180 gün; sonra yeniden sorulur |\n"],
                    ['replace', 'Çerezleri tarayıcınızın ayarlarından görebilir, silebilir veya engelleyebilirsiniz. Yukarıdaki çerezlerin tümü zorunlu olduğundan, engellemeniz durumunda giriş yapamaz ve formları gönderemezsiniz.',
                        'Ölçüm ve reklam araçlarına verdiğiniz izni istediğiniz zaman sayfanın altındaki **Çerez tercihleri** bağlantısından değiştirebilirsiniz. Çerezleri ayrıca tarayıcınızın ayarlarından görebilir, silebilir veya engelleyebilirsiniz. Zorunlu çerezleri engellerseniz giriş yapamaz ve formları gönderemezsiniz.'],
                ],
                'en' => [
                    ['replace', "- We only use cookies that are **strictly necessary** for the site to work.\n- **We use no advertising, marketing or tracking cookies.** There is no Google Analytics, Meta (Facebook) Pixel, Hotjar or any similar third-party measurement or advertising tool on the site.\n",
                        "- By default we only use cookies that are **strictly necessary** for the site to work.\n- **Measurement and advertising tools (Google, Meta, TikTok) are only loaded with your explicit consent.** Unless you press \"Accept\" in the bar at the bottom of the page, none of them runs or sets cookies.\n"],
                    ['replace', "- Strictly necessary cookies do not require consent. The banner at the bottom of the page is therefore a notice, not a consent form.\n",
                        "- Strictly necessary cookies do not require consent. On pages without measurement tools the bar at the bottom is only a notice; on pages that use them it is a consent bar offering **Accept** and **Reject** equally.\n"],
                    ['before', "## Local storage (localStorage)\n", <<<'MD'
## Measurement and advertising tools (only with your consent)

The tools below may be used in two cases:

- **On biofy.tr's own pages** (home, blog, company pages), if they were switched on in the admin panel.
- **On a bio page**, if its owner added their own pixel ID. The page owner is then the data controller for the data collected.

In both cases the tools are not loaded until you press **Accept** in the cookie bar; if you reject, no request is sent to these providers. Consent is asked separately for biofy.tr and for each bio page.

| Provider | Example cookies | Purpose | Duration |
|---|---|---|---|
| Google Analytics, Google Ads, Google Tag Manager (Google LLC) | `_ga`, `_ga_*`, `_gcl_au` | Visit statistics, measuring ad conversions | 90 days – 2 years |
| Meta Pixel (Meta Platforms Ireland Ltd.) | `_fbp`, `fr` | Ad measurement and targeting | Up to 90 days |
| TikTok Pixel (TikTok) | `_ttp`, `ttcsid` | Ad measurement | Up to 13 months |

These tools send data to Google's, Meta's and TikTok's servers abroad and are subject to those companies' own privacy policies: [Google](https://policies.google.com/privacy), [Meta](https://www.facebook.com/privacy/policy/), [TikTok](https://www.tiktok.com/legal/privacy-policy). On a bio page, link clicks are also reported to these tools as events.


MD],
                    ['after', "| `biofy.cookie-notice` | Remembers that you closed the cookie notice with **Got it**, so it does not reappear on every page. Contains only the value `1` and no personal data. | Until you clear your browser data |\n",
                        "| `biofy.consent.*` | Remembers whether you accepted or rejected measurement and advertising tools (separately for biofy.tr and each bio page). | 180 days; then asked again |\n"],
                    ['replace', 'You can view, delete or block cookies in your browser settings. All cookies above are necessary, so if you block them you will not be able to sign in or submit forms.',
                        'You can change your consent for measurement and advertising tools at any time with the **Cookie preferences** link at the bottom of the page. You can also view, delete or block cookies in your browser settings. If you block the necessary cookies, you will not be able to sign in or submit forms.'],
                ],
            ],
            'privacy' => [
                'tr' => [
                    ['before', "## 4. Verileri hangi amaçlarla ve hangi hukuki sebeplerle işliyoruz?\n", <<<'MD'
### 3.9 Kısa bağlantılar ve QR kodlar

Oluşturduğunuz kısa bağlantının kodu, hedef adresi, isteğe bağlı adı, tıklanma sayısı ve son tıklanma zamanı saklanır. Kısa bağlantıya tıklayan ziyaretçilerin IP adresi veya başka bir kimlik bilgisi kaydedilmez; botlar ve bağlantı önizlemeleri sayılmaz. QR kod oluşturucuya girdiğiniz içerik sunucumuzda anlık olarak QR koda dönüştürülür ve saklanmaz.

### 3.10 Ölçüm ve reklam araçları (yalnızca izinle)

biofy.tr'nin kendi sayfalarında Google (Analytics, Ads, Tag Manager), Meta ve TikTok ölçüm araçları etkinleştirildiyse, bu araçlar **yalnızca çerez bandında açık rızanızı vermenizden sonra** yüklenir. Bu durumda ilgili sağlayıcı ziyaret ettiğiniz sayfayı, cihaz ve tarayıcı bilgilerinizi, IP adresinizi ve kendi çerez tanımlayıcılarını işler. İzninizi istediğiniz zaman sayfanın altındaki **Çerez tercihleri** bağlantısından geri alabilirsiniz.

Pro paket kullanıcıları kendi bio sayfalarına bu piksellerden ekleyebilir. Bu durumda da pikseller yalnızca ziyaretçinin o sayfadaki onayıyla yüklenir. Bu araçlarla toplanan veriler bakımından veri sorumlusu ilgili sayfanın sahibidir; Biofy.TR bu verilere erişmez.


MD],
                    ['before', '| Yetkili kurum taleplerinin ve yasal yükümlülüklerin yerine getirilmesi |',
                        "| Kısa bağlantı ve QR kod hizmetinin sunulması | Kısa bağlantı bilgileri, tıklama sayısı | (c) Sözleşmenin ifası |\n| biofy.tr ziyaretlerinin ölçülmesi ve reklam performansının değerlendirilmesi (yalnızca izinle) | Çevrim içi tanımlayıcılar, ziyaret bilgileri | Açık rıza (KVKK m.5/1) |\n"],
                    ['replace', 'Verilerinizi reklam, profilleme veya pazarlama amacıyla kullanmıyor, **satmıyor ve kiralamıyoruz.**',
                        'Verilerinizi **satmıyor ve kiralamıyoruz.** Ölçüm ve reklam araçları yalnızca açık rızanızla çalışır; rızanız olmadan verileriniz reklam veya profilleme amacıyla kullanılmaz.'],
                    ['before', '- **Yetkili kamu kurum ve kuruluşları:**',
                        "- **Google, Meta ve TikTok:** Yalnızca çerez bandında açık rıza verdiğinizde; biofy.tr'nin kendi sayfalarındaki veya bir bio sayfasının sahibinin eklediği ölçüm ve reklam araçları aracılığıyla.\n"],
                    ['replace', 'bir bio sayfasındaki gömülü içeriği yüklediğinizde ya da YouTube önizleme görseli görüntülendiğinde. Bu aktarımlar KVKK\'nın 9. maddesinde öngörülen şartlara uygun olarak gerçekleştirilir.',
                        'bir bio sayfasındaki gömülü içeriği yüklediğinizde, YouTube önizleme görseli görüntülendiğinde ya da ölçüm ve reklam araçlarına açık rıza verdiğinizde. Bu aktarımlar KVKK\'nın 9. maddesinde öngörülen şartlara uygun olarak gerçekleştirilir; ölçüm ve reklam araçları için aktarımın dayanağı açık rızanızdır.'],
                    ['before', '| Yönetim işlem kayıtları |',
                        "| Kısa bağlantılar | Siz silene veya hesabınız silinene kadar |\n| Ölçüm araçları için verdiğiniz izin veya ret | Tarayıcınızda 180 gün |\n"],
                ],
                'en' => [
                    ['before', "## 4. Purposes and legal grounds\n", <<<'MD'
### 3.9 Short links and QR codes

For each short link you create we store its code, destination, optional name, click count and last click time. No IP address or other identifier of the people who click it is recorded; bots and link previews are not counted. Content entered in the QR code generator is turned into a QR code on our server on the fly and is not stored.

### 3.10 Measurement and advertising tools (only with consent)

If Google (Analytics, Ads, Tag Manager), Meta or TikTok tools are switched on for biofy.tr's own pages, they are loaded **only after you give your explicit consent in the cookie bar**. The provider then processes the page you visit, your device and browser details, your IP address and its own cookie identifiers. You can withdraw your consent at any time with the **Cookie preferences** link at the bottom of the page.

Pro plan users can add these pixels to their own bio pages. There too, pixels only load with the visitor's consent on that page. The page owner is the data controller for the data collected with these tools; Biofy.TR does not access it.


MD],
                    ['before', '| Meeting requests from authorities and legal obligations |',
                        "| Providing short links and QR codes | Short link details, click count | (c) Performing the contract |\n| Measuring visits to biofy.tr and evaluating ads (only with consent) | Online identifiers, visit details | Explicit consent (KVKK art. 5/1) |\n"],
                    ['replace', 'We do not use your data for advertising, profiling or marketing, and **we do not sell or rent it.**',
                        '**We do not sell or rent your data.** Measurement and advertising tools only run with your explicit consent; without it your data is not used for advertising or profiling.'],
                    ['before', '- **Competent public authorities:**',
                        "- **Google, Meta and TikTok:** only when you give explicit consent in the cookie bar, through the measurement and advertising tools on biofy.tr's own pages or added by a bio page's owner.\n"],
                    ['replace', 'when you load embedded content on a bio page, or when a YouTube preview image is shown. These transfers are made in line with the conditions of article 9 of the KVKK.',
                        'when you load embedded content on a bio page, when a YouTube preview image is shown, or when you consent to measurement and advertising tools. These transfers are made in line with the conditions of article 9 of the KVKK; for measurement and advertising tools the legal basis is your explicit consent.'],
                    ['before', '| Administrative action records |',
                        "| Short links | Until you delete them or your account is deleted |\n| Your consent or refusal for measurement tools | 180 days in your browser |\n"],
                ],
            ],
            'kvkk' => [
                'tr' => [
                    ['replace', '| Pazarlama | İşlenmez | — |',
                        '| Pazarlama | Yalnızca açık rızanızla: ölçüm ve reklam araçlarının çevrim içi tanımlayıcıları (çerez kimlikleri) ve ziyaret bilgileri | Ziyaretçi |'],
                    ['after', "- Bio sayfası sahiplerine toplu ziyaret ve tıklama istatistiklerinin sunulması.\n",
                        "- Kısa bağlantı ve QR kod hizmetinin sunulması.\n- Açık rıza vermeniz halinde biofy.tr ziyaretlerinin ölçülmesi ve reklam performansının değerlendirilmesi.\n"],
                    ['replace', 'Verileriniz **açık rızaya dayalı olarak işlenmez.** Reklam, profilleme veya pazarlama amacıyla kullanılmaz.',
                        'Yalnızca ölçüm ve reklam araçları (Google, Meta, TikTok) KVKK\'nın 5. maddesinin 1. fıkrası uyarınca **açık rızanıza** dayanır ve rıza vermediğiniz sürece çalışmaz. Rızanızı istediğiniz zaman sayfanın altındaki **Çerez tercihleri** bağlantısından geri alabilirsiniz. Bunun dışındaki veriler reklam, profilleme veya pazarlama amacıyla kullanılmaz.'],
                    ['after', 'IP adresinizi ve tarayıcı bilgilerinizi teknik olarak alır.',
                        "\n- Ölçüm ve reklam araçlarına **açık rıza** verdiğinizde, Google LLC, Meta Platforms Ireland Ltd. ve TikTok tarafından (KVKK m.9 uyarınca açık rızanıza dayanılarak)."],
                ],
                'en' => [
                    ['replace', '| Marketing | Not processed | — |',
                        '| Marketing | Only with your explicit consent: online identifiers (cookie IDs) of measurement and advertising tools and visit details | Visitor |'],
                    ['after', "- Providing aggregated visit and click statistics to bio page owners.\n",
                        "- Providing short links and QR codes.\n- If you consent, measuring visits to biofy.tr and evaluating ad performance.\n"],
                    ['replace', 'Your data is **not processed on the basis of explicit consent** and is not used for advertising, profiling or marketing.',
                        'Only the measurement and advertising tools (Google, Meta, TikTok) rely on your **explicit consent** under article 5(1) of the KVKK, and they do not run without it. You can withdraw consent at any time with the **Cookie preferences** link at the bottom of the page. Other data is not used for advertising, profiling or marketing.'],
                    ['after', 'technically receives your IP address and browser details.',
                        "\n- When you give **explicit consent** to measurement and advertising tools: Google LLC, Meta Platforms Ireland Ltd. and TikTok (based on your explicit consent under KVKK art. 9)."],
                ],
            ],
            'terms' => [
                'tr' => [
                    ['after', "- Platformun güvenliğini veya işleyişini bozmaya yönelik girişimler: yetkisiz erişim denemeleri, güvenlik önlemlerini aşma, aşırı yük oluşturma, otomatik veri toplama.\n",
                        "- Kısa bağlantıları oltalama, zararlı yazılım, istenmeyen ileti veya hedef adresi gizlemek amacıyla kullanmak.\n"],
                    ['before', "## 9. Paketler ve ücretler\n", <<<'MD'
### Kısa bağlantılar ve izleme pikselleri

Kısa bağlantının yönlendirdiği içerikten bağlantıyı oluşturan kullanıcı sorumludur. Kurallara aykırı kısa bağlantılar bildirim yapılmadan devre dışı bırakılabilir veya silinebilir.

Bio sayfanıza Google, Meta veya TikTok pikseli eklediğinizde, bu araçlarla toplanan veriler bakımından veri sorumlusu sizsiniz. Ziyaretçilerinizi bilgilendirmek ve ilgili sağlayıcıların koşullarına uymak sizin sorumluluğunuzdadır. Biofy.TR pikselleri yalnızca ziyaretçinin onayıyla yükler.


MD],
                ],
                'en' => [
                    ['after', "- Attempts to undermine the platform's security or operation: unauthorised access attempts, bypassing security measures, overloading, automated data collection.\n",
                        "- Using short links for phishing, malware, spam or to hide where they lead.\n"],
                    ['before', "## 9. Plans and fees\n", <<<'MD'
### Short links and tracking pixels

The user who creates a short link is responsible for the content it leads to. Short links that break these rules may be disabled or deleted without notice.

When you add a Google, Meta or TikTok pixel to your bio page, you are the data controller for the data these tools collect. Informing your visitors and following the providers' terms is your responsibility. Biofy.TR only loads pixels with the visitor's consent.


MD],
                ],
            ],
            'help' => [
                'tr' => [
                    ['replace', 'Profil resmi, kapak ve bloklar için görsel yükleyebilirsin. Yüklediğin görseller **Medya** bölümünde toplanır. Depolama alanın paketine göre sınırlıdır.',
                        'Profil resmi, kapak ve bloklar için JPG, PNG, GIF, WEBP ve SVG görseller yükleyebilirsin. Tüm görseller otomatik olarak boyutlandırılır ve daha az yer kaplayan **WebP** biçiminde saklanır; SVG dosyaları yüklenmeden önce tarayıcında resme dönüştürülür. Yüklediğin görseller **Medya** bölümünde toplanır. Depolama alanın paketine göre sınırlıdır.'],
                    ['before', "## Diğer\n", <<<'MD'
## Butonlar, kısa bağlantılar ve pikseller

### Butonlara simge veya arka plan görseli ekleyebilir miyim?
Evet. Bağlantı bloğundaki **Simge** alanı varsayılan olarak *Otomatik*tir: Instagram, YouTube, Spotify, WhatsApp, TikTok gibi 70'ten fazla site simgesiyle tanınır. İstersen başka bir marka ya da simge seçebilir, simgeyi kapatabilir veya butona **arka plan görseli** ekleyebilirsin.

### Kısa bağlantıyı nasıl oluştururum?
Panelde **Kısa Bağlantılar** sayfasında hedef adresi gir; istersen özel bir kod belirle (ör. `biofy.tr/s/kampanya`). Her kısa bağlantının tıklanma sayısını görebilir, hedefini değiştirebilir ve QR kodunu PNG veya SVG olarak indirebilirsin. Basic pakette 10, Pro'da 200 kısa bağlantı oluşturabilirsin.

### QR kodu nasıl oluştururum?
**QR Kod** sayfasına bir adres veya metin yaz, renklerini seç ve PNG ya da SVG olarak indir. Telefon kameralarının okuyabilmesi için koyu bir kod rengi ve açık bir arka plan seç.

### Google Analytics, Meta veya TikTok pikseli ekleyebilir miyim?
Pro ve Premium paketlerde evet. Editörde **SEO** sekmesindeki **İzleme pikselleri** bölümüne kimliklerini gir. Pikseller ziyaretçi sayfandaki çerez bandında *Kabul et* derse yüklenir; bağlantı tıklamaları da olay olarak gönderilir. Bu araçlarla toplanan veriler için veri sorumlusu sensin.


MD],
                ],
                'en' => [
                    ['replace', 'You can upload images for your profile picture, cover and blocks. Uploaded images are collected in the **Media** section. Your storage space is limited by your plan.',
                        'You can upload JPG, PNG, GIF, WEBP and SVG images for your profile picture, cover and blocks. Every image is resized automatically and stored as space-saving **WebP**; SVG files are converted to an image in your browser before uploading. Uploaded images are collected in the **Media** section. Your storage space is limited by your plan.'],
                    ['before', "## Other\n", <<<'MD'
## Buttons, short links and pixels

### Can I add an icon or a background image to a button?
Yes. The **Icon** field of a link block is *Automatic* by default: links to Instagram, YouTube, Spotify, WhatsApp, TikTok and 70+ other sites get their icons. You can also pick another brand or icon, turn the icon off, or add a **background image** to the button.

### How do I create a short link?
Enter the destination on the **Short Links** page in your dashboard; optionally choose your own code (e.g. `biofy.tr/s/campaign`). You can see each short link's clicks, change its destination and download its QR code as PNG or SVG. Basic includes 10 short links, Pro 200.

### How do I create a QR code?
Type an address or text on the **QR Code** page, pick the colours and download it as PNG or SVG. Use a dark code colour on a light background so phone cameras can read it.

### Can I add a Google Analytics, Meta or TikTok pixel?
Yes, on Pro and Premium. Enter your IDs in the **Tracking pixels** section of the editor's **SEO** tab. Pixels load only if a visitor presses *Accept* in the cookie bar on your page; link clicks are sent as events too. You are the data controller for the data these tools collect.


MD],
                ],
            ],
        ];
    }
}
