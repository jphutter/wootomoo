<?php


if( ! defined( 'ABSPATH' ) ) exit;

function wootomoo_settings_menu() {
    if( ! current_user_can('manage_options') ) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
	?>
	<div class="wrap">
	<h1><?php esc_html_e( 'Woocommerce to Moodle plugin settings', 'woocommerce-to-moodle' ) ?></h1>

	<form method="post" action="options.php">

		<?php settings_fields( WOOTOMOO_SETTINGS ); ?>
		<?php do_settings_sections( WOOTOMOO_SETTINGS ); ?>
		<table class="form-table">

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'The URL of your Moodle site.', 'woocommerce-to-moodle' ) ?></th>
				<td><input type="text" size="40" name="<?php echo WOOTOMOO_URL_KEY; ?>" value="<?php echo esc_attr( get_option(WOOTOMOO_URL_KEY) ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'The WebService token from Moodle. (see below)', 'woocommerce-to-moodle' ) ?></th>
				<td><input type="text" size="40" name="<?php echo WOOTOMOO_TOKEN_KEY; ?>" value="<?php echo esc_attr( get_option(WOOTOMOO_TOKEN_KEY) ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'List of product categories to use.', 'woocommerce-to-moodle' ) ?></th>
				<td>
				<?php
				$product_categories = get_categories( array(
					'taxonomy'     => 'product_cat',
					'orderby'      => 'cat_name',
					'pad_counts'   => false,
					'hierarchical' => 1,
					'hide_empty'   => false
				) );
				$sel_categories = get_option(WOOTOMOO_CAT_LIST);
				?>
				<select name="<?php echo WOOTOMOO_CAT_LIST; ?>[]" multiple="multiple">
				<?php
				foreach( $product_categories as $cat ) {
					$selected = false;
					if ( in_array( $cat->term_id, (array) $sel_categories ) ) {
						$selected = true;
					}
					?><option <?php echo selected( $selected, true, false ); ?> value="<?php echo esc_attr( $cat->term_id ); ?> ">  <?php echo $cat->cat_name; ?></option><?php
				}
				?>
				</select>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
	</form>

	<h3><?php esc_html_e( 'How to get the Webservice Token from Moodle.', 'woocommerce-to-moodle' ) ?></h3>
	1.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Advanced features"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'select "Enable web services"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woocommerce-to-moodle' ) ?>.<br>
	2.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Manage protocols"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'enable "REST protocol"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woocommerce-to-moodle' ) ?><br>
	3.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"External services"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add"', 'woocommerce-to-moodle' ) ?><br>
	<p style="margin-left: 40px">
		a.&nbsp;&nbsp; <?php esc_html_e( 'give a "Name"', 'woocommerce-to-moodle' ) ?><br>
		b.&nbsp;&nbsp; <?php esc_html_e( 'check "Enabled"', 'woocommerce-to-moodle' ) ?><br>
		c.&nbsp;&nbsp; <?php esc_html_e( '"Add Service"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add functions"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'select following list of functions:', 'woocommerce-to-moodle' ) ?><br>
	</p>
	<p style="margin-left: 80px">
			"core_user_create_users: Create users"<br>
			"core_user_get_users: Search for users matching the parameters"<br>
			"core_course_get_courses: Return course details"<br>
			"enrol_manual_enrol_users: Manual enrol users"<br>
	</p>
	<p style="margin-left: 40px">
		d.&nbsp;&nbsp; <?php esc_html_e( 'click "Add functions" at the end', 'woocommerce-to-moodle' ) ?><br>
	</p>
	4.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Manage tokens"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add"', 'woocommerce-to-moodle' ) ?><br>
	<p style="margin-left: 40px">
		a.&nbsp;&nbsp; <?php esc_html_e( 'select one "User" with admin privilege', 'woocommerce-to-moodle' ) ?><br>
		b.&nbsp;&nbsp; <?php esc_html_e( 'select the "Service" you created in step 3.', 'woocommerce-to-moodle' ) ?><br>
		c.&nbsp;&nbsp; click <?php esc_html_e( '"Save changes"', 'woocommerce-to-moodle' ) ?><br>
	</p>
	5.&nbsp;&nbsp; <?php esc_html_e( 'And here is your token.', 'woocommerce-to-moodle' ) ?><br>
	<br>
	<span style="font-weight:bold"><?php esc_html_e( 'Just one last point.', 'woocommerce-to-moodle' ) ?> </span><?php esc_html_e( 'The password policy settings needed to be disabled since the new user password will be generated in WordPress and will not match the password policy of Moodle.', 'woocommerce-to-moodle' ) ?>
	<br><br>
	6.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Site policies"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'uncheck "Password policy"', 'woocommerce-to-moodle' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woocommerce-to-moodle' ) ?>.

	</div>
    <?php
}
