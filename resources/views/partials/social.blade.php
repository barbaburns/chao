@if($get_social_networks)
  <nav class="nav-social">
    <ul class="nav">
      @foreach($get_social_networks as $item)
        <li>
          <a href="{{ $item['url'] }}" aria-label="acessar nossa página no {{ $item['brand'] }}">
            <i class="fab {{ $item['icon'] }}"></i>
          </a>
        </li>
      @endforeach
    </ul>
  </nav>
@endif
