{{--
Edit account form

This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.5.0
--}}

@php
  do_action( 'woocommerce_before_edit_account_form' );
@endphp

<form class="woocommerce-EditAccountForm edit-account" action="" method="post" @php do_action( 'woocommerce_edit_account_form_tag' ); @endphp >

	@php
    do_action( 'woocommerce_edit_account_form_start' );
  @endphp

	<div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first form-group">
		<label for="account_first_name" class="sr-only">{{ esc_html_e( 'First name', 'woocommerce' ) }}</label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control" name="account_first_name" id="account_first_name" autocomplete="given-name" placeholder="{{ esc_html_e( 'First name', 'woocommerce' ) }}" value="{{ esc_attr( $user->first_name ) }}" />
	</div>

	<div class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last form-group">
		<label for="account_last_name" class="sr-only">{{ esc_html_e( 'Last name', 'woocommerce' ) }}</label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control" name="account_last_name" id="account_last_name" autocomplete="family-name" placeholder="{{ esc_html_e( 'Last name', 'woocommerce' ) }}" value="{{ esc_attr( $user->last_name ) }}" />
	</div>

	<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-group">
		<label for="account_display_name" class="sr-only">{{ esc_html_e( 'Display name', 'woocommerce' ) }}</label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-control" name="account_display_name" id="account_display_name" placeholder="{{ esc_html_e( 'Display name', 'woocommerce' ) }}" value="{{ esc_attr( $user->display_name ) }}" /> <span><em>{{ esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ) }}</em></span>
	</div>

	<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-group">
		<label for="account_email" class="sr-only">{{ esc_html_e( 'Email address', 'woocommerce' ) }}</label>
		<input type="email" class="woocommerce-Input woocommerce-Input--email input-text form-control" name="account_email" id="account_email" autocomplete="email" placeholder="{{ esc_html_e( 'Email address', 'woocommerce' ) }}" value="{{ esc_attr( $user->user_email ) }}" />
	</div>

  @if ('teacher' == $user->roles[0])
    <div class="fieldset" role="group">
      <h2>Professor</h2>
      <div class="form-group form-row-first">
        <label for="user_school" class="sr-only">Instituição</label>
        <input type="text" class="form-control" name="user_school" id="user_school" placeholder="Instituição" value="{{ the_field('user_school', 'user_'.$user->ID) }}" />
      </div>
      <div class="form-group form-row-first">
        <label for="user_subject" class="sr-only">Disciplina</label>
        <input type="text" class="form-control" name="user_subject" id="user_subject" placeholder="Disciplina" value="{{ the_field('user_subject', 'user_'.$user->ID) }}" />
      </div>

      <div class="form-group form-row-wide">
        <label for="user_area" class="sr-only">Área</label>
        <select name="user_area" id="user_area" class="form-control">
          @php
            $user_area = get_field('user_area', 'user_'.$user->ID);
          @endphp

          <option value="">ÁREA</option>
          <option value="Educação Infantil" {!! selected( $user_area, "Educação Infantil") !!}>Educação Infantil</option>
          <option value="Fundamental 1" {!! selected( $user_area, "Fundamental 1") !!}>Fundamental 1</option>
          <option value="Fundamental 2" {!! selected( $user_area, "Fundamental 2") !!}>Fundamental 2</option>
          <option value="Ensino Médio" {!! selected( $user_area, "Ensino Médio") !!}>Ensino Médio</option>
          <option value="Ensino Superior" {!! selected( $user_area, "Ensino Superior") !!}>Ensino Superior</option>
        </select>
      </div>
    </div>
  @elseif('bookseller' == $user->roles[0])
    <div class="fieldset" role="group">
      <h2>Livreiro</h2>
      <div class="form-group form-row-first">
        <label for="user_bookstore" class="sr-only">Livraria</label>
        <input type="text" class="form-control" name="user_bookstore" id="user_bookstore" placeholder="Livraria" value="{{ the_field('user_bookstore', 'user_'.$user->ID) }}" />
      </div>

      <div class="form-group form-row-last">
        <label for="user_interest" class="sr-only">Áreas de interesse</label>
        <input type="text" class="form-control" name="user_interest" id="user_interest" placeholder="Áreas de interesse" value="{{ the_field('user_interest', 'user_'.$user->ID) }}" />
      </div>
    </div>
  @endif

  @if(PageMinhaConta::isMailchimpSbuscribed($user->user_email))
    <div class="fieldset" role="group">
      <h2>Newsletter</h2>
      <div class="form-group form-row-wide">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="remove_user_email" name="remove_user_email">
          <label class="custom-control-label" for="remove_user_email">REMOVER MEU E-MAIL DO MAILING DA EDITORA</label>
        </div>
      </div>
    </div>
  @endif

	<fieldset>
		<legend>{{ esc_html_e( 'Password change', 'woocommerce' ) }}</legend>

		<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-group">
			<label for="password_current" class="sr-only">{{ esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ) }}</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text form-control" name="password_current" id="password_current" autocomplete="off" placeholder="{{ esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ) }}" />
		</div>
		<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-group">
			<label for="password_1" class="sr-only">{{ esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ) }}</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text form-control" name="password_1" id="password_1" autocomplete="off" placeholder="{{ esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ) }}" />
		</div>
		<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-group">
			<label for="password_2" class="sr-only">{{ esc_html_e( 'Confirm new password', 'woocommerce' ) }}</label>
			<input type="password" class="woocommerce-Input woocommerce-Input--password input-text form-control" name="password_2" id="password_2" autocomplete="off" placeholder="{{ esc_html_e( 'Confirm new password', 'woocommerce' ) }}" />
		</div>
	</fieldset>

	@php
    do_action( 'woocommerce_edit_account_form' );
  @endphp

	<div>
		@php
      wp_nonce_field( 'save_account_details', 'save-account-details-nonce' );
    @endphp

		<button type="submit" class="woocommerce-Button button" name="save_account_details" value="{{ esc_attr_e( 'Save changes', 'woocommerce' ) }}">{{ esc_html_e( 'Save changes', 'woocommerce' ) }}</button>
		<input type="hidden" name="action" value="save_account_details" />
	</div>

	@php
    do_action( 'woocommerce_edit_account_form_end' );
  @endphp
</form>

@php
  do_action( 'woocommerce_after_edit_account_form' );
@endphp
