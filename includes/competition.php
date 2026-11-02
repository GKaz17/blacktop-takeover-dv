<?php

const COMPETITION_WIN_POINTS = 2;
const COMPETITION_LOSS_POINTS = 1;

function competitionTimezone(): DateTimeZone
{
    static $timezone = null;

    if (!$timezone instanceof DateTimeZone) {
        $timezone = new DateTimeZone('Africa/Johannesburg');
    }

    return $timezone;
}

function competitionDateTime(?string $value): ?DateTimeImmutable
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, competitionTimezone());
    $errors = DateTimeImmutable::getLastErrors();

    if ($dateTime && (!is_array($errors) || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
        return $dateTime;
    }

    return null;
}

function competitionRegistrationState(array $event, ?DateTimeImmutable $now = null): array
{
    $deadline = competitionDateTime($event['registration_deadline'] ?? null);
    $now ??= new DateTimeImmutable('now', competitionTimezone());

    if (($event['status'] ?? '') !== 'open') {
        return [
            'open' => false,
            'deadline' => $deadline,
            'message' => 'Registration is not open for this event.',
        ];
    }

    if (!$deadline) {
        return [
            'open' => false,
            'deadline' => null,
            'message' => 'Registration is unavailable until the organiser confirms a deadline.',
        ];
    }

    if ($now >= $deadline) {
        return [
            'open' => false,
            'deadline' => $deadline,
            'message' => 'Registration closed on ' . $deadline->format('d M Y') . ' at ' . $deadline->format('H:i') . '.',
        ];
    }

    return [
        'open' => true,
        'deadline' => $deadline,
        'message' => 'Registration closes on ' . $deadline->format('d M Y') . ' at ' . $deadline->format('H:i') . '.',
    ];
}

function competitionCalculateStandings(array $matches): array
{
    $standingsByTeam = [];
    $unresolvedTies = 0;

    foreach ($matches as $match) {
        $homeTeam = trim((string) ($match['home_team'] ?? ''));
        $awayTeam = trim((string) ($match['away_team'] ?? ''));

        if ($homeTeam === '' || $awayTeam === '') {
            continue;
        }

        foreach ([$homeTeam, $awayTeam] as $teamName) {
            $standingsByTeam[$teamName] ??= [
                'team' => $teamName,
                'played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'point_difference' => 0,
                'points' => 0,
            ];
        }

        if (($match['status'] ?? '') !== 'final' || ($match['home_score'] ?? null) === null || ($match['away_score'] ?? null) === null) {
            continue;
        }

        $homeScore = (int) $match['home_score'];
        $awayScore = (int) $match['away_score'];

        if ($homeScore === $awayScore) {
            $unresolvedTies++;
            continue;
        }

        $standingsByTeam[$homeTeam]['played']++;
        $standingsByTeam[$awayTeam]['played']++;
        $standingsByTeam[$homeTeam]['points_for'] += $homeScore;
        $standingsByTeam[$homeTeam]['points_against'] += $awayScore;
        $standingsByTeam[$awayTeam]['points_for'] += $awayScore;
        $standingsByTeam[$awayTeam]['points_against'] += $homeScore;

        if ($homeScore > $awayScore) {
            $standingsByTeam[$homeTeam]['wins']++;
            $standingsByTeam[$homeTeam]['points'] += COMPETITION_WIN_POINTS;
            $standingsByTeam[$awayTeam]['losses']++;
            $standingsByTeam[$awayTeam]['points'] += COMPETITION_LOSS_POINTS;
        } else {
            $standingsByTeam[$awayTeam]['wins']++;
            $standingsByTeam[$awayTeam]['points'] += COMPETITION_WIN_POINTS;
            $standingsByTeam[$homeTeam]['losses']++;
            $standingsByTeam[$homeTeam]['points'] += COMPETITION_LOSS_POINTS;
        }
    }

    $standings = array_values($standingsByTeam);
    foreach ($standings as &$standing) {
        $standing['point_difference'] = $standing['points_for'] - $standing['points_against'];
    }
    unset($standing);

    usort($standings, static function (array $a, array $b): int {
        return ($b['points'] <=> $a['points'])
            ?: ($b['point_difference'] <=> $a['point_difference'])
            ?: ($b['points_for'] <=> $a['points_for'])
            ?: strcasecmp($a['team'], $b['team']);
    });

    foreach ($standings as $index => &$standing) {
        $standing['position'] = $index + 1;
    }
    unset($standing);

    return [
        'standings' => $standings,
        'unresolved_ties' => $unresolvedTies,
    ];
}

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
