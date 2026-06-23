@if($attachments = SingleProduct::getProductAttachments())
  <div class="produt-attachments">
    @if($attachments['sample'])
      <a href="{{ $attachments['sample'] }}">leia um trecho em pdf</a>
    @endif

    @if($attachments['release'])
      <a href="{{ $attachments['release'] }}">baixe o release</a>
    @endif
  </div>

@endif
