@if ($get_books_list)
    <style>
        :root {
            --book-width: 1118px;
        }

        @media only screen and (min-width: 993px) {
            :root {
                --book-width: 1100px;
            }

            @media only screen and (max-width: 992px) {
                :root {
                    --book-width: {{ count($get_books_list) * 52 . 'px' }};
                }
            }

    </style>
    {{-- book-list-container Adicionado para poder colocar o book-list-nav --}}
    <div class="book-list-container" id="book-list-container-id">
        <ul class="books-list" id="book-list-translate">
            @foreach ($get_books_list as $item)
                <li class="books-list-item" style="background-color: {{ $item['color'] }}">
                    <a href="{{ $item['url'] }}">
                        <span class="books-list-item-author">
                            {!! $item['author'] !!}
                        </span>
                        <span class="books-list-item-title">
                            {!! $item['title'] !!}
                            
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Navegação dos livros --}}
        <div class="book-list-nav">
            {{-- <div id="temp">0</div> --}}
            <button id="btn_left" class="book-list-button">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                    <path d="M20 .755l-14.374 11.245 14.374 11.219-.619.781-15.381-12 15.391-12 .609.755z" />
                </svg>
            </button>
            <button id="btn_right" class="book-list-button">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                    <path d="M4 .755l14.374 11.245-14.374 11.219.619.781 15.381-12-15.391-12-.609.755z" />
                </svg>
            </button>
        </div>
        {{-- <div class="book-list-nav-mobile">
       <button id="btn_left_mobile" class="book-list-button-mobile">
        <svg style="margin-left: 2rem" width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path d="M20 .755l-14.374 11.245 14.374 11.219-.619.781-15.381-12 15.391-12 .609.755z"/></svg>
      </button>
      <button id="btn_right_mobile" class="book-list-button-mobile"> 
        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path d="M4 .755l14.374 11.245-14.374 11.219.619.781 15.381-12-15.391-12-.609.755z"/></svg>
        <svg style="margin-left: 4rem" width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path d="M20 .755l-14.374 11.245 14.374 11.219-.619.781-15.381-12 15.391-12 .609.755z"/></svg>
      </button> --}}
    </div>
    </div>
@endif
