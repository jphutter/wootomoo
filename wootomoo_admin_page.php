<?php


if( ! defined( 'ABSPATH' ) ) exit;

include_once 'wootomoo_lib_moodle.php';

$wootomoo_products = array();
$wootomoo_courses = array();
$wootomoo_links = array();


function wootomoo_admin_page() {
    global $wpdb;
    global $wootomoo_products;
    global $wootomoo_courses;
    global $wootomoo_links;

    if ( !current_user_can( 'manage_options' ) )  {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    if( ! wootomoo_get_courses() ) {
        ?>
        <div class="wrap">

            <h2><?php esc_html_e( 'Automatic registration on Moodle.', 'woo-to-moo' ) ?></h2>
            <br class="clear">
            <?php esc_html_e( 'There is an error accessing Moodle.', 'woo-to-moo' ) ?><br>
            <a href="options-general.php?page=wootomoo_settings_menu"><?php esc_html_e( 'Click here to check Moodle settings.', 'woo-to-moo' ) ?></a><br>

        </div>
        <?php
    }
    else {
        wootomoo_get_products();
        wootomoo_get_links();

        $sel_function = null;
        if( isset($_GET['fnc']) ) {
            $sel_function = preg_replace("/[^a-z]/", "", sanitize_text_field( $_GET['fnc'] ));
            if( $sel_function != 'delete' && $sel_function != 'add' )
                $sel_function = null;
        }
        $sel_product_id = null;
        if( isset($_POST['produit']) ) {
            $sel_product_id = intval( $_POST['produit'] );
            if( $sel_product_id <= 0 )
                $sel_product_id = null;
        }
        $sel_course_id = null;
        if( isset($_POST['cours']) ) {
            $sel_course_id = intval( $_POST['cours'] );
            if( $sel_course_id <= 0 )
                $sel_course_id = null;
        }
        $sel_link_id = null;
        if( isset($_GET['id']) ) {
            $sel_link_id = intval( $_GET['id'] );
            if( $sel_link_id <= 0 )
                $sel_link_id = null;
        }

        if( $sel_function != null ) {
            if( $sel_function == 'delete' ) {
                if( isset($_REQUEST['_wpnonce']) ) {
                    if( wp_verify_nonce($_REQUEST['_wpnonce'], 'wootomoo_admin_page_delete' ) ) {

                        if( $sel_link_id  ) {
                            wootomoo_delete_link( $sel_link_id );
                        }
                        else {
                            $sel_function = null;
                            $sel_product_id = null;
                            $sel_course_id = null;
                            $sel_link_id = null;
                        }
                    }
                    else
                        wp_die('Unauthorized access.');
                }
                else
                    wp_die('Unauthorized access.');
            }
            else if( $sel_function == 'add' ) {
                if( isset($_REQUEST['_wpnonce']) ) {
                    if( wp_verify_nonce($_REQUEST['_wpnonce'], 'wootomoo_admin_page_add' ) ) {

                        if( $sel_product_id && $sel_course_id ) {
                            $found = false;
                            if( ! empty( $wootomoo_links ) ) {
                                foreach( $wootomoo_links as $link ) {
                                    if( $link->product_id == $sel_product_id && $link->course_id == $sel_course_id ) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if( ! $found ) {
                                wootomoo_add_link( $sel_product_id, $sel_course_id );
                            }
                        }
                        else {
                            $sel_function = null;
                            $sel_product_id = null;
                            $sel_course_id = null;
                            $sel_link_id = null;
                        }
                    }
                    else
                        wp_die('Unauthorized access.');
                }
                else
                    wp_die('Unauthorized access.');
            }
            else
                wp_die('Error processing request.');
        }

        include 'wootomoo_admin_page_display.php';
    }
}


function wootomoo_get_products() {
    global $wpdb;
    global $wootomoo_products;

    $posts = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status <> 'trash';");
    if( $posts ) {
        foreach( $posts as $post ) {
            $pid = $post->ID;
            $name = get_the_title( $pid );
            $productdesc = wc_get_product( $pid );

            $p_cat = $productdesc->get_category_ids();
            $categories = get_option(WOOTOMOO_CAT_LIST);
            if( $categories )
                foreach( $categories as $test ) {
                    if(in_array( $test, (array) $p_cat )) {
                        array_push( $wootomoo_products, array( 'product_id'=>$pid, 'product_name'=>$name ) );
                        break;
                    }
                }
        }
    }
}


function wootomoo_get_courses() {
    global $wootomoo_courses;

    $m_courses = array();
    // gat all courses from Moodle
    $m_courses = wootomoo_call_moodle( 'core_course_get_courses' );

    if( $m_courses === false ) {
        return false;
    }
    // create $wootomoo_courses by removing the 'site' course
    if( ! empty( $m_courses ) ) {
        foreach( $m_courses as $course ) {
            $cid = $course->id;
            $name = $course->displayname;
            if( $course->format == 'site' ) {
                continue;
            }
            array_push( $wootomoo_courses, array( 'course_id'=>$cid, 'course_name'=>$name ) );
        }
    }
    return true;
}


function wootomoo_get_links() {
    global $wpdb;
    global $wootomoo_links;

    $wootomoo_links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wootomoo_links;");
}


function wootomoo_delete_link( $id ) {
    global $wpdb;

    $wpdb->delete(
        $wpdb->prefix . 'wootomoo_links',
        [ 'id' => $id ],
        [ '%d' ] );
    wootomoo_get_links();
}


function wootomoo_add_link( $product_id, $course_id ) {
    global $wpdb;
    global $wootomoo_products;
    global $wootomoo_courses;

    $product_name = "";
    foreach( $wootomoo_products as $product ) {
        if( $product['product_id'] == $product_id ) {
            $product_name = $product['product_name'];
            break;
        }
    }
    $course_name = "";
    foreach( $wootomoo_courses as $course ) {
        if( $course['course_id'] == $course_id ) {
            $course_name = $course['course_name'];
            break;
        }
    }
    $wpdb->insert(
        $wpdb->prefix . 'wootomoo_links',
        array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'course_id' => $course_id,
            'course_name' => $course_name
        )
    );
    wootomoo_get_links();
}

