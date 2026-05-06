<?php
// Navigation hook for local_golden – inserts a link under Site admin → Reports.

defined('MOODLE_INTERNAL') || die();

/**
 * Insert a reports-tree node for the GOLDEN map.
 *
 * @param navigation_node $navigation
 */
function local_golden_extend_navigation(global_navigation $navigation) {
    // No-op: we expose the page through the admin/reports tree below.
}

/**
 * Add a node under "Site administration → Reports".
 *
 * @param navigation_node $navigation
 */
function local_golden_extend_navigation_frontpage(navigation_node $navigation) {
    if (has_capability('local/golden:view', context_system::instance())) {
        $url = new moodle_url('/local/golden/index.php');
        $navigation->add(
            get_string('nav_golden', 'local_golden'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_golden',
            new pix_icon('i/marker', '')
        );
    }
}
