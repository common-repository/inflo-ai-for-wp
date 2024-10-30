<?php

class InfloAI_Settings{
    function __construct() {
        $this->page = 'general';
        $this->section = 'infloai_settings';
        $this->capability = 'manage_options';

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('rest_api_init', array($this, 'connect_api_register'));
    }

    function admin_init() {
        add_settings_section(
            $this->section,
            inflo_favicon('page-settings') . "inflo.Ai Settings",
            array($this, 'settings_section_cb'),
            $this->page
        );

        add_settings_field(
            'inflo_user_id',
            "inflo.Ai User",
            array($this, 'setting_user_id_cb'),
            $this->page,
            $this->section
        );

        register_setting(
            'general',
            'inflo_user_id',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_user_id'),
                'default' => $this->get_default_user()
            )
        );

        add_option('inflo_connect_pin', '||', '', 'no');
        add_option('inflo_auth_key', '', '', 'no');

        // Set the default to an 'invalid' value
        // Then check if the value is invalid, and if it is,
        // update it to a valid value (an administrator)
        add_option('inflo_user_id', -1);
        if (get_option('inflo_user_id') == -1) {
            update_option("inflo_user_id", $this->get_default_user());
        }
    }

    function get_default_user() {
        $users = get_users(['role__in' => ['administrator']]);

        foreach($users as $u){
            if($this->user_has_caps($u)){
                return $u->ID;
            }
        }
    }

    function admin_menu() {
        add_submenu_page(
            'tools.php',
            'inflo.Ai Connect',
            inflo_favicon('sidebar-tools') . 'inflo.Ai Connect',
            'manage_options',
            'infloai-connect',
            array($this, 'connect_render')
        );

        add_submenu_page(
            'tools.php',
            'Disconnect inflo.Ai',
            inflo_favicon('sidebar-tools') . 'Disconnect inflo.Ai',
            "manage_options",
            "infloai-disconnect",
            array($this, 'disconnect_render')
        );

        remove_submenu_page("tools.php", "infloai-disconnect");
    }

    function settings_section_cb() {
        $auth_key = get_option('inflo_auth_key');
        if(strlen($auth_key) == 0) {
            ?>
            <h4>inflo.Ai is not connected</h4>
            <p><a href='tools.php?page=infloai-connect'>Click Here to Connect</a></p>
            <?php
        } else {
            ?>
            <h4>inflo.Ai is connected!</h4>
            <p><a href='tools.php?page=infloai-disconnect'>Click Here to disconnect</a></p>
            <?php
        }
    }

    function sanitize_user_id($value) {
        return $value;
    }

    function user_has_caps(&$user) {
        $required_caps = array(
            "edit_posts",
            "publish_posts",
            "read_private_posts",
            "edit_private_posts",
            "edit_published_posts"
        );

        foreach($required_caps as $c) {
            if (!$user->has_cap($c)) {
                return false;
            }
        }
        return true;
    }

    function setting_user_id_cb($args) {
        $current = get_option('inflo_user_id');

        $opts = array();
        $valid_user = false;

        foreach(get_users() as $u) {
            if($this->user_has_caps($u)){
                $opts[$u->ID] = $u->nickname;
                if($current == $u->ID) {
                    $valid_user = true;
                }
            }
        }

        echo("<select name='inflo_user_id'>");

        if (!$valid_user) {
            echo("<option selected disabled>Select a User</option>");
        }

        foreach($opts as $id => $nick){
            $selected = ($id == $current) ? 'selected' : '';
            echo(sprintf("<option value='%s' %s>%s</option>",
                $id,
                $selected,
                $nick
            ));
        }

        ?>

        </select>
        <p>The user inflo.Ai will use to create and edit posts.</p>

        <?php

    }

    function render_connect_confirm() {
        ?>

        <p>
        Good news - you're already connected to inflo.Ai! Continuing here may break your existing integration.
        </p>

        <p>
        There's no need to reconnect your Wordpress and inflo.Ai accounts as you update the plugin.
        </p>

        <a class='button-primary' href='<?php echo $_SERVER["REQUEST_URI"] ?>&confirm=1'>I understand, Continue</a>
        <?php
    }

    function render_do_connect() {
        $pin = rand(100000, 999999);
        $nonce = bin2hex(random_bytes(16));
        $generated = time();
        $expires = $generated + INFLOAI_PIN_EXPIRY;

        update_option('inflo_connect_pin', $pin . "|" . $nonce . "|" . $generated, 'no');

        $wp_b64 = base64_encode(get_bloginfo('wpurl'));

        ?>

        <p>Connects inflo.Ai to your Wordpress in a few easy steps
        <ol>
            <li>Make a note of the pin below.</li>
            <li>Click 'Connect to inflo.Ai'</li>
            <li>Login to inflo.Ai and Enter the pin when prompted</li>
        </ol>
        </p>
        <p>Your PIN is: <span class='inflo-pin'><?php echo $pin ?></span></p>
        <a class='button-primary' target='_new' href='<?php echo INFLOAI_APP ?>/connect/wordpress?wp=<?php echo $wp_b64 ?>&nonce=<?php echo $nonce ?>'>Connect to inflo.Ai</a>
        <p>
            <small>This pin is valid for <?php echo INFLOAI_PIN_EXPIRY / 60 ?> minutes, and expires at: <?php echo wp_date(get_option('time_format') . " - " . get_option("date_format"), $expires) ?></small>
        </p>
        <?php
    }

    function connect_render() {
        $auth_key = get_option("inflo_auth_key");
        $confirm = array_key_exists("confirm", $_GET);

        ?>
            <div class='wrap'>

            <h1><?php echo inflo_favicon('page-tools'); ?> inflo.Ai Connect</h1>
        <?php

        if (strlen($auth_key) == 0 || $confirm) {
            $this->render_do_connect();
        } else {
            $this->render_connect_confirm();
        }
        ?>
            </div>
        <?php
    }

    function disconnect_render() {
        $confirm = array_key_exists("confirm", $_GET);

        if ($confirm) {
            update_option('inflo_auth_key', "");
            update_option('inflo_connect_pin', '||');
            ?>
                <div class='wrap'>

                <h1><?php echo inflo_favicon('page-tools'); ?>Disconnected</h1>
                <p>inflo.Ai has been disconnected from Wordpress</p>
                <p>You will need to delete your current integration in inflo.Ai and reconnect if you wish to publish
                    posts from inflo.Ai to wordpress</p>
            </div>
            <?php
 
        } else {
            ?>
                <div class='wrap'>

                <h1><?php echo inflo_favicon('page-tools'); ?>Disconnect inflo.Ai</h1>
                <p>WARNING! Disconnecting inflo.Ai will prevent you from publishing posts via your integration on inflo.Ai.</p>
                <p>You will need to delete any current integrations in inflo.Ai and reconnect if you wish to publish
                    posts from inflo.Ai to wordpress</p>
                <a class='button-secondary inflo-button-danger' href='<?php echo $_SERVER["REQUEST_URI"] ?>&confirm=1'>I understand, Continue</a>

            </div>
            <?php
        } 
    }

    function connect_api_register() {
        register_rest_route('infloai/v1', '/connect/', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'connect_api_handler'),
            'permission_callback' => '__return_true' // Public endpoint, always allow
        ));
    }

    function connect_api_handler(WP_REST_REQUEST $request) {
        error_log("INFLOAI: CONNECT: New Request");

        $opts = explode('|', get_option('inflo_connect_pin'));

        $expiry = $opts[2];
        if ($expiry == '' || (time() - $expiry) > INFLOAI_PIN_EXPIRY) {
            error_log("INFLOAI: CONNECT: Failed expiry. Given: '" . $expiry . "' time(): '" . time() . "'");
            return new WP_Error('inflo_connect_expired', 'Expired', array(
                'status' => 410,
                'time' => time(),
                'expiry' => $expiry
            ));
        }

        $pin = $opts[0];
        if ($pin == '' || $pin != $request['pin']) {
            error_log("INFLOAI: CONNECT: Failed PIN. Given: '" . $request['pin'] . "'");
            return new WP_Error('inflo_connect_invalid_pin', 'Invalid Pin', array(
                'status' => 401,
                'time' => time()
            ));
        }

        $nonce = $opts[1];
        if ($nonce == '' || $nonce != $request['nonce']) {
            error_log("INFLOAI: CONNECT: Failed Nonce. Given: '" . $request['nonce'] . "' Expected: '" . $nonce . "'");
            return new WP_Error('inflo_connect_invalid_nonce', 'Invalid Nonce', array(
                'status' => 401,
                'time' => time(),
            ));
        }

        error_log("INFLOAI: CONNECT: Success");

        update_option('inflo_connect_pin', '||');

        $key = bin2hex(random_bytes(32));
        update_option('inflo_auth_key', $key);

        return array(
            "secret" => $key
        );

    }
}

?>
