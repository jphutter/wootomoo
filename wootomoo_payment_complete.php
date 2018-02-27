<?php


if( ! defined( 'ABSPATH' ) ) exit;

include_once 'wootomoo_lib_moodle.php';

function wootomoo_payment_complete_action( $order_id ) {
	global $wpdb, $table_prefix;

	if ( ! $order_id ) {
		return;
	}

	// *** prepare
	$wc_order = new WC_Order( $order_id );
	$items = $wc_order->get_items();
	$wc_user = $wc_order->get_user();

	// *** get user data
	$user_first_name = $wc_order->get_billing_first_name();
	$user_last_name = $wc_order->get_billing_last_name();
	$user_email = $wc_order->get_billing_email();
	if( $wc_user )
		$user_login = $wc_user->data->user_login;
	else
		$user_login = $user_email;
	// make the username consistent with Moodle
	$user_login = preg_replace('/[^a-z0-9_\-\.\@]/', '', strtolower($user_login));

	// *** get list of products that the user bought
	$products = array();
	foreach( $items as $item ) {
		$product_id = $item->get_product_id();
		if( ! in_array( $product_id, $products )) {
			array_push( $products, $product_id );
		}
	}

	// *** get list of courses to register
	$courses1 = $wpdb->get_results("SELECT course_id FROM {$wpdb->prefix}jph_links WHERE product_id IN (" . implode(",", $products) . ");");
	$courses = array();
	foreach( $courses1 as $course ) {
		if( ! in_array( $course, $courses )) {
			array_push( $courses, $course );
		}
	}

	// *** all done if there is no courses to register
	if( empty($courses) ) {
		return;
	}

	// *** get existing accounts from moodle
	$user_by_username = wootomoo_call_moodle( 'core_user_get_users', array( 'criteria' => array( array( 'key' => 'username', 'value' => $user_login ) ) ) );
	$id_by_username = -1;
	if( $user_by_username !== false && ! empty( $user_by_username ) ) {
		$id_by_username = $user_by_username[0]->id;
	}
	$user_by_email = wootomoo_call_moodle( 'core_user_get_users', array( 'criteria' => array( array( 'key' => 'email', 'value' => $user_email ) ) ) );
	$id_by_email = -1;
	if( $user_by_email !== false && ! empty( $user_by_email ) ) {
		$id_by_email = $user_by_email[0]->id;
	}

	// *** check if we will use existing Moodle account or create a new one
	$account_id = -1;
	// if account already exist for that username and email, then use it
	if( $id_by_username != -1 && $id_by_username == $id_by_email )
		$account_id = $id_by_username;
	// if there is no account for that username or email, then take note to create one
	else if( $id_by_username == -1 && $id_by_email == -1 ) {
		$account_id = -2;
	}
	else {
		// there is an account for that username but with a different email
		if( $id_by_username != -1 )
			$temp = (array) $user_by_username;
		// there is an account for that email but with a different username
		else
			$temp = (array) $user_by_email;
		// get Moodle account infos
		$moodle_firstname = $temp[0]->firstname;
		$moodle_lastname = $temp[0]->lastname;
		$moodle_id = $temp[0]->id;
		$moodle_email = $temp[0]->email;
		$moodle_username = $temp[0]->username;
		if( strtolower($moodle_firstname) == strtolower($user_first_name) && strtolower($moodle_lastname) == strtolower($user_last_name) )
			// same name so use that account
			$account_id = $moodle_id;
		else
			// send email to admin about conflict
			$account_id = -3;
	}

	// at this point, $account_id can have the followings values :
	//    >= 0 : user account exist in moodle and will be used to register
	//	  -2 : indicate that a new account should be created
	//    -3 : indicate a conflict : moodle account don't match user info

	$user_password = null;
	$user = array();
	// *** create new Moodle account
	if( $account_id == -2 ) {
		$user['firstname'] = $user_first_name;
		$user['lastname'] = $user_last_name;
		$user['email'] = $user_email;
		$user['username'] = $user_login;
		$user_password = wp_generate_password( 8, true );
		$user['password'] = $user_password;
		$user['auth'] = 'manual';
		$user['lang'] = 'fr';
		$users = array($user);
		$params = array('users' => $users);

		$moodle_user = wootomoo_call_moodle( 'core_user_create_users', $params );
		if( $moodle_user !== false && array_key_exists( 0, $moodle_user ) ) {
			$account_id = $moodle_user[0]->id;
		}
		else {
			$account_id = -4;
		}
	}

	// at this point, $account_id can have the followings values :
	//    >= 0 : user account exist in moodle and will be used to register
	//           and if this is a new account then $user_password is not null
	//    -3 : indicate a conflict : moodle account don't match user info
	//    -4 : indicate that a new account could not beeen created, look at $user for details

	$success = array();
	$fail = array();
	// *** register course(s)
	if( $account_id >= 0 ) {
		foreach( $courses as $course ) {

			$enrolment = array();
			$enrolment['userid'] = $account_id;
			$enrolment['courseid'] = $course->course_id;
			$enrolment['roleid'] =  5;
			$enrolments = array($enrolment);
			$params = array('enrolments' => $enrolments);

			$resp = wootomoo_call_moodle( 'enrol_manual_enrol_users', $params );
			if( $resp === true ) {
				array_push( $success, $course );
			}
			else {
				array_push( $fail, $course );
				$account_id = -5;
			}
		}
	}
	// at this point, $account_id can have the followings values :
	//    >= 0 : succesfull enrolement was done and list of courses is in $success
	//           and if account was created then $user_password is not null
	//    -3 : indicate a conflict : moodle account don't match user info
	//    -4 : indicate that a new account could not beeen created, look at $moodle_user for details
	//    -5 : enrolement was done with one or more errors, and courses are listed in $success and $fail
	//         also if account was created then $user_password is not null

	$headers = array('Content-Type: text/html; charset=UTF-8');
	add_filter( 'wp_mail_from', 'wootomoo_sender_email' );
	add_filter( 'wp_mail_from_name', 'wootomoo_sender_name' );

	if( $account_id >= 0 ) {

		// sending email to client

		$to = $user_email;
		$subject = __( 'Here are your connection data', 'woocommerce-to-moodle' );
		$heading = __( 'Here are your connection data', 'woocommerce-to-moodle' );

		$html_message = '<h2>' . __( 'Here are your connection data', 'woocommerce-to-moodle' ) . '</h2>';

		$html_message .= '<div style="margin-bottom: 40px;">';
		$html_message .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; color: #636363; border: 1px solid #e5e5e5;" border="1">';
		$html_message .= '<tfoot>';
		$html_message .= '<tr>';
		$html_message .= '<th class="td" scope="row" colspan="1" style="text-align: left; border-top-width: 4px; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . __( 'Web site', 'woocommerce-to-moodle' ) . ' :</th>';
		$html_message .= '<td class="td" style="text-align: left; border-top-width: 4px; color: #636363; border: 1px solid #e5e5e5; padding: 12px;"><a href="' . esc_attr( get_option(WOOTOMOO_URL_KEY) ) . '/login/">' . esc_attr( get_option(WOOTOMOO_URL_KEY) ) . '</a></td>';
		$html_message .= '</tr>';
		$html_message .= '<tr>';
		$html_message .= '<th class="td" scope="row" colspan="1" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">Nom d\'usagé :</th>';
		$html_message .= '<td class="td" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . $user_login . '</td>';
		$html_message .= '</tr>';
		$html_message .= '<tr>';
		$html_message .= '<th class="td" scope="row" colspan="1" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">Mot de passe :</th>';
		$html_message .= ('<td class="td" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . (is_null($user_password) ? __( 'your usual password', 'woocommerce-to-moodle' ) : $user_password) . '</td>');
		$html_message .= '</tr>';
		$html_message .= '</tfoot>';
		$html_message .= '</table>';
		$html_message .= '</div>';

		$html_message .= '<h2 style="color: #147030; display: block; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left;">';
		if( count($success) > 1 )
			$html_message .= __( 'For the following courses', 'woocommerce-to-moodle' ) . '</h2>';
		else
			$html_message .= __( 'For the following course', 'woocommerce-to-moodle' ) . '</h2>';
		$html_message .= '<div style="margin-bottom: 40px;">';
		$html_message .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; color: #636363; border: 1px solid #e5e5e5;" border="1">';
		$html_message .= '<tfoot>';
		foreach( $success as $course ) {
			$tableName = $wpdb->prefix . 'jph_links';
			$course_name = $wpdb->get_var("SELECT course_name FROM {$tableName} WHERE course_id = " . $course->course_id);
			$html_message .= '<tr>';
			$html_message .= ('<th class="td" scope="row" colspan="4" style="text-align: left; border-top-width: 4px; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . $course_name . '</th>');
			$html_message .= '</tr>';
		}
		$html_message .= '</tfoot>';
		$html_message .= '</table>';
		$html_message .= '</div>';

		$mailer = WC()->mailer();
		$wrapped_message = $mailer->wrap_message($heading, $html_message);
		$wc_email = new WC_Email;
		$message = $wc_email->style_inline($wrapped_message);

	}
	else {

		// sending email to administrator

		$wc_email = new WC_Email;
		$to = $wc_email->get_from_address();
		$subject = "Woocommerce to Moodle";
		$message = "";

		if( $account_id == -3 ) {
			// indicate a conflict : moodle account don't match user info
			$message .= "conflict with user account<br>";
			$message .= "From Woocommerce<br>";
			$message .= "================<br>";
			$message .= "First name : $user_first_name<br>";
			$message .= "Last name : $user_last_name<br>";
			$message .= "Email : $user_email<br>";
			$message .= "Username : $user_login<br>";
			$message .= "<br>";
			$message .= "From Moodle<br>";
			$message .= "===========<br>";
			$message .= "First name : $moodle_firstname<br>";
			$message .= "Last name : $moodle_lastname<br>";
			$message .= "Email : $moodle_email<br>";
			$message .= "Username : $moodle_username<br>";
		}
		else if( $account_id == -4 ) {
			// indicate that a new account could not beeen created, look at $moodle_user for details
			$message .= "Could not create user account<br>";
			$message .= "=============================<br>";
			$message .= "First name : $user_first_name<br>";
			$message .= "Last name : $user_last_name<br>";
			$message .= "Email : $user_email<br>";
			$message .= "Username : $user_login<br>";
		}
		else if( $account_id == -5 ) {
			// enrolement was done with one or more errors and courses list is in $success and $fail
			if( is_null($user_password) ) {
				$message .= "Existing account used<br>";
				$message .= "=====================<br>";
				$message .= "First name : $moodle_firstname<br>";
				$message .= "Last name : $moodle_lastname<br>";
				$message .= "Email : $moodle_email<br>";
				$message .= "Username : $moodle_username<br>";
				$message .= "<br>";
			}
			else {
				$message .= "Account created<br>";
				$message .= "===============<br>";
				$message .= "First name : $user_first_name<br>";
				$message .= "Last name : $user_last_name<br>";
				$message .= "Email : $user_email<br>";
				$message .= "Username : $user_login<br>";
				$message .= "<br>";
			}
			foreach( $success as $course ) {
				$tableName = $wpdb->prefix . 'jph_links';
				$course_name = $wpdb->get_var("SELECT course_name FROM {$tableName} WHERE course_id = " . $course->course_id);
				$message .= ("success enrollment for course " . $course_name . "<br>");
			}
			foreach( $fail as $course ) {
				$tableName = $wpdb->prefix . 'jph_links';
				$course_name = $wpdb->get_var("SELECT course_name FROM {$tableName} WHERE course_id = " . $course->course_id);
				$message .= ("fail enrollment for course " . $course_name . "<br>");
			}
		}
		else {
			$message .= "ERROR in email composition";
		}
	}

	$status = wp_mail($to, $subject, $message, $headers);

    remove_filter( 'wp_mail_from', 'wootomoo_sender_email' );
	remove_filter( 'wp_mail_from_name', 'wootomoo_sender_name' );
}


function wootomoo_sender_email( $original_email_address ) {
	$wc_email = new WC_Email;
    return $wc_email->get_from_address();
}

function wootomoo_sender_name( $original_email_from ) {
	$wc_email = new WC_Email;
    return $wc_email->get_from_name();
}

