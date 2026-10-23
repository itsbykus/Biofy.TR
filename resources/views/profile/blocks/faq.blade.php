<div class="bp-card bp-faq">
    @if ($b['title'])
        <h2 class="bp-faq-title">{{ $b['title'] }}</h2>
    @endif
    @foreach ($b['items'] as $item)
        <details>
            <summary>{{ $item['q'] }}</summary>
            <p class="bp-faq-a">{{ $item['a'] }}</p>
        </details>
    @endforeach
</div>
