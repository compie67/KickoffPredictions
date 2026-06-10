<?php
class KickoffTournament {
    public static function tournamentFile($id) { return KickoffStorage::path('tournaments', $id, 'tournament.json'); }
    public static function teamsFile($id) { return KickoffStorage::path('tournaments', $id, 'teams.json'); }
    public static function matchesFile($id) { return KickoffStorage::path('tournaments', $id, 'matches.json'); }
    public static function resultsFile($id) { return KickoffStorage::path('scores', $id, 'results.json'); }
    public static function settingsFile($id) { return KickoffStorage::path('tournaments', $id, 'settings.json'); }

    public static function normalizeId($id) {
        $id = strtolower(trim((string)$id));
        $id = preg_replace('/[^a-z0-9_\-]/', '', $id);
        return $id ?: KICKOFF_TOURNAMENT_DEFAULT;
    }

    public static function exists($id) {
        $id = self::normalizeId($id);
        return file_exists(self::tournamentFile($id)) && file_exists(self::matchesFile($id));
    }

    public static function seedBase($id) {
        return dirname(__DIR__) . '/seed/' . self::normalizeId($id);
    }

    public static function availableSeeds() {
        $base = dirname(__DIR__) . '/seed';
        $rows = array();
        if (!is_dir($base)) {
            return $rows;
        }
        foreach (glob($base . '/*', GLOB_ONLYDIR) as $dir) {
            $id = basename($dir);
            $info = KickoffStorage::readJson($dir . '/tournament.json', array('id' => $id, 'title' => $id));
            $rows[] = array(
                'id' => isset($info['id']) ? $info['id'] : $id,
                'title' => isset($info['title']) ? $info['title'] : $id,
                'type' => isset($info['type']) ? $info['type'] : '',
                'prediction_type' => isset($info['prediction_type']) ? $info['prediction_type'] : 'score',
            );
        }
        usort($rows, function($a, $b){ return strcasecmp($a['title'], $b['title']); });
        return $rows;
    }

    public static function importedTournaments() {
        $base = KickoffStorage::path('tournaments');
        $rows = array();
        if (!is_dir($base)) {
            return $rows;
        }
        foreach (glob($base . '/*/tournament.json') as $file) {
            $id = basename(dirname($file));
            $info = KickoffStorage::readJson($file, array('id' => $id, 'title' => $id));
            $rows[] = array(
                'id' => isset($info['id']) ? $info['id'] : $id,
                'title' => isset($info['title']) ? $info['title'] : $id,
                'type' => isset($info['type']) ? $info['type'] : '',
                'prediction_type' => isset($info['prediction_type']) ? $info['prediction_type'] : 'score',
            );
        }
        usort($rows, function($a, $b){ return strcasecmp($a['title'], $b['title']); });
        return $rows;
    }

    public static function importSeed($id = KICKOFF_TOURNAMENT_DEFAULT) {
        $id = self::normalizeId($id);
        $seedBase = self::seedBase($id);
        if (!is_dir($seedBase)) {
            return false;
        }
        $ok = true;
        $ok = self::importSeedTournament($id) && $ok;
        $ok = self::importSeedTeams($id) && $ok;
        $ok = self::importSeedMatches($id) && $ok;
        $settings = $seedBase . '/settings.json';
        if (file_exists($settings)) {
            $ok = KickoffStorage::writeJson(self::settingsFile($id), KickoffStorage::readJson($settings, array())) && $ok;
        }
        if (!file_exists(self::resultsFile($id))) {
            KickoffStorage::writeJson(self::resultsFile($id), array('tournament' => $id, 'results' => array(), 'updated' => KickoffStorage::now()));
        }
        KickoffLeaderboard::recalculate($id);
        return $ok;
    }

    public static function importSeedTournament($id) {
        $id = self::normalizeId($id);
        $file = self::seedBase($id) . '/tournament.json';
        if (!file_exists($file)) {
            return false;
        }
        $data = KickoffStorage::readJson($file, array());
        if (empty($data['id'])) { $data['id'] = $id; }
        return KickoffStorage::writeJson(self::tournamentFile($id), $data);
    }

    public static function importSeedTeams($id) {
        $id = self::normalizeId($id);
        $file = self::seedBase($id) . '/teams.json';
        if (!file_exists($file)) {
            return false;
        }
        $ok = KickoffStorage::writeJson(self::teamsFile($id), KickoffStorage::readJson($file, array()));
        if ($ok) { self::syncPickWinnerCompetitors($id); KickoffLeaderboard::recalculate($id); }
        return $ok;
    }

    public static function importSeedMatches($id) {
        $id = self::normalizeId($id);
        $file = self::seedBase($id) . '/matches.json';
        if (!file_exists($file)) {
            return false;
        }
        $ok = KickoffStorage::writeJson(self::matchesFile($id), KickoffStorage::readJson($file, array()));
        if ($ok) { self::syncPickWinnerCompetitors($id); KickoffLeaderboard::recalculate($id); }
        return $ok;
    }

    public static function importUploadedTeams($id, $file) {
        $id = self::normalizeId($id);
        $data = self::uploadedFileToArray($file, 'teams');
        if (!$data) {
            return false;
        }
        $ok = KickoffStorage::writeJson(self::teamsFile($id), $data);
        if ($ok) { self::syncPickWinnerCompetitors($id); KickoffLeaderboard::recalculate($id); }
        return $ok;
    }

    public static function importUploadedMatches($id, $file) {
        $id = self::normalizeId($id);
        $data = self::uploadedFileToArray($file, 'matches');
        if (!$data) {
            return false;
        }
        $ok = KickoffStorage::writeJson(self::matchesFile($id), $data);
        if ($ok) { self::syncPickWinnerCompetitors($id); KickoffLeaderboard::recalculate($id); }
        return $ok;
    }

    protected static function uploadedFileToArray($file, $target) {
        if (empty($file) || !file_exists($file)) {
            return false;
        }
        $json = @file_get_contents($file);
        $trim = is_string($json) ? trim($json) : '';
        if ($trim !== '' && ($trim[0] === '[' || $trim[0] === '{')) {
            $data = json_decode($trim, true);
            if (is_array($data)) {
                if (isset($data[$target]) && is_array($data[$target])) {
                    return $data[$target];
                }
                if ($target === 'matches') {
                    $f1 = self::parseF1ScheduleJsonData($data);
                    if (!empty($f1)) {
                        return $f1;
                    }
                }
                if (self::isSequentialList($data)) {
                    return $data;
                }
            }
        }
        return ($target === 'teams') ? self::parseSportsDbCsvTeams($file) : self::parseSportsDbCsvMatches($file);
    }

    protected static function isSequentialList($array) {
        if (!is_array($array)) {
            return false;
        }
        $i = 0;
        foreach (array_keys($array) as $key) {
            if ($key !== $i) {
                return false;
            }
            $i++;
        }
        return true;
    }

    protected static function parseF1ScheduleJsonData(array $data) {
        $rows = self::f1RowsFromJsonData($data);
        if (empty($rows)) {
            return array();
        }
        $matches = array();
        foreach ($rows as $row) {
            $format = isset($row['event_format']) ? strtolower(trim((string)$row['event_format'])) : '';
            if ($format === 'testing') {
                continue;
            }
            $round = isset($row['round_number']) ? (int)$row['round_number'] : (count($matches) + 1);
            if ($round <= 0) {
                $round = count($matches) + 1;
            }
            $eventName = !empty($row['event_name']) ? trim((string)$row['event_name']) : (!empty($row['official_event_name']) ? trim((string)$row['official_event_name']) : 'Grand Prix');
            $location = !empty($row['location']) ? trim((string)$row['location']) : (!empty($row['country']) ? trim((string)$row['country']) : '');
            $offset = !empty($row['gmt_offset']) ? trim((string)$row['gmt_offset']) : '';
            if ($format === 'sprint_qualifying' && !empty($row['session3']) && strcasecmp(trim((string)$row['session3']), 'Sprint') === 0 && !empty($row['session3_date'])) {
                $matches[] = array(
                    'id' => sprintf('f1_2026_%02d_sprint', $round),
                    'title' => $eventName . ' Sprint',
                    'home' => '',
                    'away' => '',
                    'stage' => 'Sprint',
                    'group' => 'sprint',
                    'location' => $location,
                    'kickoff' => self::f1LocalDateToAmsterdam($row['session3_date'], $offset),
                    'source' => 'F1 schedule JSON',
                    'competitors' => array(),
                );
            }
            if (!empty($row['session5']) && strcasecmp(trim((string)$row['session5']), 'Race') === 0 && !empty($row['session5_date'])) {
                $matches[] = array(
                    'id' => sprintf('f1_2026_%02d_race', $round),
                    'title' => $eventName,
                    'home' => '',
                    'away' => '',
                    'stage' => 'Race',
                    'group' => 'race',
                    'location' => $location,
                    'kickoff' => self::f1LocalDateToAmsterdam($row['session5_date'], $offset),
                    'source' => 'F1 schedule JSON',
                    'competitors' => array(),
                );
            }
        }
        return $matches;
    }

    protected static function f1RowsFromJsonData(array $data) {
        if (self::isSequentialList($data)) {
            return $data;
        }
        if (isset($data['events']) && is_array($data['events'])) {
            return self::isSequentialList($data['events']) ? $data['events'] : self::columnObjectToRows($data['events']);
        }
        if (isset($data['event_name']) || isset($data['session5_date']) || isset($data['event_format'])) {
            return self::columnObjectToRows($data);
        }
        return array();
    }

    protected static function columnObjectToRows(array $data) {
        $indexes = array();
        foreach ($data as $column => $values) {
            if (!is_array($values)) { continue; }
            foreach ($values as $idx => $value) {
                $indexes[(string)$idx] = true;
            }
        }
        ksort($indexes, SORT_NATURAL);
        $rows = array();
        foreach (array_keys($indexes) as $idx) {
            $row = array();
            foreach ($data as $column => $values) {
                if (is_array($values) && array_key_exists($idx, $values)) {
                    $row[$column] = $values[$idx];
                }
            }
            if (!empty($row)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    protected static function f1LocalDateToAmsterdam($date, $offset = '') {
        $date = trim((string)$date);
        $offset = trim((string)$offset);
        if ($date === '') {
            return '';
        }
        try {
            if ($offset !== '' && preg_match('/^[+\-]\d{2}:\d{2}$/', $offset)) {
                $dt = new DateTime($date . $offset);
            } else {
                $dt = new DateTime($date, new DateTimeZone('UTC'));
            }
            $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            return $dt->format('Y-m-d\TH:i:sP');
        } catch (Exception $e) {
            return str_replace(' ', 'T', $date);
        }
    }

    protected static function parseSportsDbCsvRows($file) {
        $fh = @fopen($file, 'r');
        if (!$fh) {
            return array();
        }
        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            return array();
        }
        $header = array_map(function($v){ return trim((string)$v); }, $header);
        $rows = array();
        while (($row = fgetcsv($fh)) !== false) {
            $item = array();
            foreach ($header as $i => $key) {
                $item[$key] = isset($row[$i]) ? $row[$i] : '';
            }
            $rows[] = $item;
        }
        fclose($fh);
        return $rows;
    }

    protected static function parseSportsDbCsvTeams($file) {
        $rows = self::parseSportsDbCsvRows($file);
        $teams = array();
        foreach ($rows as $row) {
            foreach (array('Home Team', 'Away Team') as $field) {
                if (!empty($row[$field])) {
                    $rawName = trim($row[$field]);
                    $name = self::cleanDisplayName($rawName);
                    $id = self::slug($rawName);
                    if (!isset($teams[$id])) {
                        $teams[$id] = array(
                            'id' => $id,
                            'name' => $name,
                            'flag' => self::countryFlag($name),
                            'group' => '',
                        );
                    }
                }
            }
        }
        uasort($teams, function($a, $b){ return strcasecmp($a['name'], $b['name']); });
        return array_values($teams);
    }

    protected static function parseSportsDbCsvMatches($file) {
        $rows = self::parseSportsDbCsvRows($file);
        $matches = array();
        foreach ($rows as $index => $row) {
            $home = isset($row['Home Team']) ? trim($row['Home Team']) : '';
            $away = isset($row['Away Team']) ? trim($row['Away Team']) : '';
            if ($home === '' || $away === '') {
                continue;
            }
            $sourceId = !empty($row['idEvent']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $row['idEvent']) : sprintf('%03d', $index + 1);
            $kickoff = !empty($row['strTimestamp']) ? self::sportsDbTimestampToAmsterdam(trim($row['strTimestamp'])) : '';
            $matches[] = array(
                'id' => 'match_' . $sourceId,
                'stage' => 'group',
                'group' => !empty($row['Round']) ? trim($row['Round']) : '',
                'home' => self::slug($home),
                'away' => self::slug($away),
                'kickoff' => $kickoff,
                'source' => 'TheSportsDB CSV',
                'source_event_id' => $sourceId,
                'poster' => isset($row['Poster']) ? trim($row['Poster']) : '',
                'thumb' => isset($row['Thumb']) ? trim($row['Thumb']) : '',
            );
        }
        return $matches;
    }


    public static function sportsDbTimestampToAmsterdam($timestamp) {
        $timestamp = trim((string)$timestamp);
        if ($timestamp === '') {
            return '';
        }
        try {
            // TheSportsDB strTimestamp values are UTC timestamps without an explicit timezone.
            // Store the converted Europe/Amsterdam value so OSSN users see Dutch local time.
            $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            return $dt->format('Y-m-d\TH:i:sP');
        } catch (Exception $e) {
            return str_replace(' ', 'T', $timestamp);
        }
    }

    public static function slug($value) {
        $value = trim(strtolower((string)$value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }
        $value = str_replace('&', 'and', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');
        return $value ?: 'item';
    }

    public static function countryFlag($name) {
        $flags = array(
            'Algeria'=>'🇩🇿','Argentina'=>'🇦🇷','Australia'=>'🇦🇺','Austria'=>'🇦🇹','Belgium'=>'🇧🇪',
            'Bosnia-Herzegovina'=>'🇧🇦','Brazil'=>'🇧🇷','Canada'=>'🇨🇦','Cape Verde'=>'🇨🇻','Colombia'=>'🇨🇴',
            'Croatia'=>'🇭🇷','Curaçao'=>'🇨🇼','Czech Republic'=>'🇨🇿','DR Congo'=>'🇨🇩','Ecuador'=>'🇪🇨',
            'Egypt'=>'🇪🇬','England'=>'🇬🇧','France'=>'🇫🇷','Germany'=>'🇩🇪','Ghana'=>'🇬🇭',
            'Haiti'=>'🇭🇹','Iran'=>'🇮🇷','Iraq'=>'🇮🇶','Ivory Coast'=>'🇨🇮','Japan'=>'🇯🇵',
            'Jordan'=>'🇯🇴','Mexico'=>'🇲🇽','Morocco'=>'🇲🇦','Netherlands'=>'🇳🇱','New Zealand'=>'🇳🇿',
            'Norway'=>'🇳🇴','Panama'=>'🇵🇦','Paraguay'=>'🇵🇾','Poland'=>'🇵🇱','Portugal'=>'🇵🇹',
            'Qatar'=>'🇶🇦','Saudi Arabia'=>'🇸🇦','Scotland'=>'🇬🇧','Senegal'=>'🇸🇳','South Africa'=>'🇿🇦',
            'South Korea'=>'🇰🇷','Spain'=>'🇪🇸','Sweden'=>'🇸🇪','Switzerland'=>'🇨🇭','Tunisia'=>'🇹🇳',
            'Turkey'=>'🇹🇷','UAE'=>'🇦🇪','United Arab Emirates'=>'🇦🇪','USA'=>'🇺🇸','United States'=>'🇺🇸',
            'United Kingdom'=>'🇬🇧','Great Britain'=>'🇬🇧','Britain'=>'🇬🇧','Uruguay'=>'🇺🇾','Uzbekistan'=>'🇺🇿',
            'Monaco'=>'🇲🇨','Italy'=>'🇮🇹','Canada'=>'🇨🇦','New Zealand'=>'🇳🇿'
        );
        return isset($flags[$name]) ? $flags[$name] : '';
    }

    public static function flagFromCode($code) {
        $code = strtoupper(trim((string)$code));
        if ($code === '') {
            return '';
        }
        $aliases = array('UK' => 'GB', 'EN' => 'GB', 'SCO' => 'GB', 'NL' => 'NL');
        if (isset($aliases[$code])) {
            $code = $aliases[$code];
        }
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            return '';
        }
        $flag = '';
        for ($i = 0; $i < 2; $i++) {
            $flag .= html_entity_decode('&#' . (127397 + ord($code[$i])) . ';', ENT_NOQUOTES, 'UTF-8');
        }
        return $flag;
    }

    public static function normalizeFlag($flag, $country = '') {
        $flag = trim((string)$flag);
        $country = trim((string)$country);
        if ($flag !== '') {
            $fromCode = self::flagFromCode($flag);
            if ($fromCode !== '') {
                return $fromCode;
            }
            return $flag;
        }
        if ($country !== '') {
            $fromCode = self::flagFromCode($country);
            if ($fromCode !== '') {
                return $fromCode;
            }
            $fromName = self::countryFlag($country);
            if ($fromName !== '') {
                return $fromName;
            }
        }
        return '';
    }

    public static function displayFlag($flag, $country = '') {
        return self::normalizeFlag($flag, $country);
    }

    public static function get($id) { return KickoffStorage::readJson(self::tournamentFile(self::normalizeId($id)), array()); }
    public static function teams($id) { return KickoffStorage::readJson(self::teamsFile(self::normalizeId($id)), array()); }
    public static function matches($id) { return KickoffStorage::readJson(self::matchesFile(self::normalizeId($id)), array()); }
    public static function settings($id) { return array_merge(self::defaultSettings(), KickoffStorage::readJson(self::settingsFile(self::normalizeId($id)), array())); }
    public static function results($id) { return KickoffStorage::readJson(self::resultsFile(self::normalizeId($id)), array('results' => array())); }

    public static function predictionType($id) {
        $info = self::get($id);
        return !empty($info['prediction_type']) ? $info['prediction_type'] : 'score';
    }

    public static function defaultSettings() {
        return array(
            'points_exact' => 5,
            'points_result' => 3,
            'points_goal_diff' => 1,
            'points_pick_winner' => 5,
            'points_bonus_winner' => 10,
            'lock_minutes_before' => 0,
            'autosave_delay_ms' => 500
        );
    }


    public static function cleanDisplayName($name) {
        $name = trim((string)$name);
        // TheSportsDB often exports names like "MX Mexico". Keep the country name
        // for normal users and use the code only as hidden/source data.
        $name = preg_replace('/^[A-Z]{2,3}\s+(.+)$/u', '$1', $name);
        return trim($name);
    }

    public static function teamById($teams, $id) {
        foreach ($teams as $team) {
            if (isset($team['id']) && $team['id'] === $id) {
                return $team;
            }
        }
        return null;
    }

    public static function teamName($teams, $id) {
        $team = self::teamById($teams, $id);
        if ($team) {
            $name = isset($team['name']) ? self::cleanDisplayName($team['name']) : $id;
            $flag = self::displayFlag(isset($team['flag']) ? $team['flag'] : '', isset($team['country']) ? $team['country'] : '');
            return $flag !== '' ? ($flag . ' ' . $name) : $name;
        }
        return self::cleanDisplayName($id);
    }

    public static function teamPlainName($teams, $id) {
        $team = self::teamById($teams, $id);
        if ($team) {
            return isset($team['name']) ? self::cleanDisplayName($team['name']) : $id;
        }
        return self::cleanDisplayName($id);
    }

    public static function competitorName(array $match, $id) {
        if (isset($match['competitors']) && is_array($match['competitors'])) {
            foreach ($match['competitors'] as $item) {
                if (isset($item['id']) && $item['id'] === $id) {
                    $name = isset($item['name']) ? self::cleanDisplayName($item['name']) : $id;
                    $flag = self::displayFlag(isset($item['flag']) ? $item['flag'] : '', isset($item['country']) ? $item['country'] : '');
                    return $flag !== '' ? ($flag . ' ' . $name) : $name;
                }
            }
        }
        return self::cleanDisplayName($id);
    }

    public static function matchTitle(array $match, array $teams = array()) {
        if (!empty($match['home']) && !empty($match['away'])) {
            return self::teamName($teams, $match['home']) . ' vs ' . self::teamName($teams, $match['away']);
        }
        if (!empty($match['title'])) {
            return self::cleanDisplayName($match['title']);
        }
        return isset($match['id']) ? $match['id'] : 'event';
    }

    public static function updateTeam($tournament, array $updated) {
        $tournament = self::normalizeId($tournament);
        if (empty($updated['id'])) { return false; }
        $updated['flag'] = self::normalizeFlag(isset($updated['flag']) ? $updated['flag'] : '', isset($updated['country']) ? $updated['country'] : '');
        $teams = self::teams($tournament);
        $found = false;
        foreach ($teams as &$team) {
            if (isset($team['id']) && $team['id'] === $updated['id']) {
                foreach (array('name','flag','group','team','country') as $key) {
                    if (array_key_exists($key, $updated)) { $team[$key] = $updated[$key]; }
                }
                $found = true;
                break;
            }
        }
        unset($team);
        if (!$found) { $teams[] = $updated; }
        $ok = KickoffStorage::writeJson(self::teamsFile($tournament), $teams);
        if ($ok) {
            self::syncPickWinnerCompetitors($tournament);
            KickoffLeaderboard::recalculate($tournament);
        }
        return $ok;
    }

    public static function syncPickWinnerCompetitors($tournament) {
        $tournament = self::normalizeId($tournament);
        if (self::predictionType($tournament) !== 'pick_winner') {
            return true;
        }
        $teams = self::teams($tournament);
        $competitors = array();
        foreach ($teams as $team) {
            if (empty($team['id'])) { continue; }
            $competitors[] = array(
                'id' => $team['id'],
                'name' => isset($team['name']) ? $team['name'] : $team['id'],
                'flag' => self::normalizeFlag(isset($team['flag']) ? $team['flag'] : '', isset($team['country']) ? $team['country'] : ''),
                'team' => isset($team['team']) ? $team['team'] : '',
                'country' => isset($team['country']) ? $team['country'] : '',
            );
        }
        $matches = self::matches($tournament);
        foreach ($matches as &$match) {
            $match['competitors'] = $competitors;
        }
        unset($match);
        return KickoffStorage::writeJson(self::matchesFile($tournament), $matches);
    }

    public static function updateMatch($tournament, array $updated) {
        $tournament = self::normalizeId($tournament);
        if (empty($updated['id'])) { return false; }
        $matches = self::matches($tournament);
        foreach ($matches as &$match) {
            if (isset($match['id']) && $match['id'] === $updated['id']) {
                foreach (array('title','home','away','stage','group','location','kickoff','competitors') as $key) {
                    if (array_key_exists($key, $updated)) { $match[$key] = $updated[$key]; }
                }
                $ok = KickoffStorage::writeJson(self::matchesFile($tournament), $matches);
                if ($ok) { KickoffLeaderboard::recalculate($tournament); }
                return $ok;
            }
        }
        if (self::predictionType($tournament) === 'pick_winner' && empty($updated['competitors'])) {
            $updated['competitors'] = array();
            foreach (self::teams($tournament) as $team) {
                if (empty($team['id'])) { continue; }
                $updated['competitors'][] = array(
                    'id' => $team['id'],
                    'name' => isset($team['name']) ? $team['name'] : $team['id'],
                    'flag' => self::normalizeFlag(isset($team['flag']) ? $team['flag'] : '', isset($team['country']) ? $team['country'] : ''),
                    'team' => isset($team['team']) ? $team['team'] : '',
                    'country' => isset($team['country']) ? $team['country'] : '',
                );
            }
        }
        $matches[] = $updated;
        $ok = KickoffStorage::writeJson(self::matchesFile($tournament), $matches);
        if ($ok) { KickoffLeaderboard::recalculate($tournament); }
        return $ok;
    }

    public static function resetPredictions($tournament) {
        $tournament = self::normalizeId($tournament);
        KickoffStorage::deleteTree(KickoffStorage::path('predictions', $tournament));
        KickoffStorage::deleteTree(KickoffStorage::path('bonus', $tournament));
        return KickoffLeaderboard::recalculate($tournament);
    }

    public static function matchById($id, $matchId) {
        foreach (self::matches($id) as $match) {
            if (isset($match['id']) && $match['id'] === $matchId) {
                return $match;
            }
        }
        return null;
    }

    public static function isLocked(array $match, array $settings = array()) {
        if (empty($match['kickoff'])) {
            return false;
        }
        $settings = $settings ?: self::defaultSettings();
        $lockMinutes = isset($settings['lock_minutes_before']) ? (int)$settings['lock_minutes_before'] : 0;
        $kickoff = strtotime($match['kickoff']);
        if (!$kickoff) {
            return false;
        }
        return time() >= ($kickoff - ($lockMinutes * 60));
    }

    public static function resultFor($tournament, $matchId) {
        $data = self::results($tournament);
        if (!isset($data['results']) || !is_array($data['results'])) {
            return null;
        }
        return isset($data['results'][$matchId]) ? $data['results'][$matchId] : null;
    }

    public static function hasResult($tournament, $matchId) {
        $result = self::resultFor($tournament, $matchId);
        if (!$result || !is_array($result)) {
            return false;
        }
        if (isset($result['winner_id']) && trim((string)$result['winner_id']) !== '') {
            return true;
        }
        return isset($result['home_score']) && isset($result['away_score']) && $result['home_score'] !== '' && $result['away_score'] !== '';
    }

    public static function isPredictionClosed($tournament, array $match, array $settings = array()) {
        if (!empty($match['id']) && self::hasResult($tournament, $match['id'])) {
            return true;
        }
        return self::isLocked($match, $settings);
    }

    public static function saveResult($tournament, $matchId, $home, $away, $winnerId = '') {
        $tournament = self::normalizeId($tournament);
        $data = self::results($tournament);
        if (!isset($data['results']) || !is_array($data['results'])) {
            $data['results'] = array();
        }
        if ($winnerId !== '') {
            $data['results'][$matchId] = array(
                'winner_id' => $winnerId,
                'updated' => KickoffStorage::now(),
            );
        } else {
            $data['results'][$matchId] = array(
                'home_score' => $home,
                'away_score' => $away,
                'updated' => KickoffStorage::now(),
            );
        }
        $data['updated'] = KickoffStorage::now();
        $ok = KickoffStorage::writeJson(self::resultsFile($tournament), $data);
        if ($ok) {
            KickoffLeaderboard::recalculate($tournament);
        }
        return $ok;
    }
}
