@if($link)
  <a href="{{ $link }}">
@endif

{{ $slot }}

@if($link)
  </a>
@endif
