{{ setting('site.name') }}

@foreach ($lines as $line)
{!! html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}

@endforeach
@if ($actionUrl)
{{ $actionLabel }}: {!! $actionUrl !!}

@endif
--
{{ __('emails.footer', ['site' => setting('site.name')], $locale) }}
