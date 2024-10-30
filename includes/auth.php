<?php

class InfloAI_RestAuth {
    private $auth_error;

    function __construct() {
        add_filter('determine_current_user', array($this, 'auth_api_call'), 20);
        add_filter('rest_authentication_errors', array($this, 'auth_error'));
    }

    static function headers(){
        $hdrs = array();
        foreach ($_SERVER as $key=>$value){
            if (substr($key,0,5)=="HTTP_") {
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                $hdrs[$key]=$value;
            }
        }
        return $hdrs;
    }

    static function err_bad_nonce(){
        return new WP_Error('inflo_invalid_nonce', "Invalid Nonce", array('status' => 403));
    }

    static function err_bad_hash(){
        return new WP_Error('inflo_invalid_hash', "Invalid Hash", array('status' => 403));
    }

    static function validate_auth_headers() {
        $hdrs = InfloAI_RestAuth::headers();

        if (!isset($hdrs['X-Inflo-Nonce']) || !isset($hdrs['X-Inflo-Hash'])) {
            return null;
        }

        $now = time();

        $nonce = $hdrs['X-Inflo-Nonce'];

        if (($now - intval($nonce)) > 10) {
            return InfloAI_RestAuth::err_bad_nonce();
        }

        $hash = hash_hmac('sha512', $nonce, get_option('inflo_auth_key'));

        if (hash_equals($hash, $hdrs['X-Inflo-Hash'])) {
            return true;
        } else {
            return InfloAI_RestAuth::err_bad_hash();
        }
    }

    function auth_api_call($user) {
        $this->auth_error = null;

        if ( ! empty( $user ) ) {
            return $user;
        }

        $check = InfloAI_RestAuth::validate_auth_headers();

        if($check === true) {
            return get_option('inflo_user_id');
        } else {
            $this->auth_error = $check;
        }
    }

    function auth_error($error) {
        if ( ! empty( $error ) ) {
            return $error;
        }
        return $this->auth_error;
    }

}

?>
