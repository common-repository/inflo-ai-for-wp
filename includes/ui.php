<?php

class InfloAI_Core {
    function __construct() {
        $this->infloai_db = new InfloAI_Db;

        add_action('admin_init', array($this, 'admin_init'));
        add_action('manage_posts_custom_column', array($this, 'add_column_data'), 10, 2);
        add_filter('manage_posts_columns', array($this, 'add_column'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'), 10, 2);
        add_action('admin_menu', array($this, 'menu_item'));
    }

    function admin_init() {
        wp_register_style(
            'infloai_plugin_css',
            plugins_url('infloai.css', realpath(dirname(__FILE__) . '/../infloai.php'))
        );
        wp_enqueue_style('infloai_plugin_css');
    }

    function add_column($columns) {
        $infloai_columns = array('infloai' => inflo_favicon("post-list"));
        return $columns + $infloai_columns;
    }

    function add_column_data($column, $post_id) {
        switch ($column) {
        case 'infloai':
            $meta = get_post_meta($post_id, 'infloai');
            if ($meta) {
                $inflo_post_url = get_inflo_post_url($meta[0]['post']);

                ?>
                    <a href='<?php echo $inflo_post_url;?>' target='_blank'>
                        <?php echo inflo_favicon("post-list") ?>
                    </a>
                <?php
            }
        }
    }

    // Post Meta-Box

    function meta_box_html($post){
        $meta = get_post_meta($post->ID, 'infloai');
        $inflo_post_url = get_inflo_post_url($meta[0]['post']);

        if ($meta) {
            ?>
                <p>
                    This post was created using inflo.Ai
                    &mdash;
                    <a href='<?php echo $inflo_post_url;?>' target='_blank'>view post there<a/>.
                </p>
            <?php
        }
    }

    function add_meta_box($type, $post) {
        if (get_post_meta($post->ID, 'infloai')) {
            add_meta_box(
                'infloai_meta_box',
                inflo_favicon('post-editor') . ' inflo.Ai',
                array($this, 'meta_box_html'),
                "post"
            );
        }
    }

    function menu_item() {
        add_menu_page(
            "InfloAI",
            'Open inflo.Ai',
            "manage_options",
            INFLOAI_APP . "/posts/",
            "",
            plugins_url('logo-gray-16x16.png', realpath(dirname(__FILE__) . '/../infloai.php')),
            6
        );
    }

}

?>
