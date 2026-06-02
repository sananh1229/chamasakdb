<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';

$where_topic = [];
if ($_SESSION['role'] == 'user') {
    $where_topic[] = "t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic[] = "t.department_id = $search_dept";
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
                        JOIN departments d ON t.department_id = d.id
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
        body { background: #f8fafc; margin: 0; color: #334155; }
        .wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; background: #ffffff; }
        
        .filter-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        
        .select-control, .btn-search, .btn-clear { padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; height: 42px; background: #fff; }
        .select-control { flex: 1; min-width: 180px; }
        .btn-search { background: #3182ce; color: #fff; border: none; font-weight: bold; cursor: pointer; min-width: 100px; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 80px; }
        
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th, td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        
        .btn-edit { background: #ecc94b; color: #1a202c; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 13px; margin-right: 5px; }
        .btn-del { background: #f56565; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 13px; }
        
        @media (max-width: 768px) {
            .wrapper { flex-direction: column; }
            .filter-form { flex-direction: column; align-items: stretch; }
            .select-control, .btn-search, .btn-clear { width: 100%; }
            table { min-width: 600px; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">ເມນູ</button>

    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນພະແນກ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item active"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <li class="sidebar-item"><a href="manage_structure.php">ຈັດການ ແລະ ເພີ່ມຫົວຂໍ້</a></li>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                    <a href="logout.php" style="background: #f56565; color: #fff; text-align: center;">ອອກຈາກລະບົບ</a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; gap: 10px;">
                <div>
                    <h2 style="margin: 0; border: none; padding: 0;">ລະບົບຖານຂໍ້ມູນສະຖິຕິພະແນກ</h2>
                    <p style="color: #64748b; margin: 4px 0 0 0; font-size: 14px;">ສະແດງຜົນຕາຕະລາງລວມແຍກຕາມກຸ່ມວຽກ ແລະ ພะແນກ</p>
                </div>
                <a href="export_excel.php?year=<?php echo $selected_year; ?>&dept_id=<?php echo $search_dept; ?>&work_group=<?php echo urlencode($search_group); ?>" style="background: #48bb78; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">ສົ່ງອອກ Excel</a>
            </div>

            <div class="filter-box">
                <form method="GET" class="filter-form">
                    <select name="year" class="select-control">
                        <?php
                        $current_y = intval(date('Y'));
                        for($y = $current_y; $y >= $current_y - 5; $y--) {
                            $sel = ($selected_year == $y) ? 'selected' : '';
                            echo "<option value='$y' $sel>ເບິ່ງຂໍ້ມູນປີ $y</option>";
                        }
                        ?>
                    </select>

                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <select name="dept_id" class="select-control">
                            <option value="">ທຸກພະແນກ</option>
                            <?php
                            $depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
                            while($d = $depts->fetch_assoc()) {
                                $sel = ($search_dept == $d['id']) ? 'selected' : '';
                                echo "<option value='{$d['id']}' $sel>".htmlspecialchars($d['name'])."</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>

                    <select name="work_group" class="select-control">
                        <option value="">ທຸກກຸ່ມວຽກ</option>
                        <option value="ກຸ່ມວຽກการເມືອງແנວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກການເມືອງແנວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືองແນວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-ປກສ" <?php echo ($search_group == 'ກຸ່ມວຽກ ປກຊ-ປກສ') ? 'selected':''; ?>>ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສดຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
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
                            <th>ພະແນກ</th>
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
                                    <td><span style="color: #3182ce; font-weight: bold;"><?php echo htmlspecialchars($ts['work_group'] ?? 'ກຸ່ມວຽກເສດຖะກິດ'); ?></span></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($ts['title']); ?></td>
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
                            <td colspan="4" style="text-align: left; padding: 15px;">ສະແດງຜົນຂໍ້ມູນປະຈຳປີ <?php echo $selected_year; ?> (ຂໍ້ມູນປີອື່ນໆ ຈະບໍ່ນຳມາບວກປົນ)</td>
                            <td colspan="3" style="text-align: right; padding: 15px; color: #2b6cb0;">
                                ຂໍ້ມູນປີນີ້ອັບເດດຫຼ້າສຸດວັນທີ: <?php echo $system_latest_update; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            menuToggle.innerText = sidebar.classList.contains('open') ? '✕ ປິດ' : '☰ ເມນູ';
        });
    </script>
</body>
</html>