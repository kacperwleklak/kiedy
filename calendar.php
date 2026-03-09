<?php
require_once 'config.php';
require_once 'helpers.php';

$calendar_id = $_GET['id'] ?? null;
if (!$calendar_id) {
    die("Calendar ID is required.");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM calendars WHERE id = ?");
$stmt->execute([$calendar_id]);
$calendar = $stmt->fetch();

if (!$calendar) {
    die("Calendar not found.");
}

// Get the total number of distinct voters for this calendar
$stmtTotalVoters = $pdo->prepare("
    SELECT COUNT(DISTINCT user_id) 
    FROM availabilities a
    JOIN calendar_days cd ON a.calendar_day_id = cd.id
    WHERE cd.calendar_id = ?
");
$stmtTotalVoters->execute([$calendar_id]);
$total_voters = (int)$stmtTotalVoters->fetchColumn();

$stmtDays = $pdo->prepare("SELECT * FROM calendar_days WHERE calendar_id = ? ORDER BY date_value");
$stmtDays->execute([$calendar_id]);
$days = $stmtDays->fetchAll();

$user_id = get_current_user_id($pdo);
$stmtUser = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch();
$has_name = !empty($currentUser['name']);
$is_verified = is_user_verified($pdo, $user_id);

$day_ids = array_column($days, 'id');
$availabilities = [];
if (!empty($day_ids)) {
    $placeholders = implode(',', array_fill(0, count($day_ids), '?'));
    $stmtAvail = $pdo->prepare("
        SELECT a.*, u.name as user_name 
        FROM availabilities a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.calendar_day_id IN ($placeholders)
    ");
    $stmtAvail->execute($day_ids);
    $availabilities = $stmtAvail->fetchAll();
}

// Generate time slots (1-hour chunks)
$start = strtotime($calendar['start_time']);
$end = strtotime($calendar['end_time']);
$time_slots = [];
for ($t = $start; $t < $end; $t += 3600) {
    $time_slots[] = date('H:i:s', $t);
}

// Data for JS
$grid_data = []; 
$user_slots = []; 
$voters = [];

foreach ($availabilities as $av) {
    if (!empty($av['user_name'])) {
        $display_name = $av['user_name'];
        if ($av['user_id'] === $user_id) {
            $display_name .= " (you)";
        }
        $voters[$av['user_id']] = $display_name;
    }
    
    if ($av['user_id'] === $user_id) {
        $user_slots[$av['calendar_day_id'] . '_' . $av['time_slot']] = $av['status'];
    } else {
        $grid_data[$av['calendar_day_id'] . '_' . $av['time_slot']][] = [
            'name' => $av['user_name'],
            'status' => $av['status'],
            'user_id' => $av['user_id']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($calendar['title']) ?> - Kiedy</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="background-orbs"><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div></div>

    <!-- Name Modal -->
    <div id="nameModal" class="modal <?= $has_name ? 'hidden' : '' ?>">
        <div class="modal-content glass-card enter-animation">
            <h2>Who are you?</h2>
            <p class="subtitle">Please enter your name to add your availability.</p>
            <input type="text" id="userNameInput" placeholder="Your Name" required>
            
            <?php if (!$is_verified): ?>
                <div class="cf-turnstile mt-4" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>"></div>
            <?php endif; ?>

            <button id="saveNameBtn" class="btn btn-primary w-100 mt-4">Save & Continue</button>
        </div>
    </div>

    <main class="container calendar-container glass-card enter-animation">
        <header class="calendar-header mb-4">
            <div>
                <h1 class="gradient-text"><?= htmlspecialchars($calendar['title']) ?></h1>
                <?php if (!empty($calendar['description'])): ?>
                    <p class="subtitle"><?= nl2br(htmlspecialchars($calendar['description'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <button id="toggleEditModeBtn" class="btn btn-primary">Set My Availability</button>
                <button id="saveAvailabilityBtn" class="btn btn-success hidden">Save</button>
                <button id="copyLinkBtn" class="btn btn-secondary">Copy Link</button>
            </div>
        </header>

        <div class="grid-controls mb-4">
            <div id="readOnlyLegend">
                <p class="hint-text text-center"><strong>Heatmap view:</strong> Darker green means more people are available.</p>
                <div class="legend">
                    <div class="legend-item"><span class="box bg-heatmap-0"></span> Nobody</div>
                    <div class="legend-item"><span class="box bg-heatmap-1"></span></div>
                    <div class="legend-item"><span class="box bg-heatmap-2"></span></div>
                    <div class="legend-item"><span class="box bg-heatmap-3"></span> Everyone</div>
                </div>
            </div>

            <div id="editModeLegend" class="hidden">
                <p class="hint-text text-center text-primary"><strong>Edit Mode:</strong> Left-click & drag to mark Available. Right-click & drag for Maybe.</p>
                <div class="legend">
                    <div class="legend-item"><span class="box bg-available"></span> Available</div>
                    <div class="legend-item"><span class="box bg-maybe"></span> Maybe</div>
                    <div class="legend-item"><span class="box"></span> Unavailable</div>
                </div>
            </div>

            <?php if (!empty($voters)): ?>
            <div class="voters-list mt-4 text-center">
                <p class="hint-text"><strong>Participants (Hover to see availability):</strong></p>
                <div class="voters-badges">
                    <?php foreach ($voters as $vid => $vname): ?>
                        <span class="voter-badge" data-vid="<?= htmlspecialchars($vid) ?>"><?= htmlspecialchars($vname) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="calendar-grid-wrapper">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th class="time-col"></th>
                        <?php foreach ($days as $day): ?>
                            <th>
                                <div class="day-header">
                                    <span class="day-name"><?= date('D', strtotime($day['date_value'])) ?></span>
                                    <span class="day-date"><?= date('M j', strtotime($day['date_value'])) ?></span>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $slot): ?>
                        <tr>
                            <td class="time-label"><?= date('H:i', strtotime($slot)) ?></td>
                            <?php foreach ($days as $day): 
                                $cell_id = $day['id'] . '_' . $slot;
                                $user_status = $user_slots[$cell_id] ?? '';
                                $others = $grid_data[$cell_id] ?? [];
                                
                                $cell_class = '';
                                $available_count = 0;
                                $maybe_count = 0;
                                
                                // Count all votes for this slot
                                $all_voters = array_merge($others, ($user_status ? [['status' => $user_status, 'user_id' => $user_id, 'name' => $currentUser['name'] ?? 'You']] : []));

                                foreach($all_voters as $o) {
                                    if ($o['status'] === 'available') $available_count++;
                                    if ($o['status'] === 'maybe') $maybe_count++;
                                }

                                // Heatmap class calculation (0 to 3 density scale)
                                // "Available" counts as 1. "Maybe" counts as 0.5.
                                $heatmap_class = 'heatmap-0';
                                if ($total_voters > 0) {
                                    $score = $available_count + ($maybe_count * 0.5);
                                    $ratio = $score / $total_voters;
                                    
                                    if ($ratio > 0.75) $heatmap_class = 'heatmap-3';
                                    elseif ($ratio >= 0.35) $heatmap_class = 'heatmap-2';
                                    elseif ($ratio > 0) $heatmap_class = 'heatmap-1';
                                }

                                if ($user_status === 'available') $cell_class = 'is-available';
                                else if ($user_status === 'maybe') $cell_class = 'is-maybe';
                                
                                $tooltip = '';
                                $available_vids = [];
                                $maybe_vids = [];
                                
                                $av_names = [];
                                $maybe_names = [];

                                foreach($all_voters as $o) {
                                    if ($o['status'] === 'available') {
                                        $available_count++;
                                        $av_names[] = $o['name'];
                                        $available_vids[] = $o['user_id'];
                                    }
                                    if ($o['status'] === 'maybe') {
                                        $maybe_count++;
                                        $maybe_names[] = $o['name'];
                                        $maybe_vids[] = $o['user_id'];
                                    }
                                }
                                
                                $tooltip_parts = [];
                                if(count($av_names) > 0) {
                                    $tooltip_parts[] = "Available: " . implode(", ", $av_names);
                                }
                                if(count($maybe_names) > 0) {
                                    $tooltip_parts[] = "Maybe: " . implode(", ", $maybe_names);
                                }
                                if (count($tooltip_parts) > 0) {
                                    $tooltip = implode(" | ", $tooltip_parts);
                                }
                            ?>
                                <td class="grid-cell <?= htmlspecialchars($heatmap_class) ?> user-<?= htmlspecialchars($user_status) ?>" 
                                    data-day-id="<?= htmlspecialchars($day['id']) ?>" 
                                    data-time="<?= htmlspecialchars($slot) ?>"
                                    data-available-vids='<?= json_encode($available_vids) ?>'
                                    data-maybe-vids='<?= json_encode($maybe_vids) ?>'
                                    data-user-status="<?= htmlspecialchars($user_status) ?>"
                                    title="<?= htmlspecialchars($tooltip) ?>">
                                    <div class="cell-content"></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        const HAS_NAME = <?= $has_name ? 'true' : 'false' ?>;
        const IS_VERIFIED = <?= $is_verified ? 'true' : 'false' ?>;
    </script>
    <script src="js/calendar.js"></script>
    <script src="js/cookie-notice.js"></script>
</body>
</html>
