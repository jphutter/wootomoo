<?php


if( ! defined( 'ABSPATH' ) ) exit;

function wootomoo_settings_menu() {
    if( ! current_user_can('manage_options') ) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
    <h1><?php esc_html_e( 'Woo to Moo plugin settings', 'woo-to-moo' ) ?></h1>

    <form method="post" action="options.php">

        <?php settings_fields( WOOTOMOO_SETTINGS ); ?>
        <?php do_settings_sections( WOOTOMOO_SETTINGS ); ?>
        <table class="form-table">

            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'The URL of your Moodle site.', 'woo-to-moo' ) ?></th>
                <td><input type="url" size="40" maxlength="80" placeholder="https://" name="<?php echo WOOTOMOO_URL_KEY; ?>" value="<?php echo get_option(WOOTOMOO_URL_KEY); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'The WebService token from Moodle. (see below)', 'woo-to-moo' ) ?></th>
                <td><input type="text" size="40" maxlength="32" name="<?php echo WOOTOMOO_TOKEN_KEY; ?>" value="<?php echo get_option(WOOTOMOO_TOKEN_KEY); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'List of product categories to use.', 'woo-to-moo' ) ?></th>
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

    <h3><?php esc_html_e( 'How to get the Webservice Token from Moodle.', 'woo-to-moo' ) ?></h3>
    1.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Advanced features"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'select "Enable web services"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woo-to-moo' ) ?>.<br>
    2.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Manage protocols"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'enable "REST protocol"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woo-to-moo' ) ?><br>
    3.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"External services"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add"', 'woo-to-moo' ) ?><br>
    <p style="margin-left: 40px">
        a.&nbsp;&nbsp; <?php esc_html_e( 'give a "Name"', 'woo-to-moo' ) ?><br>
        b.&nbsp;&nbsp; <?php esc_html_e( 'check "Enabled"', 'woo-to-moo' ) ?><br>
        c.&nbsp;&nbsp; <?php esc_html_e( '"Add Service"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add functions"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'select following list of functions:', 'woo-to-moo' ) ?><br>
    </p>
    <p style="margin-left: 80px">
            "core_user_create_users: Create users"<br>
            "core_user_get_users: Search for users matching the parameters"<br>
            "core_course_get_courses: Return course details"<br>
            "enrol_manual_enrol_users: Manual enrol users"<br>
    </p>
    <p style="margin-left: 40px">
        d.&nbsp;&nbsp; <?php esc_html_e( 'click "Add functions" at the end', 'woo-to-moo' ) ?><br>
    </p>
    4.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Plugins"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Manage tokens"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Add"', 'woo-to-moo' ) ?><br>
    <p style="margin-left: 40px">
        a.&nbsp;&nbsp; <?php esc_html_e( 'select one "User" with admin privilege', 'woo-to-moo' ) ?><br>
        b.&nbsp;&nbsp; <?php esc_html_e( 'select the "Service" you created in step 3.', 'woo-to-moo' ) ?><br>
        c.&nbsp;&nbsp; click <?php esc_html_e( '"Save changes"', 'woo-to-moo' ) ?><br>
    </p>
    5.&nbsp;&nbsp; <?php esc_html_e( 'And here is your token.', 'woo-to-moo' ) ?><br>
    <br>
    <span style="font-weight:bold"><?php esc_html_e( 'Just one last point.', 'woo-to-moo' ) ?> </span><?php esc_html_e( 'The password policy settings needed to be disabled since the new user password will be generated in WordPress and will not match the password policy of Moodle.', 'woo-to-moo' ) ?>
    <br><br>
    6.&nbsp;&nbsp; <?php esc_html_e( '"Site administration"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Site policies"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( 'uncheck "Password policy"', 'woo-to-moo' ) ?> &nbsp;&nbsp;&gt;&nbsp;&nbsp; <?php esc_html_e( '"Save changes"', 'woo-to-moo' ) ?>.

    </div>
    <?php
}
