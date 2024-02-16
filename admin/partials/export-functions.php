<?php

function postnlecs_ece_update($post_id, $post, $update)
{
    $post_type = get_post_type($post_id);
    $post_status = get_post_status($post_id);

    if ($post_type != "product") {
        return;
    }

    if ($post_status != "publish") {
        return;
    }

    if (isset($_POST["ecsExport"])) {
        update_post_meta($post_id, "ecsExport", 10, 3);
    }
}

add_action("save_post", "postnlecs_ece_update", 10, 3);