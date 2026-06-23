{{--
Login Form

This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see     https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 4.1.0
--}}

@php
  do_action( 'woocommerce_before_customer_login_form' );
@endphp

@if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) )

<div class="column" id="customer_login">

	<div class="u-column1 col-1">

@endif

    <h2><?php esc_html_e('Login', 'woocommerce'); ?></h2>

    <form class="woocommerce-form woocommerce-form-login login" method="post">

      @php
			  do_action( 'woocommerce_login_form_start' );
      @endphp

    <div class="login-fields">
      <p class="form-group woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="username" class="sr-only">{{ esc_html_e( 'Username or email address', 'woocommerce' ) }}</label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control" name="username" id="username" autocomplete="username" placeholder="{{ esc_html_e( 'Username or email address', 'woocommerce' ) }}" value="{!! ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : '' !!}" /><?php // @codingStandardsIgnoreLine?>
			</p>
			<p class="form-group woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="password" class="sr-only">{{ esc_html_e( 'Password', 'woocommerce' ) }}</label>
        <input class="woocommerce-Input woocommerce-Input--text input-text form-control" type="password" name="password" id="password" autocomplete="current-password" placeholder="{{ esc_html_e( 'Password', 'woocommerce' ) }}" />
			</p>
    </div>

      @php
        do_action( 'woocommerce_login_form' );
      @endphp

      <p class="form-group form-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme custom-control custom-checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox custom-control-input form-control" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span class="custom-control-label"><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
				</label>

        <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>

        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit btn" name="login" value="<?php esc_attr_e('Log in', 'woocommerce'); ?>"><?php esc_html_e('Log in', 'woocommerce'); ?></button>
			</p>

      <p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'woocommerce'); ?></a>
			</p>

			@php
        do_action( 'woocommerce_login_form_end' )
      @endphp

		</form>

@if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) )

	</div>

	<div class="u-column2 col-2">

		<h2>{{ esc_html_e( 'Crie sua conta', 'woocommerce' ) }}</h2>

		<form method="post" class="woocommerce-form woocommerce-form-register register" @php do_action( 'woocommerce_register_form_tag' ); @endphp >

			@php
        do_action( 'woocommerce_register_form_start' );
      @endphp

			@if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) )

      <p class="form-group woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_username" class="sr-only"><?php esc_html_e('Username', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control" name="username" id="reg_username" autocomplete="username" value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" /><?php // @codingStandardsIgnoreLine?>
      </p>

			@endif

        <p class="form-group woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
  				<label for="reg_email" class="sr-only"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
  				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" placeholder="e-mail" value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" /><?php // @codingStandardsIgnoreLine?>
  			</p>

			@if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) )

        <p class="form-group woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          <label for="reg_password" class="sr-only"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
          <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
        </p>

			@else

				<p>{{ esc_html_e( 'Você receberá sua senha por e-mail', 'woocommerce' ) }}</p>

			@endif

			@php
        do_action( 'woocommerce_register_form' );
      @endphp

      <p class="form-group woocommerce-form-row form-row">
				<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>

        <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit btn" name="register" value="<?php esc_attr_e('Enviar', 'woocommerce'); ?>"><?php esc_html_e('Enviar', 'woocommerce'); ?></button>
			</p>

			@php
        do_action( 'woocommerce_register_form_end' );
      @endphp

		</form>

	</div>

</div>
@endif

@php
  do_action( 'woocommerce_after_customer_login_form' );
@endphp
