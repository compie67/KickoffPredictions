<?php
if (!ossn_isAdminLoggedin()) { redirect(''); }
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$id = trim((string)kickoff_input('team_id'));
$newId = trim((string)kickoff_input('team_new_id'));
$nameForId = trim((string)kickoff_input('name'));
if ($id === '') {
    $id = $newId !== '' ? KickoffTournament::slug($newId) : KickoffTournament::slug($nameForId);
}
if ($id === '' || $id === 'item') { ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error'); redirect('kickoff/admin?tournament=' . urlencode($tournament)); }
$updated = array(
    'id' => $id,
    'name' => trim((string)kickoff_input('name')),
    'flag' => trim((string)kickoff_input('flag')),
    'group' => trim((string)kickoff_input('group')),
    'team' => trim((string)kickoff_input('team')),
    'country' => trim((string)kickoff_input('country')),
);
$ok = KickoffTournament::updateTeam($tournament, $updated);
ossn_trigger_message($ok ? ossn_print('kickoff:admin:team_saved') : ossn_print('kickoff:error:save'), $ok ? 'success' : 'error');
redirect('kickoff/admin?tournament=' . urlencode($tournament));
