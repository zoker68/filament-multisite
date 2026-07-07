@if($canonicalUrl)
<link rel="canonical" href="{{ $canonicalUrl }}" />
@endif

@if(count($items) > 1)
    @foreach($items as $item)
    <link rel="alternate" hreflang="{{ $item['site']->locale }}" href="{{ $item['url'] }}" />
    @if($item['isDefault'])
    <link rel="alternate" hreflang="x-default" href="{{ $item['url'] }}" />
    @endif
    @endforeach
@endif
