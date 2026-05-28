<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_topic = isset($_GET['search_topic']) ? $conn->real_escape_string(trim($_GET['search_topic'])) : '';

$where_conditions = [];

if ($_SESSION['role'] == 'user') {
    $where_conditions[] = "t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_conditions[] = "t.department_id = $search_dept";
}

if ($search_topic !== '') {
    $where_conditions[] = "t.title LIKE '%$search_topic%'";
}

$where_topic = "";
if (count($where_conditions) > 0) {
    $where_topic = "WHERE " . implode(" AND ", $where_conditions);
}

$topics_query = $conn->query("SELECT t.id, t.title, d.name as dept_name FROM topics t JOIN departments d ON t.department_id = d.id $where_topic ORDER BY t.department_id ASC");

function getTopicMonthlyData($tid, $months, $conn) {
    $current = $conn->query("SELECT SUM(amount) as total FROM records WHERE topic_id = $tid AND record_date >= DATE_SUB(NOW(), INTERVAL $months MONTH)")->fetch_assoc()['total'] ?? 0;
    $past = $conn->query("SELECT SUM(amount) as total FROM records WHERE topic_id = $tid AND record_date >= DATE_SUB(NOW(), INTERVAL ".($months * 2)." MONTH) AND record_date < DATE_SUB(NOW(), INTERVAL $months MONTH)")->fetch_assoc()['total'] ?? 0;
    $pct = 0;
    if ($past > 0) { 
        $pct = (($current - $past) / $past) * 100; 
    } elseif ($current > 0) { 
        $pct = 100; 
    }
    return ['current' => $current, 'pct' => $pct];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໜ້າສະຫຼຸບສົມທຽບຂໍ້ມູນແຍກຕາມຫົວຂໍ້</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f8fafc; color: #334155; margin: 0; padding: 15px; }
        .container { max-width: 1200px; margin: auto; }
        h2 { color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; font-size: 20px; }
        
        .topic-block { background: #ffffff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .topic-header { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-bottom: 20px; }
        .topic-header h3 { margin: 0; color: #1e293b; font-size: 18px; }
        .topic-header small { color: #64748b; font-size: 13px; display: block; margin-top: 4px; }
        
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; }
        .card h4 { margin: 0 0 8px 0; color: #64748b; font-size: 13px; font-weight: 600; }
        .card .value { font-size: 22px; font-weight: bold; color: #0f172a; margin: 2px 0; }
        
        .percent { font-size: 15px; font-weight: bold; display: inline-block; margin-top: 5px; padding: 2px 8px; border-radius: 4px; }
        .up { color: #2f855a; background: #f0fff4; } .down { color: #c53030; background: #fff5f5; }
        .explain-text { font-size: 11px; color: #94a3b8; margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px; line-height: 1.4; }
        
        .btn-back { display: inline-block; padding: 10px 20px; background: #ffffff; color: #334155; text-decoration: none; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 14px; }
        .btn-back:hover { background: #f1f5f9; }
        
        .filter-box { background: #ffffff; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .input-search { padding: 10px 12px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; flex: 1; min-width: 240px; font-size: 14px; }
        .input-search:focus, select:focus { border-color: #3182ce; outline: none; }
        select, button, .btn-clear { padding: 10px 15px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        button[type="submit"] { background: #3182ce; border-color: #3182ce; color: #fff; }
        button[type="submit"]:hover { background: #2b6cb0; }
        .btn-clear { background: #64748b; color:#fff; border:none; text-decoration: none; text-align: center; }
        .btn-clear:hover { background: #475569; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 ລາຍງານສະຫຼຸບສົມທຽບຂໍ້ມູນ ເປີເຊັນເພີ່ມຂຶ້ນ/ຫຼຸດລົງ ແຍກຕາມແຕ່ລະຫົວຂໍ້ (Topic)</h2>

        <div class="filter-box">
            <form method="GET" class="filter-form">
                <input type="text" name="search_topic" class="input-search" placeholder="🔍 ຄົ້ນຫາຊື່ຫົວຂໍ້ (Topic)..." value="<?php echo htmlspecialchars($search_topic); ?>">

                <?php if($_SESSION['role'] == 'admin'): ?>
                    <select name="dept_id" style="min-width: 180px;">
                        <option value="">-- ເບິ່ງທຸກພະແນກ --</option>
                        <?php
                        $depts = $conn->query("SELECT * FROM departments");
                        while($d = $depts->fetch_assoc()) {
                            $selected = ($search_dept == $d['id']) ? 'selected' : '';
                            echo "<option value='{$d['id']}' $selected>{$d['name']}</option>";
                        }
                        ?>
                    </select>
                <?php endif; ?>

                <button type="submit">ຄົ້ນຫາ (Search)</button>
                
                <?php if($search_topic !== '' || $search_dept !== ''): ?>
                    <a href="compare_report.php" class="btn-clear">ລ້າງການຄົ້ນຫາ</a>
                <?php endif; ?>
            </form>
        </div>

        <?php 
        if($topics_query->num_rows > 0): 
            while($topic = $topics_query->fetch_assoc()):
                $tid = $topic['id'];
                
                $sum_2025 = $conn->query("SELECT SUM(amount) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = 2025")->fetch_assoc()['total'] ?? 0;
                $sum_2026 = $conn->query("SELECT SUM(amount) as total FROM records WHERE topic_id = $tid AND YEAR(record_date) = 2026")->fetch_assoc()['total'] ?? 0;
                
                $latest_unit_q = $conn->query("SELECT unit FROM records WHERE topic_id = $tid ORDER BY id DESC LIMIT 1");
                $latest_unit = ($latest_unit_q && $latest_unit_q->num_rows > 0) ? $latest_unit_q->fetch_assoc()['unit'] : 'ກິໂລ';

                $diff_year_pct = 0;
                if ($sum_2025 > 0) { 
                    $diff_year_pct = (($sum_2026 - $sum_2025) / $sum_2025) * 100; 
                } elseif ($sum_2026 > 0) { 
                    $diff_year_pct = 100; 
                }

                $m1  = getTopicMonthlyData($tid, 1, $conn);
                $m3  = getTopicMonthlyData($tid, 3, $conn);
                $m6  = getTopicMonthlyData($tid, 6, $conn);
                $m12 = getTopicMonthlyData($tid, 12, $conn);
        ?>
                <div class="topic-block">
                    <div class="topic-header">
                        <h3>📌 ຫົວຂໍ້: <?php echo $topic['title']; ?></h3>
                        <small>ສັງກັດ: <?php echo $topic['dept_name']; ?> | ຫົວໜ່ວຍວັດແທກ: <?php echo $latest_unit; ?></small>
                    </div>
                    
                    <div class="grid-cards">
                        <div class="card">
                            <h4>ຍອດລວມ ປີ 2025</h4>
                            <div class="value"><?php echo number_format($sum_2025, 0); ?></div>
                            <div class="explain-text">ຜົນລວມຂໍ້ມູນທັງໝົດໃນປີ 2025</div>
                        </div>

                        <div class="card">
                            <h4>ຍອດລວມ ປີ 2026</h4>
                            <div class="value"><?php echo number_format($sum_2026, 0); ?></div>
                            <div class="explain-text">ຜົນລວມข้อมูลທັງໝົດໃນປີ 2026</div>
                        </div>

                        <div class="card">
                            <h4>ສົມທຽບ ປີ 2025 - 2026</h4>
                            <div class="value">
                                <span class="percent <?php echo ($diff_year_pct >= 0) ? 'up' : 'down'; ?>">
                                    <?php echo ($diff_year_pct >= 0) ? '▲ +' : '▼ '; echo number_format($diff_year_pct, 2); ?>%
                                </span>
                            </div>
                            <div class="explain-text">ອັດຕາເຕີບໂຕ ປີ 2026 ທຽບກັບ ປີ 2025</div>
                        </div>

                        <div class="card">
                            <h4>ສົມທຽບ 1 ເດືອນຜ່ານມາ</h4>
                            <div class="value"><?php echo number_format($m1['current'], 0); ?></div>
                            <div>
                                <span class="percent <?php echo ($m1['pct'] >= 0) ? 'up' : 'down'; ?>">
                                    <?php echo ($m1['pct'] >= 0) ? '▲ +' : '▼ '; echo number_format($m1['pct'], 2); ?>%
                                </span>
                            </div>
                            <div class="explain-text">ທຽບ 30 ວັນຫຼ້າສຸດ ກັບ 30 ວັນກ່ອນໜ້າ</div>
                        </div>

                        <div class="card">
                            <h4>ສົມທຽບ 3 ເດືອນຜ່ານມາ</h4>
                            <div class="value"><?php echo number_format($m3['current'], 0); ?></div>
                            <div>
                                <span class="percent <?php echo ($m3['pct'] >= 0) ? 'up' : 'down'; ?>">
                                    <?php echo ($m3['pct'] >= 0) ? '▲ +' : '▼ '; echo number_format($m3['pct'], 2); ?>%
                                </span>
                            </div>
                            <div class="explain-text">ທຽບ 3 ເດືອນຫຼ້າສຸດ ກັບ 3 ເດືອນກ່ອນໜ້າ</div>
                        </div>

                        <div class="card">
                            <h4>ສົມທຽບ 12 ເດືອນຜ່ານມາ</h4>
                            <div class="value"><?php echo number_format($m12['current'], 0); ?></div>
                            <div>
                                <span class="percent <?php echo ($m12['pct'] >= 0) ? 'up' : 'down'; ?>">
                                    <?php echo ($m12['pct'] >= 0) ? '▲ +' : '▼ '; echo number_format($m12['pct'], 2); ?>%
                                </span>
                            </div>
                            <div class="explain-text">ທຽບ 1 ປີຫຼ້າສຸດ ກັບ 1 ປີກ່ອນໜ້າ</div>
                        </div>
                    </div>
                </div>
        <?php 
            endwhile; 
        else:
            echo "<p style='text-align:center; padding:50px; color: #64748b;'>❌ ບໍ່ມີຂໍ້ມູນຫົວຂໍ້ທີ່ທ່ານຄົ້ນຫາ</p>";
        endif; 
        ?>

        <p style="margin-top: 30px;"><a href="dashboard.php" class="btn-back">← ກັບຄືນໜ້າຫຼັກ Dashboard</a></p>
    </div>
</body>
</html>