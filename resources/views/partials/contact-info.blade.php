<div class="contact-info">
  @if($get_address)
    <address>{!! $get_address !!}</address>
  @endif
  @if($get_cnpj)
    <p>{!! $get_cnpj !!}</p>
  @endif
  <p>{!! $get_phone !!}</p>
  <p>{!! $get_email !!}</p>
</div>
