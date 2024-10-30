<?php

class InfloAI_Debug {
    function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    function admin_menu() {
        add_submenu_page(
            'tools.php',
            'inflo.Ai Debug',
            "",
            'manage_options',
            'infloai-debug',
            array($this, 'debug_render')
        );
    }

    function debug_render() {
        $dump = array(
            "inflo_user" => $this->debug_user(),
            "rest_auth" => InfloAI_RestAuth::validate_auth_headers(),
            "determine_current_user" => $this->debug_hooks("determine_current_user"),
            "rest_authentication_errors" => $this->debug_hooks("rest_authentication_errors")
        );
        
        echo("<h1>inflo.Ai Debug</h1> <pre>");

        var_dump($dump);

        echo("</pre>");
    }

    function debug_user() {
        $inflo_user_id = get_option('inflo_user_id');
        $user = get_user_by("ID", $inflo_user_id);

        return array(
            "inflo_user_id" => $inflo_user_id,
            "inflo_user" => $user
        );
    }

    function debug_hooks( $hook = '' ) {
        global $wp_filter;

        if ( isset( $wp_filter[$hook]->callbacks ) ) {      
            array_walk( $wp_filter[$hook]->callbacks, function( $callbacks, $priority ) use ( &$hooks ) {           
                foreach ( $callbacks as $id => $callback )
                    $hooks[] = array_merge( [ 'id' => $id, 'priority' => $priority ], $callback );
            });         
        } else {
            return [];
        }

        foreach( $hooks as &$item ) {
            // skip if callback does not exist
            if ( !is_callable( $item['function'] ) ) continue;

            // function name as string or static class method eg. 'Foo::Bar'
            if ( is_string( $item['function'] ) ) {
                $ref = strpos( $item['function'], '::' ) ? new ReflectionClass( strstr( $item['function'], '::', true ) ) : new ReflectionFunction( $item['function'] );
                $item['file'] = $ref->getFileName();
                $item['line'] = get_class( $ref ) == 'ReflectionFunction' 
                    ? $ref->getStartLine() 
                    : $ref->getMethod( substr( $item['function'], strpos( $item['function'], '::' ) + 2 ) )->getStartLine();

            // array( object, method ), array( string object, method ), array( string object, string 'parent::method' )
            } elseif ( is_array( $item['function'] ) ) {

                $ref = new ReflectionClass( $item['function'][0] );

                // $item['function'][0] is a reference to existing object
                $item['function'] = array(
                    is_object( $item['function'][0] ) ? get_class( $item['function'][0] ) : $item['function'][0],
                    $item['function'][1]
                );
                $item['file'] = $ref->getFileName();
                $item['line'] = strpos( $item['function'][1], '::' )
                    ? $ref->getParentClass()->getMethod( substr( $item['function'][1], strpos( $item['function'][1], '::' ) + 2 ) )->getStartLine()
                    : $ref->getMethod( $item['function'][1] )->getStartLine();

            // closures
            } elseif ( is_callable( $item['function'] ) ) {     
                $ref = new ReflectionFunction( $item['function'] );         
                $item['function'] = get_class( $item['function'] );
                $item['file'] = $ref->getFileName();
                $item['line'] = $ref->getStartLine();

            }       
        }

        return $hooks;
    }

}


?>