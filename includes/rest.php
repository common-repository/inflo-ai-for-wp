<?php

class InfloAI_RestFields {
    private $infloai_db;

    function __construct() {
        $this->infloai_db = new InfloAI_Db;

        add_action('rest_api_init', array($this, 'rest_api_init'));
        add_action('rest_api_init', array($this, 'version_api_register'));
    }

    function rest_api_init() {
        register_rest_field(
            'post', 'infloai',
            array(
                'get_callback'    => array($this, 'get_inflo_post_id'),
                'update_callback' => array($this, 'set_inflo_post_id'),
                'schema'          => array(
                    'description' => __('inflo.Ai post data'),
                    'type' => 'object',
                ),
            )
        );
    }

    function get_inflo_post_id($object, $field_name, $request) {
        $meta = get_post_meta($object['id'], $field_name);
        if (current_user_can('edit_posts') && !empty($meta)) {
            return get_post_meta($object['id'], $field_name)[0];
        } else {
            return null;
        }
    }

    function set_inflo_post_id($value, $object, $field_name) {
        update_post_meta($object->ID, $field_name, $value);

        $image_url = $value['featured_image_url'];
        $inflo_post_id = $value['post'];

        if ($image_url) {
            $this->attach_featured_image(
                $image_url, $object, $field_name, $inflo_post_id
            );
        } else {
            $this->detach_featured_image($object->ID);
        }
    }

    function attach_featured_image($image_url, $post, $field_name, $inflo_post_id) {
        $image_ext = 'png';
        $file_name = sprintf(
            '%s_%s_%s.%s', $field_name, $inflo_post_id, md5($image_url), $image_ext
        );

        if (!$this->should_update_featured_image($post->ID, $file_name)) {
            return;
        }

        $file_contents = $this->get_file_from_url($image_url);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $file_name;

        if (is_writable($upload_dir['path'])
            && !$upload_dir['error']
            && $file_contents
            && $this->upload_file_to_wp($file_contents, $file_path)
        ) {
            $attach = $this->generate_attachment_from_file($file_name);
            $attach_id = wp_insert_attachment($attach, $file_path, $post->ID);

            $image_post = get_post($attach_id);
            $image_post_path = get_attached_file($image_post->ID);

            if (!function_exists('wp_generate_attachment_metadata')) {
                include(ABSPATH . 'wp-admin/includes/image.php');
            }
            $attach_data = wp_generate_attachment_metadata(
                $attach_id, $image_post_path
            );

            wp_update_attachment_metadata($attach_id, $attach_data);
            set_post_thumbnail($post->ID, $attach_id);
        }
    }

    function detach_featured_image($post_id) {
        if (has_post_thumbnail($post_id)) {
            delete_post_thumbnail($post_id);
        }
    }

    function should_update_featured_image($post_id, $file_name) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $attached_file_name = basename(get_attached_file($thumbnail_id));

        return !$attached_file_name || $attached_file_name !== $file_name;
    }

    function generate_attachment_from_file($file_name) {
        $wp_filetype = wp_check_filetype(basename($file_name), null);

        return array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $file_name,
            'post_content' => '',
            'post_status' => 'inherit'
        );
    }

    function get_file_from_url($url) {
        $resp = wp_remote_get($url);

        if (!is_array($resp) || is_wp_error($resp)) {
            return false;
        }

        $resp_code = $resp['response']['code'];

        if (!$resp_code
            || $resp_code < 200
            || $resp_code > 299
            || $resp['headers']['content-length'] == 0
            || !$resp['body']
        ) {
            return false;
        }

        return $resp['body'];
    }

    function upload_file_to_wp($file_contents, $path) {
        $file = fopen($path, 'w');
        fwrite($file, $file_contents);
        fclose($file);

        return file_exists($path);
    }

    function version_api_register() {
        register_rest_route('infloai/v1', '/info/', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'info_api_handler'),
            'permission_callback' => function() {
                // Instead of relying on the WP auth framework, re-use
                // the existing code.
                return InfloAI_RestAuth::validate_auth_headers() === true;
            }
        ));

        register_rest_route('infloai/v1', '/debug/', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'debug_api_handler'),
        ));

    }

    function info_api_handler() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $meta = $all_plugins['inflo-ai-for-wp/infloai.php'];

        $user = wp_get_current_user();

        return array(
            "version" => $meta['Version'],
            "current_user" => array(
                "id" => $user->ID,
            ),
            "time" => time()
        );
    }

    function debug_api_handler() {
        return array(
            "time" => time()
        );
    }

}

?>
