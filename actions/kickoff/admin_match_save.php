<?php
if (!ossn_isAdminLoggedin()) { redirect(''); }
$tournament = KickoffTournament::normalizeId(kickoff_input('tournament') ?: KICKOFF_TOURNAMENT_DEFAULT);
$matchId = trim((string)kickoff_input('match_id'));
$newId = trim((string)kickoff_input('match_new_id'));
$titleForId = trim((string)kickoff_input('title'));
if ($matchId === '') {
    $matchId = $newId !== '' ? KickoffTournament::slug($newId) : KickoffTournament::slug($titleForId . '-' . time());
}
if ($matchId === '' || $matchId === 'item') { ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error'); redirect('kickoff/admin?tournament=' . urlencode($tournament)); }
$updated = array(
    'id' => $matchId,
    'title' => trim((string)kickoff_input('title')),
    'home' => trim((string)kickoff_input('home')),
    'away' => trim((string)kickoff_input('away')),
    'stage' => trim((string)kickoff_input('stage')),
    'group' => trim((string)kickoff_input('group')),
    'location' => trim((string)kickoff_input('location')),
    'kickoff' => trim((string)kickoff_input('kickoff')),
);
$competitorsJson = trim((string)kickoff_input('competitors_json'));
if ($competitorsJson !== '') {
    $decoded = json_decode($competitorsJson, true);
    if (!is_array($decoded)) {
        ossn_trigger_message(ossn_print('kickoff:error:invalid'), 'error');
        redirect('kickoff/admin?tournament=' . urlencode($tournament));
    }
    $updated['competitors'] = $decoded;
}
$ok = KickoffTournament::updateMatch($tournament, $updated);
ossn_trigger_message($ok ? ossn_print('kickoff:admin:match_saved') : ossn_print('kickoff:error:save'), $ok ? 'success' : 'error');
redirect('kickoff/admin?tournament=' . urlencode($tournament));
