<?php
if (!ossn_isAdminLoggedin()) {
    redirect('');
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$mode = kickoff_input('mode', 'seed');
$ok = false;

if ($mode === 'upload' && !empty($_FILES['teams_file']['tmp_name']) && is_uploaded_file($_FILES['teams_file']['tmp_name'])) {
    $ok = KickoffTournament::importUploadedTeams($tournament, $_FILES['teams_file']['tmp_name']);
} else {
    $ok = KickoffTournament::importSeedTeams($tournament);
}

if ($ok) {
    ossn_trigger_message(ossn_print('kickoff:admin:teams_imported'));
} else {
    ossn_trigger_message(ossn_print('kickoff:admin:teams_import_failed'), 'error');
}
redirect('kickoff/admin?tournament=' . urlencode($tournament));
