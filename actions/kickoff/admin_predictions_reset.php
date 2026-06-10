<?php
if (!ossn_isAdminLoggedin()) { redirect(''); }
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$confirm = trim((string)kickoff_input('confirm'));
if ($confirm !== 'RESET') {
    ossn_trigger_message(ossn_print('kickoff:admin:reset_confirm_error'), 'error');
    redirect('kickoff/admin?tournament=' . urlencode($tournament));
}
$ok = KickoffTournament::resetPredictions($tournament);
ossn_trigger_message($ok ? ossn_print('kickoff:admin:reset_done') : ossn_print('kickoff:error:save'), $ok ? 'success' : 'error');
redirect('kickoff/admin?tournament=' . urlencode($tournament));
