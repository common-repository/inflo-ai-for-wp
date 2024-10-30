<?php

function inflo_favicon($c="") {
    if ($c) {
        $c = "inflo-favicon-" . $c;
    }

    return "<span class='inflo-favicon " . $c . "'></span>";
}

function get_inflo_post_url($inflo_post_id) {
    return INFLOAI_APP . "/posts/publications/" . $inflo_post_id;
}

?>
