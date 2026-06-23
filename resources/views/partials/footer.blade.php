<footer class="content-info">
  <div class="container">
    <nav class="nav-footer">
      @if (has_nav_menu('footer_navigation'))
        {!! wp_nav_menu(['theme_location' => 'footer_navigation', 'menu_class' => 'nav']) !!}
      @endif
    </nav>

    <div class="form-newsletter-wrapper">
      <h4 class="form-newsletter-title">Assine nossa newsletter</h4>
      {!! do_shortcode('[mc4wp_form id="112"]') !!}
      {!! $get_email !!}
    </div>

    <a class="brand" href="{{ home_url('/') }}">
      @include('icon::logo')
    </a>
  </div>

  <nav class="nav-copyright">
    @if (has_nav_menu('copyright_navigation'))
      {!! wp_nav_menu(['theme_location' => 'copyright_navigation', 'menu_class' => 'nav']) !!}
    @endif
  </nav>
</footer>
