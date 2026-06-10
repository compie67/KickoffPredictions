<?php
/**
 * KickoffPredictions for OSSN
 * File based generic prediction game. Stores all module data in ossn_data/components/KickoffPredictions.
 */

define('KICKOFF_COMPONENT', 'KickoffPredictions');
define('KICKOFF_TOURNAMENT_DEFAULT', 'worldcup2026');

require_once __DIR__ . '/classes/KickoffStorage.php';
require_once __DIR__ . '/classes/KickoffTournament.php';
require_once __DIR__ . '/classes/KickoffPrediction.php';
require_once __DIR__ . '/classes/KickoffScoring.php';
require_once __DIR__ . '/classes/KickoffLeaderboard.php';

function kickoff_predictions_init() {
    ossn_register_page('kickoff', 'kickoff_page_handler');

    // Add the admin page to OSSN's normal Configure menu:
    // /administrator/component/KickoffPredictions
    ossn_register_com_panel('KickoffPredictions', 'settings');

    ossn_register_action('kickoff/prediction/save', __DIR__ . '/actions/kickoff/prediction_save.php');
    ossn_register_action('kickoff/bonus/save', __DIR__ . '/actions/kickoff/bonus_save.php');
    ossn_register_action('kickoff/admin/import', __DIR__ . '/actions/kickoff/admin_import.php');
    ossn_register_action('kickoff/admin/result/save', __DIR__ . '/actions/kickoff/admin_result_save.php');
    ossn_register_action('kickoff/admin/recalculate', __DIR__ . '/actions/kickoff/admin_recalculate.php');
    ossn_register_action('kickoff/admin/teams/import', __DIR__ . '/actions/kickoff/admin_teams_import.php');
    ossn_register_action('kickoff/admin/matches/import', __DIR__ . '/actions/kickoff/admin_matches_import.php');
    ossn_register_action('kickoff/admin/team/save', __DIR__ . '/actions/kickoff/admin_team_save.php');
    ossn_register_action('kickoff/admin/match/save', __DIR__ . '/actions/kickoff/admin_match_save.php');
    ossn_register_action('kickoff/admin/predictions/reset', __DIR__ . '/actions/kickoff/admin_predictions_reset.php');

    ossn_new_external_css('kickoff.css', ossn_site_url('kickoff/css'));
    ossn_new_external_js('kickoff.autosave', ossn_site_url('kickoff/js'));

    if (ossn_isLoggedin()) {
        // Frontend menu item in the left OSSN user menu, under Links.
        // Use ossn_register_menu_item() instead of a topbar/admin link so the
        // prediction pool appears where normal community links live.
        ossn_register_menu_item('newsfeed', array(
            'name'     => 'kickoff_predictions',
            'text'     => ossn_print('kickoff:menu'),
            'href'     => ossn_site_url(kickoff_public_menu_url()),
            'icon'     => 'fa fa-trophy',
            'parent'   => 'links',
            'priority' => 3,
            'title'    => ossn_print('kickoff:title'),
        ));
    }

    // No separate admin/sidemenu link here. ossn_register_com_panel() already places
    // the component under Configure. Adding a manual admin/sidemenu URL can become
    // /administrator/administrator/component/... on some OSSN installs.
}
ossn_register_callback('ossn', 'init', 'kickoff_predictions_init');

function kickoff_input($key, $default = null) {
    // OSSN versions differ: some have input(), but not kickoff_input().
    if (function_exists('input')) {
        $value = input($key);
        return ($value !== null && $value !== false) ? $value : $default;
    }
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}


function kickoff_first_imported_tournament($fallback = KICKOFF_TOURNAMENT_DEFAULT) {
    $imported = KickoffTournament::importedTournaments();
    if (!empty($imported) && !empty($imported[0]['id'])) {
        return KickoffTournament::normalizeId($imported[0]['id']);
    }
    return KickoffTournament::normalizeId($fallback);
}

function kickoff_resolve_tournament($requested = null) {
    $requested = KickoffTournament::normalizeId($requested ?: KICKOFF_TOURNAMENT_DEFAULT);
    if (KickoffTournament::exists($requested)) {
        return $requested;
    }
    return kickoff_first_imported_tournament($requested);
}

function kickoff_public_menu_url() {
    return 'kickoff/home?tournament=' . urlencode(kickoff_first_imported_tournament());
}

function kickoff_current_tournament() {
    return kickoff_resolve_tournament(kickoff_input('tournament', KICKOFF_TOURNAMENT_DEFAULT));
}

function kickoff_url($page = 'home', $tournament = null) {
    $tournament = KickoffTournament::normalizeId($tournament ?: kickoff_current_tournament());
    $path = ($page === 'home') ? 'kickoff/home' : 'kickoff/' . $page;
    return ossn_site_url($path . '?tournament=' . urlencode($tournament));
}

function kickoff_page_handler($pages) {
    $page = isset($pages[0]) ? $pages[0] : 'home';

    if ($page === 'css') {
        header('Content-Type: text/css; charset=utf-8');
        echo ossn_plugin_view('kickoff/css/style');
        return true;
    }
    if ($page === 'js') {
        header('Content-Type: application/javascript; charset=utf-8');
        echo ossn_plugin_view('kickoff/js/autosave');
        return true;
    }

    $public_pages = array('matches', 'leaderboard');
    $is_public_view = in_array($page, $public_pages, true) && !ossn_isLoggedin();
    if (!ossn_isLoggedin() && !$is_public_view) {
        ossn_error_page();
        return false;
    }

    ossn_load_external_css('kickoff.css');
    ossn_load_external_js('kickoff.autosave');

    $allowed = array('home', 'matches', 'leaderboard', 'bonus', 'admin');
    if (!in_array($page, $allowed, true)) {
        ossn_error_page();
        return false;
    }
    if ($page === 'admin' && !ossn_isAdminLoggedin()) {
        ossn_error_page();
        return false;
    }

    $params = array(
        'tournament' => kickoff_current_tournament(),
        'public_view' => $is_public_view,
    );

    $title = ossn_print('kickoff:title');
    $contents = '<style>' . ossn_plugin_view('kickoff/css/style') . '</style>' . ossn_plugin_view('kickoff/pages/' . $page, $params) . '<script>' . ossn_plugin_view('kickoff/js/autosave') . '</script>';
    $body = ossn_set_page_layout('contents', array(
        'content' => $contents,
    ));
    echo ossn_view_page($title, $body);
    return true;
}

function kickoff_action_json($ok, $message = '', array $extra = array()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array(
        'ok' => (bool) $ok,
        'message' => $message,
    ), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function kickoff_safe_int($value, $min = null, $max = null) {
    if ($value === '' || $value === null || !is_numeric($value)) {
        return null;
    }
    $int = (int) $value;
    if ($min !== null && $int < $min) {
        return null;
    }
    if ($max !== null && $int > $max) {
        return null;
    }
    return $int;
}
