<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$current_year = intval(date('Y'));
$prev_year = $current_year - 1;

$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_topic = isset($_GET['search_topic']) ? $conn->real_escape_string(trim($_GET['search_topic'])) : '';
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';

$where_conditions = [];
if ($_SESSION['role'] == 'user') {
    $where_conditions[] = "t.district_id = " . intval($_SESSION['dept_id']);
} elseif ($search_dept) {
    $where_conditions[] = "t.district_id = $search_dept";
}

if ($search_topic !== '') { $where_conditions[] = "t.title LIKE '%$search_topic%'"; }
if ($search_group !== '') { $where_conditions[] = "t.work_group = '$search_group'"; }

$where_topic = (count($where_conditions) > 0) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// ดึงข้อมูลเชื่อมตาราง districts ตามโครงสร้างฐานข้อมูลของคุณ
$topics_query = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN districts d ON t.district_id = d.id $where_topic ORDER BY d.id ASC, t.id ASC");
$depts_list = $conn->query("SELECT * FROM districts ORDER BY id ASC");

function getPeriodAmount($conn, $topic_id, $year, $months_count) {
    $q = "SELECT SUM(amount) as total FROM records 
          WHERE topic_id = $topic_id 
          AND YEAR(record_date) = $year 
          AND MONTH(record_date) <= $months_count";
    $res = $conn->query($q);
    if ($res) {
        $row = $res->fetch_assoc();
        return floatval($row['total'] ?? 0);
    }
    return 0;
}

function calcPct($old, $new) {
    if ($old == 0 && $new == 0) return 0;
    if ($old == 0) return 100;
    return (($new - $old) / $old) * 100;
}

// ฟังก์ชันแสดงผลเปอร์เซ็นต์แบบมีสีสันสดใส
function renderColorPct($pct) {
    if ($pct > 0) {
        return "<span style='color: #10b981; font-weight: 700;'>+" . number_format($pct, 1) . "% ⬆</span>";
    } elseif ($pct < 0) {
        return "<span style='color: #ef4444; font-weight: 700;'>" . number_format($pct, 1) . "% ⬇</span>";
    }
    return "<span style='color: #3b82f6; font-weight: 700;'>0.0% ➡</span>";
}

// ฟังก์ชันกำหนดสีพื้นหลังตามกลุ่มงานเพื่อเพิ่มสีสันให้หน้าเว็บ
function getGroupBadgeStyle($group) {
    if ($group == 'ກຸ່ມວຽກການເມືອງແנວຄິດ') return 'background: #eff6ff; color: #1d4ed8; border-left: 4px solid #3b82f6;';
    if ($group == 'ກຸ່ມວຽກ ປກຊ-ປກສ') return 'background: #fff1f2; color: #9f1239; border-left: 4px solid #f43f5e;';
    if ($group == 'ກຸ່ມວຽກເສດຖະກິດ') return 'background: #ecfdf5; color: #065f46; border-left: 4px solid #10b981;';
    return 'background: #fff7ed; color: #9a3412; border-left: 4px solid #f97316;'; // กลุ่มงานวัฒนธรรมสังคม
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານສະຫຼຸບສົມທຽບຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f1f5f9; margin: 0; color: #1e293b; }
        .wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar Styling */
        .sidebar { width: 260px; background: #ffffff; padding: 25px; flex-shrink: 0; border-right: 1px solid #e2e8f0; }
        .sidebar-brand { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 10px; }
        .sidebar-item a { display: block; padding: 12px 18px; color: #475569; text-decoration: none; font-weight: 500; border-radius: 8px; transition: all 0.2s; }
        .sidebar-item a:hover { background: #f8fafc; color: #0f172a; }
        .sidebar-item.active a { background: #2563eb; color: #ffffff; font-weight: 700; }

        /* Main Content Styling */
        .main-content { flex: 1; padding: 35px; width: 100%; }
        h2 { font-size: 28px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .sub-t { color: #64748b; font-size: 15px; margin-bottom: 30px; }
        
        /* Modern Filter Box */
        .filter-box { background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .input-control, .select-control { flex: 1; min-width: 220px; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; height: 46px; background: #ffffff; color: #334155; }
        .input-control:focus, .select-control:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .btn-search { background: #2563eb; color: #fff; border: none; font-weight: 700; cursor: pointer; height: 46px; padding: 0 30px; border-radius: 8px; font-size: 14px; transition: background 0.2s; }
        .btn-search:hover { background: #1d4ed8; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 20px; border-radius: 8px; font-size: 14px; }
        
        /* Modern Card List Styles (แทนที่ตาราง Excel เก่า) */
        .report-card-grid { display: flex; flex-direction: column; gap: 20px; }
        .report-card { background: #ffffff; border-radius: 14px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.006); }
        
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        .card-title-section { max-width: 70%; }
        .card-dept { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
        .card-topic-title { font-size: 15px; color: #475569; font-weight: 500; line-height: 1.5; }
        .card-group-badge { padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 6px; }
        
        /* ส่วนแสดงตัวเลขเปรียบเทียบแบ่ง 4 ช่วงเวลาในรูปแบบ Grid สวยงาม */
        .card-periods-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 15px; }
        @media (min-width: 576px) { .card-periods-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1200px) { .card-periods-grid { grid-template-columns: repeat(4, 1fr); } }
        
        .period-sub-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; }
        .period-title { font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        
        .period-values { display: flex; flex-direction: column; gap: 4px; }
        .val-row { display: flex; justify-content: space-between; font-size: 13px; }
        .val-row span:first-child { color: #64748b; }
        .val-row span:last-child { font-weight: 700; color: #0f172a; }
        .pct-row { text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; font-size: 14px; }

        @media (max-width: 992px) { .wrapper { flex-direction: column; } .sidebar { width: 100%; border-right: none; border-bottom: 1px solid #e2e8f0; } }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item active"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <li class="sidebar-item"><a href="manage_structure.php">ຈັດການໂຄงສ້າງລະບົບ</a></li>
                    <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ລາຍງານສະຫຼຸບສົມທຽບຂໍ້ມູນສະຖິຕິ</h2>
            <div class="sub-t">ສົມທຽບຜົນຜະລິດລະຫວ່າງ ປີປະຈຸບັນ (<?php echo $current_year; ?>) ກັບ ປີຜ່ານມາ (<?php echo $prev_year; ?>) ແຍກຕາມໄລຍະເວລາສະສົມ</div>

            <div class="filter-box">
                <form method="GET" class="filter-form">
                    <input type="text" name="search_topic" class="input-control" placeholder="ຄົ້ນຫາຫົວຂໍ້ກິດຈະກຳ..." value="<?php echo htmlspecialchars($search_topic); ?>">
                    
                    <select name="work_group" class="select-control">
                        <option value="">-- ເລືອກກຸ່ມວຽກງານ --</option>
                        <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກການເມືອງແנວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືອງແנວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-ປກສ" <?php echo ($search_group == 'ກຸ່ມວຽກ ປກຊ-ປກສ') ? 'selected':''; ?>>ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສດຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ" <?php echo ($search_group == 'ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ') ? 'selected':''; ?>>ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <select name="dept_id" class="select-control">
                            <option value="">-- ເລືອກນະຄອນ/ເມືອງ ທັງໝົດ --</option>
                            <?php while($d = $depts_list->fetch_assoc()) {
                                $sel = ($search_dept == $d['id']) ? 'selected' : '';
                                echo "<option value='{$d['id']}' $sel>".htmlspecialchars($d['name'])."</option>";
                            } ?>
                        </select>
                    <?php endif; ?>

                    <button type="submit" class="btn-search">ຄົ້ນຫາຂໍ້ມູນ</button>
                    <?php if($search_group !== '' || $search_topic !== '' || $search_dept !== ''): ?>
                        <a href="compare_report.php" class="btn-clear">ລ້າງຄ່າ</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="report-card-grid">
                <?php 
                if ($topics_query && $topics_query->num_rows > 0):
                    while($t = $topics_query->fetch_assoc()):
                        $tid = $t['id'];

                        // ประมวลผลจำนวนยอดย้อนหลัง 1, 3, 6, 12 เดือน
                        $m1_old  = getPeriodAmount($conn, $tid, $prev_year, 1);
                        $m1_new  = getPeriodAmount($conn, $tid, $current_year, 1);
                        $m3_old  = getPeriodAmount($conn, $tid, $prev_year, 3);
                        $m3_new  = getPeriodAmount($conn, $tid, $current_year, 3);
                        $m6_old  = getPeriodAmount($conn, $tid, $prev_year, 6);
                        $m6_new  = getPeriodAmount($conn, $tid, $current_year, 6);
                        $m12_old = getPeriodAmount($conn, $tid, $prev_year, 12);
                        $m12_new = getPeriodAmount($conn, $tid, $current_year, 12);

                        // คำนวณร้อยละการเติบโต
                        $pct1  = calcPct($m1_old, $m1_new);
                        $pct3  = calcPct($m3_old, $m3_new);
                        $pct6  = calcPct($m6_old, $m6_new);
                        $pct12 = calcPct($m12_old, $m12_new);
                ?>
                        <div class="report-card">
                            <div class="card-header">
                                <div class="card-title-section">
                                    <div class="card-dept">📍 <?php echo htmlspecialchars($t['dept_name']); ?></div>
                                    <div class="card-topic-title"><?php echo htmlspecialchars($t['title']); ?></div>
                                </div>
                                <span class="card-group-badge" style="<?php echo getGroupBadgeStyle($t['work_group']); ?>">
                                    <?php echo htmlspecialchars($t['work_group']); ?>
                                </span>
                            </div>
                            
                            <div class="card-periods-grid">
                                <div class="period-sub-box">
                                    <div class="period-title">ສົມທຽບ 1 ເດືອນ (ມັງກອນ)</div>
                                    <div class="period-values">
                                        <div class="val-row"><span>ປີ <?php echo $prev_year; ?>:</span> <span><?php echo number_format($m1_old, 2); ?></span></div>
                                        <div class="val-row"><span style="color:#2563eb;"><b>ປີ <?php echo $current_year; ?>:</b></span> <span style="color:#2563eb;"><b><?php echo number_format($m1_new, 2); ?></b></span></div>
                                    </div>
                                    <div class="pct-row">ອັດຕາເຕີບໂຕ: <?php echo renderColorPct($pct1); ?></div>
                                </div>

                                <div class="period-sub-box">
                                    <div class="period-title">ສົມທຽບ 3 ເດືອນສະສົມ</div>
                                    <div class="period-values">
                                        <div class="val-row"><span>ປີ <?php echo $prev_year; ?>:</span> <span><?php echo number_format($m3_old, 2); ?></span></div>
                                        <div class="val-row"><span style="color:#2563eb;"><b>ປີ <?php echo $current_year; ?>:</b></span> <span style="color:#2563eb;"><b><?php echo number_format($m3_new, 2); ?></b></span></div>
                                    </div>
                                    <div class="pct-row">ອັດຕາເຕີບໂຕ: <?php echo renderColorPct($pct3); ?></div>
                                </div>

                                <div class="period-sub-box">
                                    <div class="period-title">ສົມທຽບ 6 ເດືອນສະສົມ</div>
                                    <div class="period-values">
                                        <div class="val-row"><span>ປີ <?php echo $prev_year; ?>:</span> <span><?php echo number_format($m6_old, 2); ?></span></div>
                                        <div class="val-row"><span style="color:#2563eb;"><b>ປີ <?php echo $current_year; ?>:</b></span> <span style="color:#2563eb;"><b><?php echo number_format($m6_new, 2); ?></b></span></div>
                                    </div>
                                    <div class="pct-row">ອັດຕາເຕີບໂຕ: <?php echo renderColorPct($pct6); ?></div>
                                </div>

                                <div class="period-sub-box">
                                    <div class="period-title">ສົມທຽບ 12 ເດືອນສະສົມ</div>
                                    <div class="period-values">
                                        <div class="val-row"><span>ປີ <?php echo $prev_year; ?>:</span> <span><?php echo number_format($m12_old, 2); ?></span></div>
                                        <div class="val-row"><span style="color:#2563eb;"><b>ປີ <?php echo $current_year; ?>:</b></span> <span style="color:#2563eb;"><b><?php echo number_format($m12_new, 2); ?></b></span></div>
                                    </div>
                                    <div class="pct-row">ອັດຕາເຕີບໂຕ: <?php echo renderColorPct($pct12); ?></div>
                                </div>
                            </div>
                        </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div style="background:#ffffff; padding:40px; text-align:center; border-radius:12px; color:#64748b; border:1px solid #e2e8f0;">
                        ບໍ່ມີຂໍ້ມູນສະຖິຕິໃນການສົມທຽບ
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>