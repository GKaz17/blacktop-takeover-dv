<?php

function competitionMinimumRoster(string $format): int
{
    return $format === '5v5' ? 5 : 3;
}

function competitionTeamEligibility(mysqli $conn, int $tournamentId, int $teamId): ?array
{
    $query = $conn->prepare(
        "SELECT tournament.format, tournament.max_roster, team.name,
                COUNT(DISTINCT CASE WHEN member.status = 'active' THEN member.user_id END) AS active_roster,
                MAX(CASE WHEN member.user_id = team.captain_id AND member.status = 'active' THEN 1 ELSE 0 END) AS captain_active,
                MAX(CASE WHEN member.squad_role = 'vice_captain' AND member.status = 'active' THEN 1 ELSE 0 END) AS vice_captain_active
         FROM tournaments tournament
         JOIN teams team ON team.id = ?
         LEFT JOIN team_members member ON member.team_id = team.id
         WHERE tournament.id = ?
         GROUP BY tournament.id, team.id"
    );
    $query->bind_param('ii', $teamId, $tournamentId);
    $query->execute();
    $state = $query->get_result()->fetch_assoc();

    if (!$state) {
        return null;
    }

    $activeRoster = (int) $state['active_roster'];
    $maximumRoster = (int) $state['max_roster'];
    $minimumRoster = competitionMinimumRoster($state['format']);
    $leaderAvailable = (bool) $state['captain_active'] || (bool) $state['vice_captain_active'];
    $reason = '';

    if ($activeRoster < $minimumRoster) {
        $reason = $state['name'] . ' has ' . $activeRoster . ' active player' . ($activeRoster === 1 ? '' : 's')
            . '; ' . strtoupper($state['format']) . ' requires at least ' . $minimumRoster . '.';
    } elseif ($activeRoster > $maximumRoster) {
        $reason = $state['name'] . ' has ' . $activeRoster . ' active players; this event allows a maximum of ' . $maximumRoster . '.';
    } elseif (!$leaderAvailable) {
        $reason = $state['name'] . ' needs an active captain or vice captain before it can compete.';
    }

    return [
        'eligible' => $reason === '',
        'reason' => $reason,
        'team_name' => $state['name'],
        'format' => $state['format'],
        'active_roster' => $activeRoster,
        'minimum_roster' => $minimumRoster,
        'maximum_roster' => $maximumRoster,
        'leader_available' => $leaderAvailable,
    ];
}
