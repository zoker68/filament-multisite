@foreach($items as $item)
<link rel="alternate" hreflang="{{ $item['site']->locale }}" href="{{ $item['url'] }}" />
@endforeach
