<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function user(): ?array {
    static $loaded = false;
    static $current = null;
    if ($loaded) return $current;
    $loaded = true;
    if (!empty($_SESSION['user_id'])) {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $current = $stmt->fetch() ?: null;
    }
    return $current;
}


function avatar_url(?array $u): ?string {
    if (!$u || empty($u['avatar_path']) || empty($u['id'])) return null;
    // Avatars are served through avatar.php so the actual file can live in a
    // persistent folder outside the SIDEQUEST app directory. Replacing the
    // SIDEQUEST folder therefore does not delete users' profile pictures.
    return base_url('avatar.php?id='.(int)$u['id']);
}

function avatar_markup(array $u, string $class = 'avatar'): string {
    $initial = strtoupper(substr((string)($u['username'] ?? '?'), 0, 1));
    $src = avatar_url($u);
    if ($src) {
        $fallback = 'this.onerror=null;this.style.display="none";this.parentNode.classList.remove("has-avatar");this.parentNode.textContent='.json_encode($initial).';';
        return '<span class="'.e($class).' has-avatar"><img src="'.e($src).'" alt="'.e(($u['username'] ?? 'Player').' avatar').'" onerror="'.$fallback.'"></span>';
    }
    return '<span class="'.e($class).'">'.e($initial).'</span>';
}

function require_login(): void {
    if (!user()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void {
    require_login();
    if (user()['role'] !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}

function difficulty_xp(string $difficulty): int {
    return match (strtolower($difficulty)) {
        'easy' => 50,
        'normal', 'medium' => 100,
        'hard' => 175,
        'epic', 'special' => 300,
        default => 100,
    };
}

function level_from_xp(int $xp): int {
    $level = 1;
    $needed = 250;
    $total = 0;
    while ($xp >= $total + $needed && $level < 100) {
        $total += $needed;
        $level++;
        $needed = (int)round(250 * pow(1.16, $level - 1));
    }
    return $level;
}

function xp_bounds(int $xp): array {
    $level = level_from_xp($xp);
    $start = 0;
    for ($l = 1; $l < $level; $l++) {
        $start += (int)round(250 * pow(1.16, $l - 1));
    }
    $need = (int)round(250 * pow(1.16, $level - 1));
    return [$start, $start + $need];
}

function distance_km(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) return null;
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
    return $r * 2 * atan2(sqrt($a), sqrt(1-$a));
}

function quest_distance(array $quest): ?float {
    $u = user();
    if (!$u) return null;
    return distance_km(
        isset($u['latitude']) ? (float)$u['latitude'] : null,
        isset($u['longitude']) ? (float)$u['longitude'] : null,
        isset($quest['latitude']) ? (float)$quest['latitude'] : null,
        isset($quest['longitude']) ? (float)$quest['longitude'] : null
    );
}

function notify(int $userId, string $title, string $message, ?string $link = null): void {
    $stmt = db()->prepare("INSERT INTO notifications (user_id,title,message,link) VALUES (?,?,?,?)");
    $stmt->execute([$userId, $title, $message, $link]);
}

function setting(string $key, string $default = ''): string {
    $stmt = db()->prepare("SELECT setting_value FROM admin_settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function update_level_and_achievements(int $userId): void {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT xp, level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) return;

    $newLevel = level_from_xp((int)$u['xp']);
    if ($newLevel !== (int)$u['level']) {
        $pdo->prepare("UPDATE users SET level = ? WHERE id = ?")->execute([$newLevel, $userId]);
        notify($userId, 'LEVEL UP', "You reached Level {$newLevel}. Your quest capacity has increased.", 'dashboard.php');
    }

    $checks = [
        ['FIRST QUEST', "Completed your first SideQuest.", "SELECT COUNT(*) FROM quest_participants WHERE user_id=? AND status='completed'"],
        ['QUEST HUNTER', "Completed 10 quests.", "SELECT COUNT(*) FROM quest_participants WHERE user_id=? AND status='completed'"],
        ['ADVENTURER', "Completed 25 quests.", "SELECT COUNT(*) FROM quest_participants WHERE user_id=? AND status='completed'"],
        ['SIDEQUEST LEGEND', "Completed 100 quests.", "SELECT COUNT(*) FROM quest_participants WHERE user_id=? AND status='completed'"],
        ['QUEST CREATOR', "Created your first SideQuest.", "SELECT COUNT(*) FROM quests WHERE creator_id=?"],
        ['HIGH ROLLER', "Completed a paid SideQuest.", "SELECT COUNT(*) FROM quest_participants qp JOIN quests q ON q.id=qp.quest_id WHERE qp.user_id=? AND qp.status='completed' AND q.reward_amount>0"],
        ['LOCAL HERO', "Completed community-related quests.", "SELECT COUNT(*) FROM quest_participants qp JOIN quests q ON q.id=qp.quest_id JOIN categories c ON c.id=q.category_id WHERE qp.user_id=? AND qp.status='completed' AND c.slug='community'"],
        ['EXPLORER', "Completed quests across multiple categories.", "SELECT COUNT(DISTINCT q.category_id) FROM quest_participants qp JOIN quests q ON q.id=qp.quest_id WHERE qp.user_id=? AND qp.status='completed'"],
    ];

    foreach ($checks as $i => [$name, $desc, $sql]) {
        $a = $pdo->prepare("SELECT * FROM achievements WHERE name=?");
        $a->execute([$name]);
        $achievement = $a->fetch();
        if (!$achievement) continue;
        $q = $pdo->prepare($sql);
        $q->execute([$userId]);
        $count = (int)$q->fetchColumn();
        $threshold = match ($name) {
            'FIRST QUEST','QUEST CREATOR','HIGH ROLLER','LOCAL HERO' => 1,
            'QUEST HUNTER' => 10,
            'ADVENTURER' => 25,
            'SIDEQUEST LEGEND' => 100,
            'EXPLORER' => 3,
            default => 1
        };
        if ($count >= $threshold) {
            $ins = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id,achievement_id) VALUES (?,?)");
            $ins->execute([$userId, $achievement['id']]);
            if ($ins->rowCount() > 0) {
                notify($userId, 'BADGE UNLOCKED', 'You earned the “'.$name.'” achievement.', 'achievements.php');
            }
        }
    }
}


function finalize_participant_completion(PDO $pdo, int $questId, int $participantId): bool {
    // This is the single source of truth for awarding a participant's
    // completion. It is safe to call repeatedly because the reward ledger
    // prevents duplicate quest rewards.
    $stmt = $pdo->prepare("SELECT q.*, qp.status participant_status FROM quests q JOIN quest_participants qp ON qp.quest_id=q.id AND qp.user_id=? WHERE q.id=? FOR UPDATE");
    $stmt->execute([$participantId, $questId]);
    $q = $stmt->fetch();
    if (!$q || !in_array($q['participant_status'], ['accepted','in_progress','completed'], true)) {
        return false;
    }

    if ($q['participant_status'] !== 'completed') {
        $pdo->prepare("UPDATE quest_participants SET status='completed', completed_at=COALESCE(completed_at,NOW()) WHERE quest_id=? AND user_id=? AND status IN ('accepted','in_progress')")
            ->execute([$questId, $participantId]);
    }

    $completion = $pdo->prepare("SELECT id,status FROM quest_completions WHERE quest_id=? AND participant_id=? FOR UPDATE");
    $completion->execute([$questId, $participantId]);
    $row = $completion->fetch();

    if ($row && $row['status'] === 'confirmed') {
        return false;
    }

    if (!$row) {
        $pdo->prepare("INSERT INTO quest_completions (quest_id,participant_id,participant_confirmed_at,creator_confirmed_at,status) VALUES (?,?,NOW(),NOW(),'confirmed')")
            ->execute([$questId, $participantId]);
    } else {
        $pdo->prepare("UPDATE quest_completions SET participant_confirmed_at=COALESCE(participant_confirmed_at,NOW()), creator_confirmed_at=NOW(), status='confirmed' WHERE id=?")
            ->execute([(int)$row['id']]);
    }

    $already = $pdo->prepare("SELECT COUNT(*) FROM reward_transactions WHERE user_id=? AND quest_id=? AND transaction_type='quest_reward'");
    $already->execute([$participantId, $questId]);
    $hasReward = (int)$already->fetchColumn() > 0;

    if (!$hasReward) {
        $pdo->prepare("UPDATE users SET xp=xp+? WHERE id=?")->execute([(int)$q['xp_reward'], $participantId]);

        if ((float)$q['reward_amount'] > 0) {
            $pdo->prepare("INSERT INTO reward_transactions (user_id,quest_id,transaction_type,amount,note) VALUES (?,?,?,?,?)")
                ->execute([$participantId,$questId,'quest_reward',$q['reward_amount'],'Demo quest reward']);
            $feePercent = (float)setting('PLATFORM_FEE_PERCENT', '10');
            $poolShare = (float)setting('LEADERBOARD_POOL_SHARE', '70');
            $devShare = (float)setting('DEVELOPMENT_SHARE', '30');
            $fee = round((float)$q['reward_amount'] * ($feePercent / 100), 2);
            $pool = round($fee * ($poolShare / 100), 2);
            $dev = round($fee * ($devShare / 100), 2);
            $pdo->prepare("INSERT INTO reward_transactions (user_id,quest_id,transaction_type,amount,note) VALUES (?,?,?,?,?)")
                ->execute([$participantId,$questId,'platform_fee',$fee,'Configurable demo platform fee']);
            $pdo->prepare("INSERT INTO reward_transactions (user_id,quest_id,transaction_type,amount,note) VALUES (?,?,?,?,?)")
                ->execute([$participantId,$questId,'leaderboard_allocation',$pool,'Demo leaderboard pool allocation']);
            $pdo->prepare("INSERT INTO reward_transactions (user_id,quest_id,transaction_type,amount,note) VALUES (?,?,?,?,?)")
                ->execute([$participantId,$questId,'development_allocation',$dev,'Demo development allocation']);
        }
        update_level_and_achievements($participantId);
    }

    return true;
}

function active_quest_count(int $userId): int {
    // Capacity is based on genuinely active participations only. A stale
    // participant row must not consume a slot once its parent quest has been
    // completed or cancelled. Deleted quests are removed by the FK cascade.
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM quest_participants qp
        JOIN quests q ON q.id = qp.quest_id
        WHERE qp.user_id=?
          AND qp.status IN ('requested','accepted','in_progress')
          AND q.status NOT IN ('completed','cancelled')
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function quest_capacity(int $level): int {
    return max(1, min(10, $level));
}

function status_label(string $status): string {
    return ucwords(str_replace('_', ' ', $status));
}
