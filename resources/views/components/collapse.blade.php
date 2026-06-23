<h2>
  <button type="button" {{ $open ? '' : 'class=collapsed' }} data-toggle="collapse" data-target="#{{ $id }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $id }}">{{ $title }}</button>
</h2>
<div class="collapse {{ $open ? 'show' : '' }}" id="{{ $id }}">
  <div class="card card-body">
    {!! $slot !!}
  </div>
</div>
