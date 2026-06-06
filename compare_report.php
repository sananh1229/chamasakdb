<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$currentYear = date('Y');
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_topic = isset($_GET['search_topic']) ? $conn->real_escape_string(trim($_GET['search_topic'])) : '';
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';

$where_conditions = [];
if ($_SESSION['role'] == 'user') {
    $where_conditions[] = "t.district_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_conditions[] = "t.district_id = $search_dept";
}

if ($search_topic !== '') { $where_conditions[] = "t.title LIKE '%$search_topic%'"; }
if ($search_group !== '') { $where_conditions[] = "t.work_group = '$search_group'"; }

$where_topic = (count($where_conditions) > 0) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$topics_query = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN districts d ON t.district_id = d.id $where_topic ORDER BY t.district_id ASC, t.id DESC");

function getTopicMonthlyData($tid, $months, $conn, $currentYear) {
    $current = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = $currentYear AND record_date >= DATE_SUB(NOW(), INTERVAL $months MONTH)")->fetch_assoc()['total'];
    $past = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = $currentYear AND record_date >= DATE_SUB(NOW(), INTERVAL " . ($months * 2) . " MONTH) AND record_date < DATE_SUB(NOW(), INTERVAL $months MONTH)")->fetch_assoc()['total'];
    $pct = ($past > 0) ? (($current - $past) / $past) * 100 : (($current > 0) ? 100 : 0);
    return ['current' => $current, 'pct' => $pct];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lາຍງານສົມທຽບຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 15px; color: #334155; }
        .container { max-width: 1200px; margin: auto; }
        .filter-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .input-search, select, button, .btn-clear { flex: 1; min-width: 200px; padding: 11px 14px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; background: #fff; color: #334155; height: 44px; }
        button { background: #3182ce; color: #fff; border: none; font-weight: bold; max-width: 150px; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; max-width: 100px; }
        .topic-block { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 30px; }
        .topic-header { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-bottom: 20px; }
        .topic-header h3 { margin: 0 0 5px 0; font-size: 18px; color: #0f172a; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; }
        .value { font-size: 24px; font-weight: bold; color: #0f172a; }
        .percent { display: inline-block; margin-top: 8px; padding: 4px 8px; border-radius: 5px; font-weight: bold; }
        .up { background: #f0fff4; color: #2f855a; } .down { background: #fff5f5; color: #c53030; }
        @media (max-width: 768px) { .filter-form { flex-direction: column; align-items: stretch; } .input-search, select, button, .btn-clear { width: 100%; max-width: 100%; } .wrapper { flex-direction: column; } }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">ເມນູ</button>
    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item active"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <li class="sidebar-item"><a href="manage_structure.php">ຈັດການໂຄງສ້າງລະບົບ</a></li>
                    <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                    <a href="logout.php" style="background: #f56565; color: #fff; text-align: center;">ອອກຈາກລະບົບ</a>
                </li>
            </ul>
        </nav>

        <main class="main-content" style="flex:1; padding:20px;">
            <h2>ລາຍງານສົມທຽບຂໍ້ມູນການເຕີບໂຕ</h2>
            <div class="filter-box">
                <form method="GET" class="filter-form">
                    <input type="text" name="search_topic" class="input-search" placeholder="ຄົ້ນຫາຫົວຂໍ້..." value="<?php echo htmlspecialchars($search_topic); ?>">
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <select name="dept_id">
                            <option value="">-- ທຸກນະຄອນ/ເມືອງ --</option>
                            <?php
                            $depts = $conn->query("SELECT * FROM districts");
                            while($d = $depts->fetch_assoc()) {
                                echo "<option value='{$d['id']}' ".($search_dept == $d['id'] ? 'selected':'').">{$d['name']}</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>
                    <select name="work_group">
                        <option value="">-- ທຸກກຸ່ມວຽກ --</option>
                        <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກการເມືອງແנວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືອງແנວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-%E0%BB%9B%E0%BB%80%E0%BB%AA" <?php echo ($search_group == 'ກຸ່ມວຽກ ປກຊ-%E0%BB%9B%E0%BB%80%E0%BB%AA') ? 'selected':''; ?>>ກຸ່ມວຽກ ປกຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖะກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສດຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ" <?php echo ($search_group == 'ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ') ? 'selected':''; ?>>ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>
                    <button type="submit">ຄົ້ນຫາ</button>
                    <?php if($search_topic !== '' || $search_dept !== '' || $search_group !== ''): ?>
                        <a href="compare_report.php" class="btn-clear">ລ້າງຄ່າ</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php
            if($topics_query && $topics_query->num_rows > 0):
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
                    <small>ນະຄອນ/ເມືອງ: <?php echo htmlspecialchars($topic['dept_name']); ?> | ກຸ່ມວຽກ: <?php echo htmlspecialchars($topic['work_group']); ?> | ຫົວໜ່ວຍ: <?php echo htmlspecialchars($latest_unit); ?></small>
                </div>
                <div class="grid-cards">
                    <div class="card"><h4>ຂໍ້ມູນປີ 2025</h4><div class="value"><?php echo number_format($sum_2025, 2); ?></div></div>
                    <div class="card"><h4>ຂໍ້ມູນປີ 2026</h4><div class="value"><?php echo number_format($sum_2026, 2); ?></div></div>
                    <div class="card"><h4>ສົມທຽບປີ 25-26</h4>
                        <div class="value"><span class="percent <?php echo ($diff_year_pct >= 0) ? 'up':'down'; ?>"><?php echo ($diff_year_pct >= 0) ? '+': ''; echo number_format($diff_year_pct, 2); ?>%</span></div>
                    </div>
                    <div class="card"><h4>ທຽບ 1 ເດືອນ</h4><div class="value"><?php echo number_format($m1['current'], 2); ?></div><span class="percent <?php echo ($m1['pct'] >= 0) ? 'up':'down'; ?>"><?php echo ($m1['pct'] >= 0) ? '+': ''; echo number_format($m1['pct'], 2); ?>%</span></div>
                    <div class="card"><h4>ທຽບ 3 ເດືອນ</h4><div class="value"><?php echo number_format($m3['current'], 2); ?></div><span class="percent <?php echo ($m3['pct'] >= 0) ? 'up':'down'; ?>"><?php echo ($m3['pct'] >= 0) ? '+': ''; echo number_format($m3['pct'], 2); ?>%</span></div>
                    <div class="card"><h4>ທຽບ 6 ເດືອນ</h4><div class="value"><?php echo number_format($m6['current'], 2); ?></div><span class="percent <?php echo ($m6['pct'] >= 0) ? 'up':'down'; ?>"><?php echo ($m6['pct'] >= 0) ? '+': ''; echo number_format($m6['pct'], 2); ?>%</span></div>
                    <div class="card"><h4>ທຽບ 12 ເດືອນ</h4><div class="value"><?php echo number_format($m12['current'], 2); ?></div><span class="percent <?php echo ($m12['pct'] >= 0) ? 'up':'down'; ?>"><?php echo ($m12['pct'] >= 0) ? '+': ''; echo number_format($m12['pct'], 2); ?>%</span></div>
                </div>
            </div>
            <?php endwhile; else: echo "<p style='text-align:center;'>ບໍ່ພົບຂໍ້ມູນ</p>"; endif; ?>
        </main>
    </div>
    <script>
        document.getElementById('menuToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>