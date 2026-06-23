@php
  $resolvedProductId = \App\resolve_product_id($product_id ?? null, $product ?? null);
  $soon = $resolvedProductId ? get_field('em_breve', $resolvedProductId) : false;
@endphp

@if($soon)
  <span class="btn-learn-more full"> saiba mais </span>
@else
  <span class="btn-learn-more"> saiba mais </span>
@endif
