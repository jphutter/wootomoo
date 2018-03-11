<?php


if( ! defined( 'ABSPATH' ) ) exit;

/*

list of all possible wp_remote_retrieve_body($response) for each Moodle command
===============================================================================

core_course_get_courses
-----------------------
[]
[{"id":1,"shortname":"xxxxx","categoryid":0,"categorysortorder":1,"fullname":"xxxxxxx","displayname":"xxxxxx","idnumber":"",...}, ...]
{"exception":"moodle_exception",...}

core_user_get_users
-------------------
{"users":[],"warnings":[]}
{"users":[{"id":999,"username":"xxxxxx","firstname":"xxxx","lastname":"xxxxx","fullname":"xxxxx","email":"xxxxx","department":"",...}],"warnings":[]}
{"exception":"moodle_exception",...}

core_user_create_users
----------------------
[{"id":999,"username":"xxxxxxx"}]
{"exception":"moodle_exception",...}

enrol_manual_enrol_users
------------------------
null
{"exception":"moodle_exception",...}

*/



function wootomoo_call_moodle( $function_name = '', $request_param = array() ) {
    $response = null;

    foreach( $request_param as $param ) {
        if( is_array( $param ) ) {
            foreach( $param as $par ) {
                if( is_array( $par ) ) {
                    foreach( $par as &$p ) {
                        $p = esc_attr($p);
                    }
                }
                else
                    $par = esc_attr($par);
            }
        }
        else
            $param = esc_attr($param);
    }

    $url = get_option( WOOTOMOO_URL_KEY );
    $url = esc_url( $url );

    $token = get_option( WOOTOMOO_TOKEN_KEY );
    $token = sanitize_key( $token );

    $request_url = $url . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $function_name . '&moodlewsrestformat=json';

    $request_query = http_build_query( $request_param );
    $response = wp_remote_post( $request_url, array( 'body' => $request_query ) );

    if( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
        return false;
    }
    $body = json_decode( wp_remote_retrieve_body( $response ) );
    if( $body == null ) {
        if( $function_name == 'enrol_manual_enrol_users' ) {
            return true;
        }
        else {
            return false;
        }
    }
    if( array_key_exists( 'exception', $body ) ) {
        return false;
    }

    if( $function_name == 'core_user_get_users' ) {
        return $body->users;
    }
    return $body;
}
