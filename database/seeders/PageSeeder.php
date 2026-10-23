<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Editable starter texts. The legal pages are templates, NOT legally reviewed
 * documents: the operator must have them reviewed before going live.
 */
class PageSeeder extends Seeder
{
    private const LEGAL_NOTE_TR = "> **Not:** Bu metin düzenlenebilir bir başlangıç taslağıdır ve hukuki danışmanlık niteliği taşımaz. Yayına almadan önce bir hukuk uzmanına inceletin ve şirket bilgilerinizi ekleyin.\n\n";

    private const LEGAL_NOTE_EN = "> **Note:** This is an editable starting draft and is not legal advice. Have it reviewed by a lawyer and add your company details before going live.\n\n";

    public function run(): void
    {
        $pages = [
            'about' => [
                ['Hakkımızda', 'About us'],
                [
                    "## Biofy.TR nedir?\n\nBiofy.TR; içerik üreticilerinin, serbest çalışanların, markaların ve işletmelerin tüm bağlantılarını tek, hızlı ve mobil uyumlu bir sayfada toplamasını sağlayan bir link-in-bio platformudur.\n\nSosyal medya profilindeki tek bağlantı alanını; videolarına, ürünlerine, randevu sayfana, iletişim bilgilerine ve daha fazlasına açılan bir kapıya dönüştürürsün.\n\n## İlkelerimiz\n\n- **Sadelik:** Teknik bilgi gerekmeden dakikalar içinde yayına çık.\n- **Gizlilik:** Ziyaretçilerini çerezlerle takip etmeyiz; istatistikler anonimleştirilmiş olarak tutulur.\n- **Hız:** Sayfaların mobilde hızlı açılacak şekilde tasarlanır.",
                    "## What is Biofy.TR?\n\nBiofy.TR is a link-in-bio platform that lets creators, freelancers, brands and businesses gather all their links on one fast, mobile-friendly page.\n\nTurn the single link field of your social profile into a gateway to your videos, products, booking page, contact details and more.\n\n## Our principles\n\n- **Simplicity:** go live in minutes without technical knowledge.\n- **Privacy:** we don't track your visitors with cookies; statistics are kept anonymised.\n- **Speed:** pages are built to load fast on mobile.",
                ],
            ],
            'help' => [
                ['Yardım ve SSS', 'Help & FAQ'],
                [
                    "## Sık sorulan sorular\n\n### Sayfamı nasıl yayınlarım?\nPanelden **Bio Sayfalarım** bölümüne gir, sayfanı düzenle ve sağ üstteki **Yayınla** butonuna bas. Yayınlamadığın değişiklikler ziyaretçilere görünmez.\n\n### Taslak ile yayındaki sürüm arasındaki fark nedir?\nEditörde yaptığın her değişiklik önce taslağa kaydedilir. Ziyaretçiler, en son yayınladığın sürümü görür. **Değişiklikleri geri al** ile taslağı yayındaki sürüme döndürebilirsin.\n\n### Kullanıcı adımı değiştirebilir miyim?\nEvet. Sayfa ayarlarından adresi değiştirebilirsin. Paketin destekliyorsa eski adres belirli bir süre yeni adrese yönlendirilir.\n\n### Kendi alan adımı nasıl bağlarım?\n**Özel Alan Adları** bölümünde alan adını ekle, gösterilen DNS kayıtlarını alan adı sağlayıcında oluştur ve **Doğrula** butonuna bas. DNS değişikliklerinin yayılması birkaç saati bulabilir. HTTPS sertifikası barındırma sağlayıcısı tarafından sağlanır.\n\n### İstatistikler nasıl hesaplanıyor?\nSayfa görüntülemeleri ve bağlantı tıklamaları sunucu tarafında sayılır. Botlar ve kısa süre içindeki tekrar eden yenilemeler sayılmaz. Ziyaretçi IP adresleri saklanmaz.\n\n### Hesabımı nasıl silerim?\n**Hesap Ayarları** sayfasından silme talebi oluşturabilirsin. Talep, bekleme süresi sonunda kalıcı olarak uygulanır; bu süre içinde iptal edebilirsin.",
                    "## Frequently asked questions\n\n### How do I publish my page?\nGo to **My Bio Pages** in the dashboard, edit your page and press **Publish** at the top right. Unpublished changes are not visible to visitors.\n\n### What is the difference between draft and published?\nEvery change you make in the editor is saved to the draft first. Visitors see the version you last published. **Discard changes** resets the draft to the published version.\n\n### Can I change my username?\nYes, from the page settings. If your plan supports it, the old address redirects to the new one for a while.\n\n### How do I connect my own domain?\nAdd the domain under **Custom Domains**, create the DNS records shown at your domain provider and press **Verify**. DNS changes can take a few hours to propagate. The HTTPS certificate is provided by the hosting provider.\n\n### How are statistics counted?\nPage views and link clicks are counted on the server. Bots and rapid repeated refreshes are not counted. Visitor IP addresses are not stored.\n\n### How do I delete my account?\nRequest deletion on the **Account Settings** page. It becomes permanent after the waiting period, during which you can cancel it.",
                ],
            ],
            'privacy' => [
                ['Gizlilik Politikası', 'Privacy Policy'],
                [
                    self::LEGAL_NOTE_TR."## 1. Toplanan veriler\n\n- **Hesap verileri:** ad, kullanıcı adı, e-posta adresi, parola özeti (parolanın kendisi saklanmaz), dil ve saat dilimi tercihleri.\n- **Sayfa içerikleri:** bio sayfanda yayınladığın metin, bağlantı ve görseller.\n- **Güvenlik kayıtları:** giriş, parola değişikliği gibi olaylar için IP adresi ve tarayıcı bilgisi; belirli bir süre sonunda otomatik silinir.\n- **Ziyaretçi istatistikleri:** sayfa görüntüleme ve tıklama olayları. Ziyaretçi IP adresleri saklanmaz; benzersiz ziyaretçi tahmini için her gün değişen, geri döndürülemez bir takma kimlik kullanılır.\n\n## 2. Kullanım amaçları\n\nHizmetin sunulması, hesap güvenliğinin sağlanması, yasal yükümlülüklerin yerine getirilmesi ve hizmetin iyileştirilmesi.\n\n## 3. Saklama süreleri\n\nAnalitik ve güvenlik kayıtları, yönetici panelinde belirlenen süre sonunda silinir. Hesap silme talebinden sonra veriler bekleme süresi dolduğunda kalıcı olarak silinir.\n\n## 4. Haklarınız\n\nVerilerinize erişme, düzeltme, silme ve dışa aktarma hakkına sahipsiniz. Hesap ayarlarından verilerinizi JSON olarak indirebilir ve silme talebi oluşturabilirsiniz.\n\n## 5. İletişim\n\nGizlilikle ilgili sorularınız için iletişim sayfasını kullanabilirsiniz.",
                    self::LEGAL_NOTE_EN."## 1. Data we collect\n\n- **Account data:** name, username, e-mail address, password hash (never the password itself), language and time zone preferences.\n- **Page content:** the text, links and images you publish on your bio page.\n- **Security records:** IP address and browser details for events such as sign-ins and password changes; deleted automatically after a set period.\n- **Visitor statistics:** page view and click events. Visitor IP addresses are not stored; unique visitors are estimated with a daily-rotating, irreversible pseudonymous identifier.\n\n## 2. Purposes\n\nProviding the service, keeping accounts secure, meeting legal obligations and improving the service.\n\n## 3. Retention\n\nAnalytics and security records are deleted after the period configured by the operator. After an account deletion request, data is permanently removed when the waiting period ends.\n\n## 4. Your rights\n\nYou can access, correct, delete and export your data. You can download your data as JSON and request deletion from your account settings.\n\n## 5. Contact\n\nUse the contact page for privacy questions.",
                ],
            ],
            'terms' => [
                ['Kullanım Koşulları', 'Terms of Use'],
                [
                    self::LEGAL_NOTE_TR."## 1. Hizmet\n\nBiofy.TR, kullanıcıların bağlantılarını ve içeriklerini tek bir sayfada yayınlamasını sağlayan bir platformdur.\n\n## 2. Hesap sorumluluğu\n\nHesabının güvenliğinden ve hesabın üzerinden yapılan işlemlerden sen sorumlusun. Güçlü bir parola ve iki faktörlü doğrulama kullanmanı öneririz.\n\n## 3. İçerik kuralları\n\nYasa dışı, yanıltıcı, zararlı yazılım içeren, oltalama (phishing) amaçlı veya başkalarının haklarını ihlal eden içerik yayınlanamaz. Bu kurallara aykırı sayfalar ve hesaplar askıya alınabilir.\n\n## 4. Paketler\n\nPaket özellikleri ve limitleri fiyatlandırma sayfasında belirtilir. Bu sürümde platform üzerinden ödeme alınmamaktadır; paket atamaları yönetim tarafından yapılır.\n\n## 5. Sorumluluğun sınırlandırılması\n\nHizmet \"olduğu gibi\" sunulur. Kullanıcıların yayınladığı içeriklerden ilgili kullanıcı sorumludur.\n\n## 6. Değişiklikler\n\nBu koşullar güncellenebilir; önemli değişiklikler kullanıcılara duyurulur.",
                    self::LEGAL_NOTE_EN."## 1. Service\n\nBiofy.TR is a platform that lets users publish their links and content on a single page.\n\n## 2. Account responsibility\n\nYou are responsible for the security of your account and the actions taken with it. We recommend a strong password and two-factor authentication.\n\n## 3. Content rules\n\nIllegal, misleading, malware-spreading, phishing or rights-infringing content may not be published. Pages and accounts breaking these rules may be suspended.\n\n## 4. Packages\n\nPackage features and limits are shown on the pricing page. No payments are taken through the platform in this version; packages are assigned by the operator.\n\n## 5. Limitation of liability\n\nThe service is provided \"as is\". Users are responsible for the content they publish.\n\n## 6. Changes\n\nThese terms may be updated; significant changes will be announced.",
                ],
            ],
            'cookies' => [
                ['Çerez Politikası', 'Cookie Policy'],
                [
                    self::LEGAL_NOTE_TR."## Kullandığımız çerezler\n\nBiofy.TR yalnızca hizmetin çalışması için **zorunlu** çerezleri kullanır:\n\n| Çerez | Amaç | Süre |\n|---|---|---|\n| Oturum çerezi | Giriş yapmış kullanıcının oturumunu sürdürmek | Oturum süresi |\n| XSRF-TOKEN | Formları siteler arası istek sahteciliğine karşı korumak | Oturum süresi |\n| Beni hatırla | Seçilirse girişin hatırlanması | En fazla 400 gün |\n\nZiyaretçi istatistikleri çerez kullanılmadan, sunucu tarafında ve anonimleştirilmiş olarak tutulur. Reklam veya üçüncü taraf takip çerezi kullanılmaz.\n\nBio sayfalarına eklenen YouTube, Spotify gibi gömülü içerikler, ziyaretçi **yükle** butonuna basana kadar yüklenmez; yüklendiklerinde ilgili hizmetin kendi çerez politikası geçerlidir.",
                    self::LEGAL_NOTE_EN."## Cookies we use\n\nBiofy.TR only uses cookies that are **strictly necessary** for the service:\n\n| Cookie | Purpose | Duration |\n|---|---|---|\n| Session cookie | Keeps a signed-in user's session | Session lifetime |\n| XSRF-TOKEN | Protects forms against cross-site request forgery | Session lifetime |\n| Remember me | Remembers the sign-in when selected | Up to 400 days |\n\nVisitor statistics are kept without cookies, server-side and anonymised. No advertising or third-party tracking cookies are used.\n\nEmbedded content such as YouTube or Spotify on bio pages is not loaded until the visitor presses **load**; once loaded, that service's own cookie policy applies.",
                ],
            ],
            'kvkk' => [
                ['KVKK Aydınlatma Metni', 'Personal Data Notice (KVKK)'],
                [
                    self::LEGAL_NOTE_TR."## Veri sorumlusu\n\n6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında veri sorumlusu, bu platformu işleten kuruluştur. *(Şirket unvanı, adres ve iletişim bilgilerini buraya ekleyin.)*\n\n## İşlenen kişisel veriler ve amaçları\n\nKimlik ve iletişim verileri (ad, e-posta) üyelik sözleşmesinin kurulması ve ifası; işlem güvenliği verileri (IP adresi, oturum kayıtları) bilgi güvenliğinin sağlanması amacıyla işlenir.\n\n## Hukuki sebepler\n\nKVKK m.5/2-c (sözleşmenin kurulması ve ifası), m.5/2-ç (hukuki yükümlülük) ve m.5/2-f (meşru menfaat).\n\n## Aktarım\n\nKişisel veriler; barındırma ve e-posta altyapısı sağlayıcıları gibi hizmetin sunulması için zorunlu tedarikçilerle sınırlı olarak paylaşılabilir.\n\n## Haklarınız (KVKK m.11)\n\nKişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini veya silinmesini isteme ve itiraz etme haklarına sahipsiniz. Başvurularınızı iletişim sayfası üzerinden iletebilirsiniz.",
                    self::LEGAL_NOTE_EN."## Data controller\n\nUnder Turkish Law No. 6698 on the Protection of Personal Data (KVKK), the data controller is the organisation operating this platform. *(Add the company name, address and contact details here.)*\n\n## Personal data processed and purposes\n\nIdentity and contact data (name, e-mail) are processed to establish and perform the membership agreement; transaction security data (IP address, session records) to ensure information security.\n\n## Legal grounds\n\nKVKK art. 5/2-c (contract), 5/2-ç (legal obligation) and 5/2-f (legitimate interest).\n\n## Transfers\n\nPersonal data may be shared, to a limited extent, with suppliers required to provide the service, such as hosting and e-mail providers.\n\n## Your rights (KVKK art. 11)\n\nYou have the right to learn whether your data is processed, request information, ask for correction or deletion, and object. Submit requests through the contact page.",
                ],
            ],
            'contact' => [
                ['İletişim', 'Contact'],
                [
                    'Sorularınız, paket talepleriniz ve iş birlikleri için aşağıdaki formu kullanabilirsiniz. Genellikle iki iş günü içinde yanıt veriyoruz.',
                    'Use the form below for questions, package requests and partnerships. We usually reply within two business days.',
                ],
            ],
        ];

        foreach ($pages as $slug => [$titles, $bodies]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => ['tr' => $titles[0], 'en' => $titles[1]],
                'body' => ['tr' => $bodies[0], 'en' => $bodies[1]],
                'meta_description' => null,
                'is_published' => true,
                'is_system' => true,
            ]);
        }
    }
}
