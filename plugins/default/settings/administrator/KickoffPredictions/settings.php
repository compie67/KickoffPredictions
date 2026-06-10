<?php
/**
 * Admin configure panel for KickoffPredictions.
 * Available at /administrator/component/KickoffPredictions after the component is enabled.
 */
if(!ossn_isAdminLoggedin()){
    ossn_error_page();
    return;
}

$tournament = kickoff_current_tournament();

echo '<style>' . ossn_plugin_view('kickoff/css/style') . '</style>';
echo ossn_plugin_view('kickoff/pages/admin', array(
    'tournament' => $tournament,
));
