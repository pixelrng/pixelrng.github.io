<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/trainclicker-data/config.php';

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
);

session_name('trainclicker_stats');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/trainclickerstats/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ./');
    exit;
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedPassword = (string) ($_POST['password'] ?? '');

    if (hash_equals(TC_STATS_PASSWORD, $submittedPassword)) {
        session_regenerate_id(true);
        $_SESSION['trainclicker_authenticated'] = true;
        header('Location: ./');
        exit;
    }

    $loginError = 'Incorrect password.';
}

$authenticated = ($_SESSION['trainclicker_authenticated'] ?? false) === true;

function tc_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!$authenticated):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>TrainClicker Stats</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #111827;
            color: #f9fafb;
        }
        main {
            width: min(380px, calc(100% - 32px));
        }
        form {
            display: grid;
            gap: 14px;
            padding: 24px;
            border: 1px solid #374151;
            border-radius: 14px;
            background: #1f2937;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 1.4rem;
        }
        label {
            font-weight: 600;
        }
        input, button {
            box-sizing: border-box;
            width: 100%;
            padding: 11px 12px;
            border-radius: 9px;
            font: inherit;
        }
        input {
            border: 1px solid #4b5563;
            background: #111827;
            color: #f9fafb;
        }
        button {
            border: 0;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        .error {
            margin: 0;
            color: #fca5a5;
        }
    </style>
</head>
<body>
<main>
    <form method="post" action="./">
        <div>
            <h1>TrainClicker Stats</h1>
            <div>Enter the dashboard password.</div>
        </div>

        <?php if ($loginError !== ''): ?>
            <p class="error"><?= tc_h($loginError) ?></p>
        <?php endif; ?>

        <div>
            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                autofocus
            >
        </div>

        <button type="submit">View stats</button>
    </form>
</main>
</body>
</html>
<?php
exit;
endif;

$stats = tc_load_stats();

$platforms = [
    'ios' => (int) ($stats['platforms']['ios'] ?? 0),
    'android' => (int) ($stats['platforms']['android'] ?? 0),
    'steam' => (int) ($stats['platforms']['steam'] ?? 0),
];

$sources = is_array($stats['sources'] ?? null) ? $stats['sources'] : [];
uasort($sources, static function (array $a, array $b): int {
    return ((int) ($b['total'] ?? 0)) <=> ((int) ($a['total'] ?? 0));
});

$daily = is_array($stats['daily'] ?? null) ? $stats['daily'] : [];
krsort($daily);
$daily = array_slice($daily, 0, 30, true);

$total = (int) ($stats['total'] ?? 0);
$updatedAt = (string) ($stats['updated_at'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>TrainClicker Stats</title>
    <style>
        :root {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background: #f4f7fb;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
        }
        main {
            width: min(1050px, calc(100% - 32px));
            margin: 32px auto 60px;
        }
        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }
        h1, h2 {
            margin-top: 0;
        }
        h1 {
            margin-bottom: 5px;
        }
        .muted {
            color: #68738a;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .button {
            display: inline-block;
            padding: 9px 13px;
            border: 1px solid #cbd3df;
            border-radius: 8px;
            background: white;
            color: #172033;
            text-decoration: none;
            font-weight: 650;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .card, section {
            border: 1px solid #dce2eb;
            border-radius: 12px;
            background: white;
        }
        .card {
            padding: 18px;
        }
        .card-label {
            color: #68738a;
            font-size: 0.9rem;
        }
        .card-value {
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 750;
        }
        section {
            margin-top: 20px;
            padding: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }
        th, td {
            padding: 11px 10px;
            border-bottom: 1px solid #e5e9f0;
            text-align: right;
            white-space: nowrap;
        }
        th:first-child, td:first-child {
            text-align: left;
        }
        th {
            color: #536078;
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        tbody tr:last-child td {
            border-bottom: 0;
        }
        code {
            padding: 2px 5px;
            border-radius: 5px;
            background: #eef2f7;
        }
        .note {
            padding: 14px 16px;
            border-left: 4px solid #2563eb;
            background: #eaf2ff;
            border-radius: 6px;
            margin-top: 20px;
        }
        @media (max-width: 760px) {
            header {
                display: block;
            }
            .actions {
                margin-top: 14px;
            }
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>TrainClicker Link Stats</h1>
            <div class="muted">
                Redirect requests by source and platform.
                <?php if ($updatedAt !== ''): ?>
                    Updated <?= tc_h(date('M j, Y g:i A T', strtotime($updatedAt))) ?>.
                <?php endif; ?>
            </div>
        </div>

        <div class="actions">
            <a class="button" href="./">Refresh</a>
            <a class="button" href="?logout=1">Log out</a>
        </div>
    </header>

    <div class="cards">
        <div class="card">
            <div class="card-label">All redirects</div>
            <div class="card-value"><?= number_format($total) ?></div>
        </div>
        <div class="card">
            <div class="card-label">iOS</div>
            <div class="card-value"><?= number_format($platforms['ios']) ?></div>
        </div>
        <div class="card">
            <div class="card-label">Android</div>
            <div class="card-value"><?= number_format($platforms['android']) ?></div>
        </div>
        <div class="card">
            <div class="card-label">Steam / desktop</div>
            <div class="card-value"><?= number_format($platforms['steam']) ?></div>
        </div>
    </div>

    <section>
        <h2>Traffic sources</h2>
        <table>
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Total</th>
                    <th>iOS</th>
                    <th>Android</th>
                    <th>Steam</th>
                    <th>Share</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($sources === []): ?>
                <tr>
                    <td colspan="6">No tracked requests yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($sources as $source => $row): ?>
                    <?php
                    $sourceTotal = (int) ($row['total'] ?? 0);
                    $share = $total > 0 ? ($sourceTotal / $total) * 100 : 0;
                    ?>
                    <tr>
                        <td><code><?= tc_h((string) $source) ?></code></td>
                        <td><?= number_format($sourceTotal) ?></td>
                        <td><?= number_format((int) ($row['ios'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['android'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['steam'] ?? 0)) ?></td>
                        <td><?= number_format($share, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Last 30 active days</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total</th>
                    <th>iOS</th>
                    <th>Android</th>
                    <th>Steam</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($daily === []): ?>
                <tr>
                    <td colspan="5">No tracked requests yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($daily as $date => $row): ?>
                    <tr>
                        <td><?= tc_h((string) $date) ?></td>
                        <td><?= number_format((int) ($row['total'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['ios'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['android'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['steam'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <div class="note">
        These are redirect requests, not confirmed installs. Repeat clicks may count
        more than once, while common preview crawlers and bots are filtered out.
    </div>
</main>
</body>
</html>
