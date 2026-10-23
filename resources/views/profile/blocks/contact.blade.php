@include('profile.blocks._button', ['href' => $block['href'] ?? '#', 'title' => $b['label'] ?: __('blocks.types.contact.default_label'), 'icon' => 'contact', 'newTab' => false])
