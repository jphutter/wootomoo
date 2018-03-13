<div class="wrap">

    <h2><?php esc_html_e( 'Automatic registration on Moodle.', 'woo-to-moo' ) ?></h2>
    <br class="clear">
    <table class='wp-list-table widefat fixed striped posts'>
        <tr>
            <th class="manage-column width:40%;">Produit</th>
            <th class="manage-column width:40%">Cours</th>
            <th class="manage-column width:20%"></th>
            <th>&nbsp;</th>
        </tr>
        <?php foreach ($wootomoo_links as $row) { ?>
            <tr>
                <td class="manage-column width:40%">
                <?php
                    $found = false;
                    foreach( $wootomoo_products as $product ) {
                        if( $product['product_id'] == $row->product_id ) {
                            $found = true;
                            break;
                        }
                    }
                    if( $found )
                        echo $row->product_name;
                    else
                        echo '<span style="color:red">' . $row->product_name . '</span>';
                ?>
                </td>
                <td class="manage-column width:40%">
                <?php
                    $found = false;
                    foreach( $wootomoo_courses as $course ) {
                        if( $course['course_id'] == $row->course_id ) {
                            $found = true;
                            break;
                        }
                    }
                    if( $found )
                        echo $row->course_name;
                    else
                        echo '<span style="color:red">' . $row->course_name . '</span>';
                ?>
                </td>
                <td class="manage-column width:20%">
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <a href="<?php echo
                                wp_nonce_url(
                                    admin_url('edit.php?post_type=product&amp;page='
                                        . WOOTOMOO_LINKS_PAGE
                                        . '&amp;fnc=delete&amp;id='
                                        . $row->id
                                        ),
                                    'wootomoo_admin_page_delete');
                                ?>"><?php esc_html_e( 'Delete', 'woo-to-moo' ) ?></a>
                        </div>
                        <br class="clear">
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>

    <br class="clear"><br><br>

    <style>
        select:invalid { color: gray; }
    </style>
    <form action="<?php echo admin_url('edit.php?post_type=product&amp;page=' . WOOTOMOO_LINKS_PAGE . '&amp;fnc=add'); ?>" method="post">
        <select name="produit" required>
            <option value="" disabled selected hidden><?php esc_html_e( 'Choose a product...', 'woo-to-moo' ) ?></option>
            <?php
            foreach( $wootomoo_products as $product ) {
                ?>
                <option value=<?php echo '"' . strval($product['product_id']) . '">' . $product['product_name'] ?></option>
            <?php } ?>
        </select>
        <select name="cours" required>
            <option value="" disabled selected hidden><?php esc_html_e( 'Choose a course...', 'woo-to-moo' ) ?></option>
            <?php
            foreach( $wootomoo_courses as $course ) {
                ?>
                <option value=<?php echo '"' . strval($course['course_id']) . '">' . $course['course_name'] ?></option>
            <?php } ?>
        </select>
        <?php wp_nonce_field( 'wootomoo_admin_page_add' ); ?>
        <input type="submit" value="<?php esc_html_e( 'Add', 'woo-to-moo' ) ?>">
    </form>

</div>
