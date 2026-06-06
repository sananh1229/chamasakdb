<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';

$user_dist_name = '';
if ($_SESSION['role'] == 'user') {
    $user_dist_id = intval($_SESSION['dept_id']);
    $dept_query = $conn->query("SELECT name FROM districts WHERE id = $user_dist_id");
    if ($dept_query && $dept_query->num_rows > 0) {
        $user_dist_name = $dept_query->fetch_assoc()['name'];
    }
}

$where_topic = [];
if ($_SESSION['role'] == 'user') {
    $where_topic[] = "t.district_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic[] = "t.district_id = $search_dept";
}

if ($search_group !== '') {
    $where_topic[] = "t.work_group = '$search_group'";
}

$where_clause = (count($where_topic) > 0) ? "WHERE " . implode(" AND ", $where_topic) : "";

$topic_summary_query = "SELECT t.id as topic_id, t.title, t.work_group, d.name as dept_name, 
                        SUM(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.amount ELSE 0 END) as current_total,
                        MAX(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.created_at ELSE NULL END) as latest_update,
                        (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit
                        FROM topics t
                        JOIN districts d ON t.district_id = d.id
                        LEFT JOIN records r ON r.topic_id = t.id
                        $where_clause
                        GROUP BY t.id, t.title, t.work_group, d.name
                        ORDER BY d.id ASC, t.id DESC";

$summary_result = $conn->query($topic_summary_query);
$system_latest_update = "-";
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໜ້າຫຼັກ Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; color: #334155; padding: 0; }
        .wrapper { display: flex; min-height: 100vh; position: relative; }
        .main-content { flex: 1; padding: 20px; background: #ffffff; width: 100%; overflow: hidden; }
        .filter-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 12px; width: 100%; }
        .select-control { flex: 1; min-width: 200px; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; height: 46px; background: #fff; color: #334155; }
        .btn-search { background: #3182ce; color: #fff; border: none; font-weight: bold; cursor: pointer; height: 46px; padding: 0 25px; border-radius: 8px; font-size: 14px; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 20px; border-radius: 8px; font-size: 14px; }
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 900px; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; white-space: nowrap; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        .btn-edit { background: #ecc94b; color: #1a202c; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-del { background: #f56565; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-block; }
        .user-dept-display { background: #ebf8ff; border: 1px solid #bee3f8; color: #2b6cb0; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        @media (max-width: 768px) {
            .wrapper { flex-direction: column; }
            .filter-form { flex-direction: column; align-items: stretch; gap: 10px; }
            .select-control { width: 100%; min-width: 100%; }
            .btn-search, .btn-clear { width: 100%; text-align: center; justify-content: center; }
            .header-section { flex-direction: column; align-items: stretch; gap: 15px; }
            .header-section a { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">ເມນູ</button>
    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item active"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
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

        <main class="main-content">
            <div class="header-section" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; margin-bottom: 25px; gap: 15px;">
                <div>
                    <h2 style="margin: 0; border: none; padding: 0; font-size: 22px; font-weight: 700;">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</h2>
                    <p style="color: #64748b; margin: 6px 0 0 0; font-size: 14px;">ສະແດງຜົນຕາຕະລางລວມແຍກຕາມກຸ່ມວຽກ ແລະ ນະຄອນ/ເມືອງ</p>
                </div>
                <a href="export_excel.php?year=<?php echo $selected_year; ?>&dept_id=<?php echo $search_dept; ?>&work_group=<?php echo urlencode($search_group); ?>" style="background: #48bb78; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; text-align: center;">ສົ່ງອອກ Excel</a>
            </div>

            <?php if ($_SESSION['role'] == 'user' && !empty($user_dist_name)): ?>
                <div class="user-dept-display">ນະຄອນ/ເມືອງຂອງທ່ານ: <?php echo htmlspecialchars($user_dist_name); ?></div>
            <?php endif; ?>

            <div class="filter-box">
                <form method="GET" class="filter-form">
                    <select name="year" class="select-control">
                        <?php
                        $current_y = intval(date('Y'));
                        for($y = $current_y; $y >= $current_y - 5; $y--) {
                            echo "<option value='$y' ".($selected_year == $y ? 'selected':'').">ເບິ່ງຂໍ້ມູນປີ $y</option>";
                        }
                        ?>
                    </select>

                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <select name="dept_id" class="select-control">
                            <option value="">ທຸກນະຄອນ/ເມືອງ</option>
                            <?php
                            $depts = $conn->query("SELECT * FROM districts ORDER BY id ASC");
                            while($d = $depts->fetch_assoc()) {
                                echo "<option value='{$d['id']}' ".($search_dept == $d['id'] ? 'selected':'').">".htmlspecialchars($d['name'])."</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>

                    <select name="work_group" class="select-control">
                        <option value="">ທຸກກຸ່ມວຽກ</option>
                        <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກການເມືອງແנວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກซ-ປກສ" <?php echo ($search_group == 'ກຸ່ມວຽກ ປกຊ-%E0%BB%9B%E0%BB%80%E0%BB%AA') ? 'selected':''; ?>>ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສດຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ" <?php echo ($search_group == 'ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ') ? 'selected':''; ?>>ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>

                    <button type="submit" class="btn-search">ຄົ້ນຫາ</button>
                    <?php if($search_dept !== '' || $search_group !== ''): ?>
                        <a href="dashboard.php" class="btn-clear">ລ້າງຄ່າ</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ນະຄອນ/ເມືອງ</th>
                            <th>ກຸ່ມວຽກ</th>
                            <th>ຫົວຂໍ້ກິດຈະກຳ</th>
                            <th style="text-align: right;">ຍອດລວມ</th>
                            <th style="text-align: center;">ຫົວໜ່ວຍ</th>
                            <th>ອັບເດດລ່າສຸດ</th>
                            <th style="text-align: center;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($summary_result && $summary_result->num_rows > 0): ?>
                            <?php while($ts = $summary_result->fetch_assoc()): 
                                if(!empty($ts['latest_update']) && ($ts['latest_update'] > $system_latest_update || $system_latest_update == "-")) {
                                    $system_latest_update = date('d-m-Y H:i:s', strtotime($ts['latest_update']));
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ts['dept_name']); ?></td>
                                    <td><span style="color: #3182ce; font-weight: bold;"><?php echo htmlspecialchars($ts['work_group']); ?></span></td>
                                    <td style="font-weight: 500; white-space: normal; min-width: 200px;"><?php echo htmlspecialchars($ts['title']); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #2f855a;"><?php echo number_format($ts['current_total'], 2); ?></td>
                                    <td style="text-align: center; font-weight: bold; color: #4a5568;"><?php echo htmlspecialchars($ts['latest_unit'] ?? 'ບໍ່ທັນລະບຸ'); ?></td>
                                    <td style="color: #64748b; font-size: 13px;">
                                        <?php echo !empty($ts['latest_update']) ? date('d-m-Y H:i:s', strtotime($ts['latest_update'])) : 'ບໍ່ມີຂໍ້ມູນໃນປີນີ້'; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="edit_record.php?topic_id=<?php echo $ts['topic_id']; ?>&year=<?php echo $selected_year; ?>" class="btn-edit">ແກ້ໄຂ</a>
                                        <a href="delete_record.php?topic_id=<?php echo $ts['topic_id']; ?>" class="btn-del" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບຂໍ້ມູນທັງໝົດໃນຫົວຂໍ້ນີ້?')">ລົບ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; color:#64748b;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້ກິດຈະກຳ</td></tr>
                        <?php endif; ?>
                        <tr style="background: #f1f5f9; font-weight: bold;">
                            <td colspan="4" style="text-align: left; padding: 15px;">ສະແດງຜົນຂໍ້ມູນປະຈຳປີ <?php echo $selected_year; ?></td>
                            <td colspan="3" style="text-align: right; padding: 15px; color: #2b6cb0;">ອັບເດดຫຼ້າສຸດວັນທີ: <?php echo $system_latest_update; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('menuToggle').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
            document.getElementById('menuToggle').innerText = sidebar.classList.contains('open') ? '✕ ປິດ' : '☰ ເມນູ';
        });
    </script>
</body>
</html>