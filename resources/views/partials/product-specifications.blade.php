<div class="product-specifications">
  <h2>Especificações</h2>

  <dl>
    @if($specifications = SingleProduct::getProductSpecifications())
      @foreach ($specifications as $title => $value)
        <div>
          <dt>{{ $title }}</dt>
          <dd>{{ $value }}</dd>
        </div>
      @endforeach
    @endif
  </dl>
</div>
