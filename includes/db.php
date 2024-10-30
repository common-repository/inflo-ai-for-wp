<?php

class InfloAI_Db {

    private $posts;
    private $postmeta;
    private $wpdb;
    private $author;

    function __construct() {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->posts = $wpdb->prefix . 'posts';
        $this->postmeta = $wpdb->prefix . 'postmeta';
    }

}
