<?php


function inflo_activate() {

}

register_activation_hook( __FILE__, 'inflo_activate' );


function inflo_uninstall() {
    delete_option('inflo_auth_key');
    delete_option('inflo_connect_pin');
    delete_option('inflo_user_id');
}

register_uninstall_hook(__FILE__, 'inflo_uninstall');

?>