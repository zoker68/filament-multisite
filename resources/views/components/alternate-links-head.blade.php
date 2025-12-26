@if(count($items) > 1)
    @if($canonicalUrl)
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    @endif

    @foreach($items as $item)
    <link rel="alternate" hreflang="{{ $item['site']->locale }}" href="{{ $item['url'] }}" />
    @endforeach
@endif
