{{-- Plain text only (escaped); line breaks preserved by CSS white-space: pre-line. --}}
<p class="bp-text @if (($b['align'] ?? 'center') === 'left') bp-text--left @endif">{{ $b['text'] }}</p>
