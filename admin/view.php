<?php
require_once 'auth.php';
require_once '../config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$s) {
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Admin view DB error: " . $e->getMessage());
    die('Database error. Please try again.');
}

$packageLabels = [
    'basic'   => 'Package 1 — Essential (MVR 5,999)',
    'premium' => 'Package 2 — Premium (MVR 14,999)',
    'luxury'  => 'Package 3/4 — Luxury (MVR 14,999 – 29,999)',
    'custom'  => 'Custom Package',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission #<?= $s['id'] ?> - Special Events Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f5f4f0; color: #333; }
        nav {
            background: #5a6348; color: #fff;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; height: 56px;
        }
        nav .brand { font-family: 'Playfair Display', serif; font-size: 1.2rem; }
        nav .nav-right { display: flex; align-items: center; gap: 20px; font-size: 0.85rem; }
        nav a { color: #d4dbc8; text-decoration: none; }
        nav a:hover { color: #fff; }

        main { max-width: 860px; margin: 32px auto; padding: 0 20px 60px; }

        .back-link { display: inline-block; margin-bottom: 20px; font-size: 0.875rem; color: #5a6348; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem; color: #5a6348; margin-bottom: 4px;
        }
        .meta { font-size: 0.8rem; color: #999; margin-bottom: 28px; }

        .section {
            background: #fff;
            border-radius: 10px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .section h2 {
            font-size: 0.95rem; font-weight: 600; color: #5a6348;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin-bottom: 18px; padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
        .field label { display: block; font-size: 0.75rem; color: #999; margin-bottom: 3px; }
        .field .value { font-size: 0.9rem; color: #222; word-break: break-word; }
        .field .value.empty { color: #bbb; font-style: italic; }
        .field.full { grid-column: 1 / -1; }

        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 500;
        }
        .badge-basic   { background: #e8f5e9; color: #388e3c; }
        .badge-premium { background: #e3f2fd; color: #1565c0; }
        .badge-luxury  { background: #fce4ec; color: #ad1457; }
        .badge-custom  { background: #fff3e0; color: #e65100; }

        .addon-list { list-style: none; display: flex; flex-wrap: wrap; gap: 8px; }
        .addon-list li {
            background: #f0f0eb; border-radius: 20px;
            padding: 4px 12px; font-size: 0.8rem; color: #555;
        }

        @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<nav>
    <span class="brand">Special Events &mdash; Admin</span>
    <div class="nav-right">
        <span><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
        <a href="logout.php">Sign Out</a>
    </div>
</nav>

<main>
    <a class="back-link" href="index.php">&larr; Back to Dashboard</a>

    <h1 class="page-title">
        <?= htmlspecialchars($s['client_name']) ?> &amp; <?= htmlspecialchars($s['partner_name']) ?>
    </h1>
    <p class="meta">Submission #<?= $s['id'] ?> &middot; Received on <?= date('d M Y, H:i', strtotime($s['submission_date'])) ?></p>

    <!-- Contact -->
    <div class="section">
        <h2>Contact Information</h2>
        <div class="grid">
            <div class="field">
                <label>Client Name</label>
                <div class="value"><?= htmlspecialchars($s['client_name']) ?></div>
            </div>
            <div class="field">
                <label>Partner Name</label>
                <div class="value"><?= htmlspecialchars($s['partner_name']) ?></div>
            </div>
            <div class="field">
                <label>Email</label>
                <div class="value"><a href="mailto:<?= htmlspecialchars($s['email']) ?>"><?= htmlspecialchars($s['email']) ?></a></div>
            </div>
            <div class="field">
                <label>Phone</label>
                <div class="value"><a href="tel:<?= htmlspecialchars($s['phone']) ?>"><?= htmlspecialchars($s['phone']) ?></a></div>
            </div>
            <div class="field">
                <label>Instagram</label>
                <div class="value <?= $s['instagram'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['instagram'] ?: 'Not provided') ?></div>
            </div>
            <div class="field">
                <label>ID / Passport</label>
                <div class="value <?= $s['client_id'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['client_id'] ?: 'Not provided') ?></div>
            </div>
        </div>
    </div>

    <!-- Event Details -->
    <div class="section">
        <h2>Event Details</h2>
        <div class="grid">
            <div class="field">
                <label>Wedding Date</label>
                <div class="value <?= $s['wedding_date'] ? '' : 'empty' ?>">
                    <?= $s['wedding_date'] ? date('d M Y', strtotime($s['wedding_date'])) : 'Not provided' ?>
                </div>
            </div>
            <div class="field">
                <label>Location / Venue</label>
                <div class="value <?= $s['location'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['location'] ?: 'Not provided') ?></div>
            </div>
            <div class="field">
                <label>Party Start Time</label>
                <div class="value <?= $s['party_start_time'] ? '' : 'empty' ?>">
                    <?= $s['party_start_time'] ? date('h:i A', strtotime($s['party_start_time'])) : 'Not provided' ?>
                </div>
            </div>
            <div class="field">
                <label>Party End Time</label>
                <div class="value <?= $s['party_end_time'] ? '' : 'empty' ?>">
                    <?= $s['party_end_time'] ? date('h:i A', strtotime($s['party_end_time'])) : 'Not provided' ?>
                </div>
            </div>
            <div class="field">
                <label>Guest Count</label>
                <div class="value <?= $s['guest_count'] ? '' : 'empty' ?>"><?= $s['guest_count'] ?: 'Not provided' ?></div>
            </div>
        </div>
    </div>

    <!-- Package & Cake -->
    <div class="section">
        <h2>Package &amp; Cake Table</h2>
        <div class="grid">
            <div class="field">
                <label>Decoration Package</label>
                <div class="value">
                    <?php
                    $pkg = $s['decor_package'];
                    $cls = in_array($pkg, ['basic','premium','luxury','custom']) ? "badge-$pkg" : 'badge-custom';
                    echo "<span class='badge $cls'>" . htmlspecialchars($packageLabels[$pkg] ?? ucfirst($pkg)) . "</span>";
                    ?>
                </div>
            </div>
            <div class="field">
                <label>Cake Table Type</label>
                <div class="value <?= $s['cake_table_type'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['cake_table_type'] ?: 'Not selected') ?></div>
            </div>
            <div class="field">
                <label>Cake Table Size</label>
                <div class="value <?= $s['cake_table_size'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['cake_table_size'] ?: 'Not selected') ?></div>
            </div>
            <?php if ($pkg === 'custom'): ?>
            <div class="field">
                <label>Estimated Budget</label>
                <div class="value"><?= $s['estimated_budget'] ? 'MVR ' . number_format($s['estimated_budget'], 2) : '—' ?></div>
            </div>
            <div class="field full">
                <label>Custom Requirements</label>
                <div class="value <?= $s['custom_requirements'] ? '' : 'empty' ?>"><?= nl2br(htmlspecialchars($s['custom_requirements'] ?: 'Not provided')) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add-ons -->
    <?php if (!empty($s['addons'])): ?>
    <div class="section">
        <h2>Add-ons</h2>
        <ul class="addon-list">
            <?php foreach (explode(', ', $s['addons']) as $addon): ?>
                <li><?= htmlspecialchars(trim($addon)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Vision -->
    <div class="section">
        <h2>Vision &amp; Additional Info</h2>
        <div class="grid">
            <div class="field">
                <label>Wedding / Decoration Colours</label>
                <div class="value <?= $s['wedding_colors'] ? '' : 'empty' ?>"><?= htmlspecialchars($s['wedding_colors'] ?: 'Not provided') ?></div>
            </div>
            <div class="field">
                <label>Media Consent</label>
                <div class="value"><?= htmlspecialchars($s['media_consent']) ?></div>
            </div>
            <div class="field full">
                <label>Ideas &amp; Vision</label>
                <div class="value <?= $s['ideas'] ? '' : 'empty' ?>"><?= nl2br(htmlspecialchars($s['ideas'] ?: 'Not provided')) ?></div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
