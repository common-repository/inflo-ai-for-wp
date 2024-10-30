<?php
/**
 * Plugin Name: inflo.Ai for WP
 * Description: Integrate WordPress with inflo.Ai
 * Author: inflo.Ai
 * Author URI: https://inflo.ai
 * Version: 1.8
 * Plugin URI: https://inflo.ai/wordpress
 */


// CSS

define('INFLOAI_PATH', plugin_dir_path(__FILE__));
define('INFLOAI_APP', "https://app.inflo.ai");
define('INFLOAI_PIN_EXPIRY', 300);

require_once INFLOAI_PATH . 'includes/db.php';
require_once INFLOAI_PATH . "includes/utils.php";

// Disabled until we can push versions to WP.org
// require_once INFLOAI_PATH . 'includes/hooks.php';

require_once INFLOAI_PATH . "includes/ui.php";
new InfloAI_Core;

require_once INFLOAI_PATH . 'includes/rest.php';
new InfloAI_RestFields;

require_once INFLOAI_PATH . 'includes/settings.php';
new InfloAI_Settings;

require_once INFLOAI_PATH . 'includes/auth.php';
new InfloAI_RestAuth;

require_once INFLOAI_PATH . 'includes/debug.php';
new InfloAI_Debug;

?>
