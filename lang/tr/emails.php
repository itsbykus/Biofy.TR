<?php

// Default texts; editable per locale in Admin → E-mail templates. Paragraphs are separated by a blank line.
return [
    'button_fallback' => 'Buton çalışmıyorsa bu bağlantıyı tarayıcına yapıştır:',
    'footer' => 'Bu e-posta :site tarafından gönderildi. Bu işlemi sen yapmadıysan bu mesajı yok sayabilir veya destek ekibimizle iletişime geçebilirsin.',
    'welcome' => [
        'subject' => '{site} ailesine hoş geldin!',
        'body' => "Merhaba {name},\n\n{site} hesabın oluşturuldu. Artık tüm bağlantılarını tek bir şık sayfada toplayabilirsin.\n\nİlk adım olarak profil fotoğrafını ekle, bağlantılarını oluştur ve sayfanı yayınla.",
        'action' => 'Panele git',
    ],
    'verify_email' => [
        'subject' => 'E-posta adresini doğrula',
        'body' => "Merhaba {name},\n\nHesabını güvence altına almak için lütfen e-posta adresini doğrula. Bu bağlantı {minutes} dakika geçerlidir.",
        'action' => 'E-postamı doğrula',
    ],
    'reset_password' => [
        'subject' => 'Parola sıfırlama isteği',
        'body' => "Merhaba {name},\n\nHesabın için bir parola sıfırlama isteği aldık. Yeni parola belirlemek için aşağıdaki butonu kullan. Bağlantı {minutes} dakika geçerlidir.\n\nBu isteği sen yapmadıysan hiçbir işlem yapmana gerek yok; parolan değişmeyecek.",
        'action' => 'Parolamı sıfırla',
    ],
    'security_alert' => [
        'subject' => 'Hesabında güvenlik değişikliği',
        'body' => "Merhaba {name},\n\nHesabında şu işlem yapıldı: {event}\nZaman: {time}\nIP adresi: {ip}\n\nBu işlemi sen yapmadıysan hemen parolanı değiştir ve destek ekibiyle iletişime geç.",
        'action' => '',
    ],
    'announcement' => [
        'subject' => '{site} duyurusu',
        'body' => "Merhaba {name},\n\n{message}",
        'action' => '',
    ],
    'account_suspended' => [
        'subject' => 'Hesabın askıya alındı',
        'body' => "Merhaba {name},\n\n{site} hesabın askıya alındı. Bu süre boyunca sayfaların ziyaretçilere gösterilmez.\n\nGerekçe: {reason}\n\nBir hata olduğunu düşünüyorsan destek ekibiyle iletişime geçebilirsin.",
        'action' => '',
    ],
    'account_reactivated' => [
        'subject' => 'Hesabın yeniden etkinleştirildi',
        'body' => "Merhaba {name},\n\n{site} hesabın {date} itibarıyla yeniden etkin. Giriş yapıp sayfalarını yönetmeye devam edebilirsin.",
        'action' => 'Giriş yap',
    ],
    'deletion_requested' => [
        'subject' => 'Hesap silme talebin alındı',
        'body' => "Merhaba {name},\n\nHesap silme talebini aldık. Hesabın ve tüm verilerin {date} tarihinde kalıcı olarak silinecek.\n\nFikrini değiştirirsen bu tarihten önce giriş yapıp talebi iptal edebilirsin.",
        'action' => '',
    ],
    'contact_received' => [
        'subject' => '[{site}] Yeni iletişim mesajı',
        'body' => "Yeni bir iletişim mesajı alındı:\n\n{message}",
        'action' => 'Mesajı görüntüle',
    ],
    'test' => [
        'subject' => '{site} test e-postası',
        'body' => "Bu bir test e-postasıdır.\n\nBu mesajı görüyorsan e-posta ayarların doğru çalışıyor.",
        'action' => '',
    ],
];
