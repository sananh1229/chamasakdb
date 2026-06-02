<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentYear = date('Y');

/* =========================
   SEARCH & FILTER
========================= */
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_topic = isset($_GET['search_topic']) ? $conn->real_escape_string(trim($_GET['search_topic'])) : '';
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';

$where_conditions = [];

if ($_SESSION['role'] == 'user') {
    $where_conditions[] = "t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_conditions[] = "t.department_id = $search_dept";
}

if ($search_topic !== '') {
    $where_conditions[] = "t.title LIKE '%$search_topic%'";
}

if ($search_group !== '') {
    $where_conditions[] = "t.work_group = '$search_group'";
}

$where_topic = '';
if (count($where_conditions) > 0) {
    $where_topic = "WHERE " . implode(" AND ", $where_conditions);
}

/* =========================
   TOPIC QUERY
========================= */
$topics_query = $conn->query("
    SELECT
        t.id,
        t.title,
        t.work_group,
        d.name as dept_name
    FROM topics t
    JOIN departments d ON t.department_id = d.id
    $where_topic
    ORDER BY t.department_id ASC, t.id DESC
");

/* =========================
   MONTH COMPARE FUNCTION
========================= */
function getTopicMonthlyData($tid, $months, $conn, $currentYear) {
    $current = $conn->query("
        SELECT COALESCE(SUM(amount),0) as total
        FROM records
        WHERE topic_id = $tid
        AND YEAR(record_date) = $currentYear
        AND record_date >= DATE_SUB(NOW(), INTERVAL $months MONTH)
    ")->fetch_assoc()['total'];

    $past = $conn->query("
        SELECT COALESCE(SUM(amount),0) as total
        FROM records
        WHERE topic_id = $tid
        AND YEAR(record_date) = $currentYear
        AND record_date >= DATE_SUB(NOW(), INTERVAL " . ($months * 2) . " MONTH)
        AND record_date < DATE_SUB(NOW(), INTERVAL $months MONTH)
    ")->fetch_assoc()['total'];

    $pct = 0;
    if ($past > 0) {
        $pct = (($current - $past) / $past) * 100;
    } elseif ($current > 0) {
        $pct = 100;
    }

    return [
        'current' => $current,
        'pct' => $pct
    ];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານສົມທຽບຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 15px; color: #334155; }
        .container { max-width: 1200px; margin: auto; }
        h2 { margin-bottom: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; font-size: 24px; }
        .filter-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .input-search, select, button, .btn-clear { flex: 1; min-width: 200px; padding: 11px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; background: #fff; color: #334155; height: 44px; }
        button { background: #3182ce; color: #fff; cursor: pointer; border: none; font-weight: bold; max-width: 150px; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; max-width: 100px; }
        .topic-block { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 30px; }
        .topic-header { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-bottom: 20px; }
        .topic-header h3 { margin: 0 0 5px 0; font-size: 18px; color: #0f172a; }
        .topic-header small { font-size: 13px; color: #64748b; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; }
        .card h4 { margin: 0 0 10px 0; font-size: 13px; color: #64748b; }
        .value { font-size: 24px; font-weight: bold; color: #0f172a; }
        .percent { display: inline-block; margin-top: 8px; padding: 4px 8px; border-radius: 5px; font-weight: bold; font-size: 14px; }
        .up { background: #f0fff4; color: #2f855a; }
        .down { background: #fff5f5; color: #c53030; }
        .explain-text { margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; line-height: 1.5; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #334155; font-weight: bold; }
        .no-data { text-align: center; padding: 50px; color: #64748b; font-weight: bold; }
        @media (max-width: 768px) {
            .filter-form { flex-direction: column; align-items: stretch; }
            .input-search, select, button, .btn-clear { width: 100%; max-width: 100%; }
            .grid-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>ລາຍງານສົມທຽບຂໍ້ມູນ</h2>

    <div class="filter-box">
        <form method="GET" class="filter-form">
            <input type="text" name="search_topic" class="input-search" placeholder="ຄົ້ນຫາຫົວຂໍ້..." value="<?php echo htmlspecialchars($search_topic); ?>">

            <?php if($_SESSION['role'] == 'admin'): ?>
                <select name="dept_id">
                    <option value="">-- ເບິ່ງທຸກພະແນກ --</option>
                    <?php
                    $depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
                    while($d = $depts->fetch_assoc()):
                        $selected = ($search_dept == $d['id']) ? 'selected' : '';
                        echo "<option value='{$d['id']}' $selected>".htmlspecialchars($d['name'])."</option>";
                    endwhile;
                    ?>
                </select>
            <?php endif; ?>

            <select name="work_group">
                <option value="">-- ເບິ່ງທຸກກຸ່ມວຽກ --</option>
                <option value="ກຸ່ມວຽກການເມືອງແນວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກການເມືອງແນວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                <option value="ກຸ່ມວຽກ ປກຊ-ປກສ" <?php echo ($search_group == 'ກຸ່ມວຽກ ປກຊ-ປກສ') ? 'selected':''; ?>>ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                <option value="ກຸ່ມວຽກເສດຖະກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສດຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
                <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ" <?php echo ($search_group == 'ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ') ? 'selected':''; ?>>ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
            </select>

            <button type="submit">ຄົ້ນຫາ</button>
            <?php if($search_topic !== '' || $search_dept !== '' || $search_group !== ''): ?>
                <a href="compare_report.php" class="btn-clear">ລ້າງຄ່າ</a>
            <?php endif; ?>
        </form>
    </div>

    <?php
    if($topics_query->num_rows > 0):
        while($topic = $topics_query->fetch_assoc()):
            $tid = $topic['id'];

            $sum_2025 = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = 2025")->fetch_assoc()['total'];
            $sum_2026 = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = 2026")->fetch_assoc()['total'];

            $diff_year_pct = ($sum_2025 > 0) ? (($sum_2026 - $sum_2025) / $sum_2025) * 100 : (($sum_2026 > 0) ? 100 : 0);

            $latest_unit_q = $conn->query("SELECT unit FROM records WHERE topic_id = $tid ORDER BY id DESC LIMIT 1");
            $latest_unit = ($latest_unit_q && $latest_unit_q->num_rows > 0) ? $latest_unit_q->fetch_assoc()['unit'] : 'ກິໂລ';

            $m1  = getTopicMonthlyData($tid, 1, $conn, $currentYear);
            $m3  = getTopicMonthlyData($tid, 3, $conn, $currentYear);
            $m6  = getTopicMonthlyData($tid, 6, $conn, $currentYear);
            $m12 = getTopicMonthlyData($tid, 12, $conn, $currentYear);
    ?>
    <div class="topic-block">
        <div class="topic-header">
            <h3><?php echo htmlspecialchars($topic['title']); ?></h3>
            <small>
                ພະແນກ: <?php echo htmlspecialchars($topic['dept_name']); ?> | 
                ກຸ່ມວຽກ: <span style="color: #3182ce; font-weight: bold;"><?php echo htmlspecialchars($topic['work_group'] ?? 'ກຸ່ມວຽກເສດຖະກິດ'); ?></span> | 
                ຫົວໜ່ວຍ: <?php echo htmlspecialchars($latest_unit); ?>
            </small>
        </div>

        <div class="grid-cards">
            <div class="card">
                <h4>ຂໍ້ມູນປີ 2025</h4>
                <div class="value"><?php echo number_format($sum_2025, 2); ?></div>
                <div class="explain-text">ສະແດງສະເພາະຂໍ້ມູນປີ 2025</div>
            </div>

            <div class="card">
                <h4>ຂໍ້ມູນປີ 2026</h4>
                <div class="value"><?php echo number_format($sum_2026, 2); ?></div>
                <div class="explain-text">ສະແດງສະເພາະຂໍ້ມູນປີ 2026</div>
            </div>

            <div class="card">
                <h4>ສົມທຽບ 2025 - 2026</h4>
                <div class="value">
                    <span class="percent <?php echo ($diff_year_pct >= 0) ? 'up' : 'down'; ?>">
                        <?php echo ($diff_year_pct >= 0) ? '+' : ''; echo number_format($diff_year_pct, 2); ?>%
                    </span>
                </div>
                <div class="explain-text"><?php echo ($diff_year_pct >= 0) ? 'ປີ 2026 ເພີ່ມຂຶ້ນຈາກປີ 2025' : 'ປີ 2026 ຫຼຸດລົງจากປີ 2025'; ?></div>
            </div>

            <div class="card">
                <h4>ສົມທຽບ 1 ເດືອນ</h4>
                <div class="value"><?php echo number_format($m1['current'], 2); ?></div>
                <span class="percent <?php echo ($m1['pct'] >= 0) ? 'up' : 'down'; ?>">
                    <?php echo ($m1['pct'] >= 0) ? '+' : ''; echo number_format($m1['pct'], 2); ?>%
                </span>
                <div class="explain-text">ທຽບໄລຍະ 1 ເດືອນພາຍໃນປີປະຈຸບັນ</div>
            </div>

            <div class="card">
                <h4>ສົມທຽບ 3 ເດືອນ</h4>
                <div class="value"><?php echo number_format($m3['current'], 2); ?></div>
                <span class="percent <?php echo ($m3['pct'] >= 0) ? 'up' : 'down'; ?>">
                    <?php echo ($m3['pct'] >= 0) ? '+' : ''; echo number_format($m3['pct'], 2); ?>%
                </span>
                <div class="explain-text">ທຽບໄລຍະ 3 ເດືອນພາຍໃນປີປະຈຸບັນ</div>
            </div>

            <div class="card">
                <h4>ສົມທຽບ 6 ເດືອນ</h4>
                <div class="value"><?php echo number_format($m6['current'], 2); ?></div>
                <span class="percent <?php echo ($m6['pct'] >= 0) ? 'up' : 'down'; ?>">
                    <?php echo ($m6['pct'] >= 0) ? '+' : ''; echo number_format($m6['pct'], 2); ?>%
                </span>
                <div class="explain-text">ທຽບໄລຍະ 6 ເດືອນພາຍໃນປີປະຈຸบัน</div>
            </div>

            <div class="card">
                <h4>ສົມທຽບ 12 ເດືອນ</h4>
                <div class="value"><?php echo number_format($m12['current'], 2); ?></div>
                <span class="percent <?php echo ($m12['pct'] >= 0) ? 'up' : 'down'; ?>">
                    <?php echo ($m12['pct'] >= 0) ? '+' : ''; echo number_format($m12['pct'], 2); ?>%
                </span>
                <div class="explain-text">ທຽບໄລຍະ 12 ເດືອນພາຍໃນປີປະຈຸບັນ</div>
            </div>
        </div>
    </div>
    <?php
        endwhile;
    else:
        echo "<p class='no-data'>ບໍ່ພົບຂໍ້ມູນ</p>";
    endif;
    ?>
    <a href="dashboard.php" class="btn-back">ກັບ Dashboard</a>
</div>
</body>
</html>