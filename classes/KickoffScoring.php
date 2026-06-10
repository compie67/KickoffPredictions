<?php
class KickoffScoring {
    public static function matchPoints(array $prediction, array $result, array $settings) {
        if (isset($prediction['winner_id'], $result['winner_id'])) {
            return ($prediction['winner_id'] !== '' && $prediction['winner_id'] === $result['winner_id']) ? (int)$settings['points_pick_winner'] : 0;
        }
        if (!isset($prediction['home_score'], $prediction['away_score'], $result['home_score'], $result['away_score'])) {
            return 0;
        }
        $ph = (int)$prediction['home_score'];
        $pa = (int)$prediction['away_score'];
        $rh = (int)$result['home_score'];
        $ra = (int)$result['away_score'];

        if ($ph === $rh && $pa === $ra) {
            return (int)$settings['points_exact'];
        }
        $points = 0;
        $predResult = self::outcome($ph, $pa);
        $realResult = self::outcome($rh, $ra);
        if ($predResult === $realResult) {
            $points += (int)$settings['points_result'];
        }
        if (($ph - $pa) === ($rh - $ra)) {
            $points += (int)$settings['points_goal_diff'];
        }
        return $points;
    }

    public static function isExact(array $prediction, array $result, array $settings) {
        if (isset($prediction['winner_id'], $result['winner_id'])) {
            return $prediction['winner_id'] !== '' && $prediction['winner_id'] === $result['winner_id'];
        }
        return isset($prediction['home_score'], $prediction['away_score'], $result['home_score'], $result['away_score'])
            && (int)$prediction['home_score'] === (int)$result['home_score']
            && (int)$prediction['away_score'] === (int)$result['away_score'];
    }

    public static function outcome($home, $away) {
        if ($home > $away) return 'home';
        if ($home < $away) return 'away';
        return 'draw';
    }
}
