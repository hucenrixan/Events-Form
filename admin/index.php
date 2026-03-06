<?php
require_once 'auth.php';
require_once '../config.php';

// Filtering & search
$search  = trim($_GET['search'] ?? '');
$package = trim($_GET['package'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build WHERE clause
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(client_name LIKE ? OR partner_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like, $like]);
    }
    if ($package !== '') {
        $where[]  = "decor_package = ?";
        $params[] = $package;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM submissions $whereSQL");
    $countStmt->execute($params);
    $total     = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    // Fetch page
    $stmt = $pdo->prepare(
        "SELECT id, submission_date, client_name, partner_name, email, phone, wedding_date, location, decor_package
         FROM submissions $whereSQL
         ORDER BY submission_date DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin index DB error: " . $e->getMessage());
    $submissions = [];
    $total       = 0;
    $totalPages  = 1;
    $dbError     = 'Could not load submissions from the database.';
}

$packageLabels = [
    'basic'   => 'Package 1',
    'premium' => 'Package 2',
    'luxury'  => 'Package 3 / 4',
    'custom'  => 'Custom',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Special Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f5f4f0; color: #333; }

        /* Top nav */
        nav {
            background: #5a6348;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            height: 56px;
        }
        nav .brand { font-family: 'Playfair Display', serif; font-size: 1.2rem; }
        nav .nav-right { display: flex; align-items: center; gap: 20px; font-size: 0.85rem; }
        nav a { color: #d4dbc8; text-decoration: none; }
        nav a:hover { color: #fff; }

        /* Content wrapper */
        main { max-width: 1200px; margin: 32px auto; padding: 0 20px; }

        /* Stats bar */
        .stats { display: flex; gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px 24px;
            flex: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-card .value { font-size: 2rem; font-weight: 600; color: #5a6348; }
        .stat-card .label { font-size: 0.8rem; color: #888; margin-top: 2px; }

        /* Filters */
        .filters {
            background: #fff;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex-wrap: wrap;
        }
        .filters input[type="text"], .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            outline: none;
        }
        .filters input[type="text"] { flex: 1; min-width: 180px; }
        .filters input[type="text"]:focus, .filters select:focus { border-color: #8b947a; }
        .btn {
            padding: 8px 18px;
            background: #8b947a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #737d63; }
        .btn-outline {
            background: transparent;
            color: #8b947a;
            border: 1px solid #8b947a;
        }
        .btn-outline:hover { background: #8b947a; color: #fff; }

        /* Table */
        .table-wrap {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        thead tr { background: #f0f0eb; }
        th { text-align: left; padding: 12px 16px; font-weight: 600; color: #555; white-space: nowrap; }
        td { padding: 12px 16px; border-top: 1px solid #f0eeeb; vertical-align: middle; }
        tr:hover td { background: #fafaf7; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-basic   { background: #e8f5e9; color: #388e3c; }
        .badge-premium { background: #e3f2fd; color: #1565c0; }
        .badge-luxury  { background: #fce4ec; color: #ad1457; }
        .badge-custom  { background: #fff3e0; color: #e65100; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap; }
        .pagination a, .pagination span {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.875rem;
            text-decoration: none;
            background: #fff;
            color: #555;
            border: 1px solid #ddd;
        }
        .pagination a:hover { background: #8b947a; color: #fff; border-color: #8b947a; }
        .pagination .active { background: #5a6348; color: #fff; border-color: #5a6348; }

        .error-msg { background: #fde8e8; color: #c0392b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .empty { text-align: center; padding: 48px 20px; color: #999; }
    </style>
</head>
<body>
<nav>
    <span class="brand">Special Events &mdash; Admin</span>
    <div class="nav-right">
        <span>Signed in as <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong></span>
        <a href="logout.php">Sign Out</a>
    </div>
</nav>

<main>
    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="value"><?= $total ?></div>
            <div class="label">Total Submissions</div>
        </div>
        <?php
        // Quick package breakdown (only if no error)
        if (empty($dbError)):
            $pkgStmt = $pdo->query("SELECT decor_package, COUNT(*) AS cnt FROM submissions GROUP BY decor_package");
            $pkgCounts = $pkgStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        endif;
        ?>
        <div class="stat-card">
            <div class="value"><?= ($pkgCounts['basic'] ?? 0) ?></div>
            <div class="label">Package 1 (Basic)</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= ($pkgCounts['premium'] ?? 0) ?></div>
            <div class="label">Package 2 (Premium)</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= ($pkgCounts['luxury'] ?? 0) ?></div>
            <div class="label">Package 3 / 4 (Luxury)</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= ($pkgCounts['custom'] ?? 0) ?></div>
            <div class="label">Custom Package</div>
        </div>
    </div>

    <!-- Filters -->
    <form class="filters" method="GET" action="index.php">
        <input type="text" name="search" placeholder="Search name, email, phone…"
               value="<?= htmlspecialchars($search) ?>">
        <select name="package">
            <option value="">All Packages</option>
            <option value="basic"   <?= $package === 'basic'   ? 'selected' : '' ?>>Package 1</option>
            <option value="premium" <?= $package === 'premium' ? 'selected' : '' ?>>Package 2</option>
            <option value="luxury"  <?= $package === 'luxury'  ? 'selected' : '' ?>>Package 3 / 4</option>
            <option value="custom"  <?= $package === 'custom'  ? 'selected' : '' ?>>Custom</option>
        </select>
        <button class="btn" type="submit">Filter</button>
        <?php if ($search || $package): ?>
            <a class="btn btn-outline" href="index.php">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($dbError)): ?>
        <div class="error-msg"><?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Submitted</th>
                    <th>Client</th>
                    <th>Partner</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Wedding Date</th>
                    <th>Location</th>
                    <th>Package</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($submissions)): ?>
                <tr><td colspan="10" class="empty">No submissions found.</td></tr>
            <?php else: foreach ($submissions as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= date('d M Y', strtotime($row['submission_date'])) ?></td>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><?= htmlspecialchars($row['partner_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= $row['wedding_date'] ? date('d M Y', strtotime($row['wedding_date'])) : '—' ?></td>
                    <td><?= htmlspecialchars($row['location'] ?: '—') ?></td>
                    <td>
                        <?php
                        $pkg = $row['decor_package'];
                        $cls = in_array($pkg, ['basic','premium','luxury','custom']) ? "badge-$pkg" : 'badge-custom';
                        echo "<span class='badge $cls'>" . htmlspecialchars($packageLabels[$pkg] ?? ucfirst($pkg)) . "</span>";
                        ?>
                    </td>
                    <td><a class="btn" href="view.php?id=<?= $row['id'] ?>">View</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $buildUrl = function(int $p) use ($search, $package): string {
            $q = http_build_query(array_filter(['search' => $search, 'package' => $package, 'page' => $p]));
            return 'index.php' . ($q ? "?$q" : '');
        };
        if ($page > 1): ?>
            <a href="<?= $buildUrl($page - 1) ?>">&laquo; Prev</a>
        <?php endif;
        for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $buildUrl($i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor;
        if ($page < $totalPages): ?>
            <a href="<?= $buildUrl($page + 1) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
