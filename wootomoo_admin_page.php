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

	$sel_function = null;
    if( isset($_GET['fnc']) ) {
		$sel_function = $_GET['fnc'];
	}
	$sel_product_id = null;
    if( isset($_POST['produit']) ) {
		$sel_product_id = $_POST['produit'];
	}
	$sel_course_id = null;
    if( isset($_POST['cours']) ) {
		$sel_course_id = $_POST['cours'];
	}
	$sel_link_id = null;
    if( isset($_GET['id']) ) {
		$sel_link_id = $_GET['id'];
	}

	if( ! wootomoo_get_courses() ) {
		?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Automatic registration on Moodle.', 'woocommerce-to-moodle' ) ?></h2>
			<br class="clear">
			<?php esc_html_e( 'There is an error accessing Moodle.', 'woocommerce-to-moodle' ) ?><br>
			<a href="options-general.php?page=wootomoo_settings_menu"><?php esc_html_e( 'Click here to check Moodle settings.', 'woocommerce-to-moodle' ) ?></a><br>

		</div>
		<?php
	}
	else {
		wootomoo_get_products();
		wootomoo_get_links();

		if( $sel_function == 'delete' && $sel_link_id  ) {
			wootomoo_delete_link( $sel_link_id );
		}

		if( $sel_function == 'add' && $sel_product_id && $sel_course_id ) {
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
		?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Automatic registration on Moodle.', 'woocommerce-to-moodle' ) ?></h2>
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
									<a href="<?php echo admin_url('edit.php?post_type=product&amp;page=' . WOOTOMOO_LINKS_PAGE . '&amp;fnc=delete&amp;id=' . $row->id); ?>"><?php esc_html_e( 'Delete', 'woocommerce-to-moodle' ) ?></a>
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
					<option value="" disabled selected hidden><?php esc_html_e( 'Choose a product...', 'woocommerce-to-moodle' ) ?></option>
					<?php
					foreach( $wootomoo_products as $product ) {
						?>
						<option value=<?php echo '"' . strval($product['product_id']) . '">' . $product['product_name'] ?></option>
					<?php } ?>
				</select>
				<select name="cours" required>
					<option value="" disabled selected hidden><?php esc_html_e( 'Choose a course...', 'woocommerce-to-moodle' ) ?></option>
					<?php
					foreach( $wootomoo_courses as $course ) {
						?>
						<option value=<?php echo '"' . strval($course['course_id']) . '">' . $course['course_name'] ?></option>
					<?php } ?>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Add', 'woocommerce-to-moodle' ) ?>">
			</form>

		</div>
		<?php
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
			$sel_categories = get_option(WOOTOMOO_CAT_LIST);
			foreach( $sel_categories as $test ) {
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

	$wootomoo_links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}jph_links;");
}


function wootomoo_delete_link( $id ) {
	global $wpdb;

	$wpdb->delete(
		$wpdb->prefix . 'jph_links',
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
		$wpdb->prefix . 'jph_links',
		array(
			'product_id' => $product_id,
			'product_name' => $product_name,
			'course_id' => $course_id,
			'course_name' => $course_name
        )
    );
	wootomoo_get_links();
}

