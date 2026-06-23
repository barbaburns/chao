@if($get_team)
  <div class="team-list-wrapper">
    @foreach($get_team as $type => $list)
      @if($list)
        <h3>{{ $type }}</h3>

        @foreach($list as $item)
          <figure class="team-item">
            {!! $item['image'] !!}
            <figcaption>
              <a href="{!! $item['url'] !!}">
                {{ $item['title'] }}
              </a>
            </figcaption>
          </figure>
        @endforeach
      @endif
    @endforeach
  </div>
@endif
