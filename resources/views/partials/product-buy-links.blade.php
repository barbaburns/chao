@if($buy_links = SingleProduct::getBuyLinks())
  @component('components.dropdown', [
    'id' => 'dropdown-links',
    'items' => $buy_links
  ])
    @include('icon::cart-out') Comprar E-book
  @endcomponent
@endif
