<?php
if (!ossn_isAdminLoggedin()) {
    redirect('');
}
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
if (KickoffTournament::importSeed($tournament)) {
    ossn_trigger_message(ossn_print('kickoff:admin:imported'));
} else {
    ossn_trigger_message(ossn_print('kickoff:admin:import_failed'), 'error');
}
redirect('kickoff/admin?tournament=' . urlencode($tournament));
