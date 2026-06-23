{{--
Login form

This template can be overridden by copying it to yourtheme/woocommerce/global/form-login.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see         https://docs.woocommerce.com/document/template-structure/
@package     WooCommerce/Templates
@version     3.6.0
--}}

@php
  if ( is_user_logged_in() ) {
    return;
  }
@endphp

<form class="woocommerce-form woocommerce-form-login login" method="post" {!! ( $hidden ) ? 'style="display:none;"' : '' !!}>

  @php
  	do_action( 'woocommerce_login_form_start' );
  @endphp

	{!! ( $message ) ? wpautop( wptexturize( $message ) ) : '' !!}

  <div class="login-fields">
    <div class="form-group">
      <label for="username" class="sr-only">{{ esc_html_e( 'Username or email', 'woocommerce' ) }}</label>
      <input type="text" class="form-control" name="username" id="username" placeholder="{{ esc_html_e( 'Username or email', 'woocommerce' ) }}" autocomplete="username" />
    </div>

    <div class="form-group">
      <label for="password" class="sr-only">{{ esc_html_e( 'Password', 'woocommerce' ) }}</label>
      <input class="form-control" type="password" name="password" id="password" placeholder="{{ esc_html_e( 'Password', 'woocommerce' ) }}" autocomplete="current-password" />
    </div>
  </div>

	@php
    do_action( 'woocommerce_login_form' )
  @endphp

	<div class="form-group">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme custom-control custom-checkbox">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox custom-control-input form-control" name="rememberme" type="checkbox" id="rememberme" value="forever" />
      <span class="custom-control-label">{{ esc_html_e( 'Remember me', 'woocommerce' ) }}</span>
		</label>

    @php
		  wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' );
    @endphp

		<input type="hidden" name="redirect" value="{{ esc_url( $redirect ) }}" />

		<button type="submit" class="woocommerce-button button woocommerce-form-login__submit btn" name="login" value="{{ esc_attr_e( 'Login', 'woocommerce' ) }}">{{ esc_html_e( 'Login', 'woocommerce' ) }}</button>
	</div>

	<p class="lost_password">
		<a href="{{ esc_url( wp_lostpassword_url() ) }}">{{ esc_html_e( 'Lost your password?', 'woocommerce' ) }}</a>
	</p>

	@php
    do_action( 'woocommerce_login_form_end' )
  @endphp

</form>
