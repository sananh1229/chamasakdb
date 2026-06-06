<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_group = isset($_GET['work_group']) ? $conn->real_escape_string(trim($_GET['work_group'])) : '';
$search_title = isset($_GET['search_title']) ? $conn->real_escape_string(trim($_GET['search_title'])) : '';
$search_dist = isset($_GET['district_id']) ? intval($_GET['district_id']) : '';

$user_role = $_SESSION['role'];
$user_dist_id = intval($_SESSION['dept_id']); 

// ດຶງຊື່ເມືອງກໍລະນີເປັນ User ທົ່ວໄປ
$user_dist_name = '';
if ($user_role == 'user') {
    $d_q = $conn->query("SELECT name FROM districts WHERE id = $user_dist_id");
    if($d_q && $d_q->num_rows > 0) { $user_dist_name = $d_q->fetch_assoc()['name']; }
}

// ສ້າງເງື່ອນໄຂຄົ້ນຫາ (Filter Conditions)
$where_prov = [];
$where_dist = [];

if ($search_group !== '') {
    $where_prov[] = "t.work_group = '$search_group'";
    $where_dist[] = "t.work_group = '$search_group'";
}
if ($search_title !== '') {
    $where_prov[] = "t.title LIKE '%$search_title%'";
    $where_dist[] = "t.title LIKE '%$search_title%'";
}

if ($user_role === 'user') {
    $where_dist[] = "t.district_id = $user_dist_id";
} elseif ($search_dist !== '') {
    $where_dist[] = "t.district_id = $search_dist";
}

$where_prov_clause = (count($where_prov) > 0) ? "WHERE " . implode(" AND ", $where_prov) : "";
$where_dist_clause = (count($where_dist) > 0) ? "WHERE " . implode(" AND ", $where_dist) : "";

/* ==========================================================================
   1. ຄິວຣີດຶງຂໍ້ມູນ "ຍອດລວມທົ່ວແຂວງ" (ສະເພາະ Admin ເທົ່ານັ້ນ)
   ========================================================================== */
$prov_result = null;
if ($user_role === 'admin') {
    $prov_query = "SELECT t.title, 
                   SUM(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.amount ELSE 0 END) as total_amount,
                   (SELECT r2.unit FROM records r2 JOIN topics t2 ON r2.topic_id = t2.id WHERE t2.title = t.title ORDER BY r2.id DESC LIMIT 1) as latest_unit,
                   MAX(r.created_at) as last_update
                   FROM topics t
                   LEFT JOIN records r ON r.topic_id = t.id
                   $where_prov_clause
                   GROUP BY t.title
                   ORDER BY t.title ASC";
    $prov_result = $conn->query($prov_query);
}

/* ==========================================================================
   2. ຄິວຣີດຶງຂໍ້ມູນ "ລາຍລະອຽດນະຄອນ/ເມືອງ" (Admin ເຫັນທຸກເມືອງ / User ເຫັນສະເພາະເມືອງຕົນເອງ)
   ========================================================================== */
$dist_query = "SELECT d.name as dist_name, t.title, 
               SUM(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.amount ELSE 0 END) as total_amount,
               (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit,
               MAX(r.created_at) as last_update
               FROM topics t
               JOIN districts d ON t.district_id = d.id
               LEFT JOIN records r ON r.topic_id = t.id
               $where_dist_clause
               GROUP BY d.id, t.title
               ORDER BY d.id ASC, t.title ASC";
$dist_result = $conn->query($dist_query);

/* ==========================================================================
   3. ລະບົບສົ່ງອອກເປັນ Excel (Export to Excel) ຕາມໂຄງສ້າງເດີມ
   ========================================================================== */
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=summary_report_$selected_year.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo '<meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
    ?>
    <table border="1">
        <?php if ($user_role === 'admin'): ?>
            <tr><th colspan="3" style="background-color: #3182ce; color: #ffffff; font-weight: bold; text-align: center;">1. ຍອດລວມສະສົມທົ່ວແຂວງ ປະຈຳປີ <?php echo $selected_year; ?></th></tr>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <th>ຫົວຂໍ້ກິດຈະກຳ</th>
                <th>ຍອດລວມທົ່ວແຂວງ</th>
                <th>ຫົວໜ່ວຍ</th>
            </tr>
            <?php 
            if($prov_result && $prov_result->num_rows > 0) {
                $prov_result->data_seek(0);
                while($row = $prov_result->fetch_assoc()) {
                    echo "<tr>
                            <td>".htmlspecialchars($row['title'])."</td>
                            <td align='right'>".number_format($row['total_amount'], 2)."</td>
                            <td align='center'>".htmlspecialchars($row['latest_unit'] ?? 'ບໍ່ລະບຸ')."</td>
                          </tr>";
                }
            }
            ?>
            <tr><td colspan="3" style="border: none; height: 20px;"></td></tr>
        <?php endif; ?>
        
        <tr><th colspan="4" style="background-color: #4a5568; color: #ffffff; font-weight: bold; text-align: center;">2. ຍອດລວມສະສົມແຍກຕາມ ນະຄອນ/ເມືອງ ປະຈຳປີ <?php echo $selected_year; ?></th></tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <th>ນະຄອນ/ເມືອງ</th>
            <th>ຫົວຂໍ້ກິດຈະກຳ</th>
            <th>ຍອດລວມສະສົມ</th>
            <th>ຫົວໜ່ວຍ</th>
        </tr>
        <?php 
        if($dist_result && $dist_result->num_rows > 0) {
            $dist_result->data_seek(0);
            while($row = $dist_result->fetch_assoc()) {
                echo "<tr>
                        <td>".htmlspecialchars($row['dist_name'])."</td>
                        <td>".htmlspecialchars($row['title'])."</td>
                        <td align='right'>".number_format($row['total_amount'], 2)."</td>
                        <td align='center'>".htmlspecialchars($row['latest_unit'] ?? 'ບໍ່ລະບຸ')."</td>
                      </tr>";
            }
        }
        ?>
    </table>
    <?php
    exit();
}

$system_latest_update = "-";
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານສະຫຼຸບຍອດລວມສະສົມ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; color: #334155; padding: 0; }
        .wrapper { display: flex; min-height: 100vh; position: relative; }
        .main-content { flex: 1; padding: 20px; background: #ffffff; width: 100%; overflow: hidden; }
        
        .filter-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 12px; width: 100%; align-items: center; }
        .input-control, .select-control { flex: 1; min-width: 180px; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; height: 46px; background: #fff; color: #334155; }
        .btn-search { background: #3182ce; color: #fff; border: none; font-weight: bold; cursor: pointer; height: 46px; padding: 0 25px; border-radius: 8px; font-size: 14px; }
        .btn-clear { background: #64748b; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 20px; border-radius: 8px; font-size: 14px; }
        
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; -webkit-overflow-scrolling: touch; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; white-space: nowrap; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        
        .province-row { background: #eff6ff !important; font-weight: 500; }
        .province-header { background: #3182ce !important; color: #ffffff !important; }
        .section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin: 25px 0 12px 0; padding-left: 5px; border-left: 4px solid #3182ce; }
        
        .user-mode-alert { background: #ebf8ff; border: 1px solid #bee3f8; color: #2b6cb0; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        .btn-excel { background: #48bb78; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; height: 42px; line-height: 22px; }
        
        @media (max-width: 768px) {
            .wrapper { flex-direction: column; }
            .header-section { flex-direction: column; align-items: stretch; gap: 15px; }
            .header-section .btn-excel { width: 100%; text-align: center; }
            .filter-form { flex-direction: column; align-items: stretch; gap: 10px; }
            .input-control, .select-control { max-width: 100%; width: 100%; }
            .btn-search, .btn-clear { width: 100%; text-align: center; justify-content: center; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">ເມນູ</button>
    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item active"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <?php if($user_role == 'admin'): ?>
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
                    <h2 style="margin: 0; border: none; padding: 0; font-size: 22px; font-weight: 700;">ລາຍງານສະຫຼຸບຍອດລວມສະສົມຂໍ້ມູນ</h2>
                    <p style="color: #64748b; margin: 6px 0 0 0; font-size: 14px;">
                        <?php echo ($user_role === 'admin') ? 'ສະແດງຍອດລວມທົ່ວແխວງຢູ່ເທິງສຸດ ແລະ ຂໍ້ມູນແຍກລາຍ ນະຄອນ/ເມືອງ ຢູ່ດ້ານລຸ່ມ' : 'ສະແດງຍອດລວມສະສົມຂໍ້ມູນພາຍໃນນະຄອນ/ເມືອງຂອງທ່ານ'; ?>
                    </p>
                </div>
                <a href="province_summary.php?year=<?php echo $selected_year; ?>&work_group=<?php echo urlencode($search_group); ?>&search_title=<?php echo urlencode($search_title); ?>&district_id=<?php echo $search_dist; ?>&export=excel" class="btn-excel">ສົ່ງອອກ Excel</a>
            </div>

            <?php if ($user_role == 'user'): ?>
                <div class="user-mode-alert">ນະຄອນ/ເມືອງຂອງທ່ານ: <?php echo htmlspecialchars($user_dist_name); ?></div>
            <?php endif; ?>

            <div class="filter-box">
                <form method="GET" class="filter-form">
                    <select name="year" class="select-control">
                        <?php
                        $cy = intval(date('Y'));
                        for($y = $cy; $y >= $cy - 5; $y--) {
                            $sel = ($selected_year == $y) ? 'selected' : '';
                            echo "<option value='$y' $sel>ເບິ່ງຂໍ້ມູນສະສົມປີ $y</option>";
                        }
                        ?>
                    </select>

                    <input type="text" name="search_title" class="input-control" placeholder="ຄົ້ນຫາຫົວຂໍ້ກິດຈະກຳ..." value="<?php echo htmlspecialchars($search_title); ?>">

                    <select name="work_group" class="select-control">
                        <option value="">-- ເລືອກຄົ້ນຫາຕາມກຸ່ມວຽກ --</option>
                        <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ" <?php echo ($search_group == 'ກຸ່ມວຽກການເມືອງແנວຄິດ') ? 'selected':''; ?>>ກຸ່ມວຽກການເມືອງແנວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-ປກສ" <?php echo ($search_group == 'ກຸ່ມວຽກ ປກຊ-ປກສ') ? 'selected':''; ?>>ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ" <?php echo ($search_group == 'ກຸ່ມວຽກເສດຖະກິດ') ? 'selected':''; ?>>ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ" <?php echo ($search_group == 'ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ') ? 'selected':''; ?>>ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>

                    <?php if ($user_role === 'admin'): ?>
                        <select name="district_id" class="select-control">
                            <option value="">-- ເລືອກນະຄອນ/ເມືອງ --</option>
                            <?php 
                            $dist_list = $conn->query("SELECT * FROM districts ORDER BY id ASC");
                            while($d = $dist_list->fetch_assoc()) {
                                $sel = ($search_dist == $d['id']) ? 'selected' : '';
                                echo "<option value='{$d['id']}' $sel>".htmlspecialchars($d['name'])."</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>

                    <button type="submit" class="btn-search">ຄົ້ນຫາ</button>
                    <?php if($search_group !== '' || $search_title !== '' || $search_dist !== ''): ?>
                        <a href="province_summary.php" class="btn-clear">ລ້າງຄ່າ</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($user_role === 'admin'): ?>
                <div class="section-title">1. ຍອດລວມສະສົມທົ່ວແຂວງ</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="province-header">ຫົວຂໍ້ກິດຈະກຳ</th>
                                <th class="province-header" style="text-align: right;">ຍອດລວມທົ່ວແຂວງ</th>
                                <th class="province-header" style="text-align: center;">ຫົວໜ່ວຍ</th>
                                <th class="province-header">ອັບເດດຫຼ້າສຸດ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($prov_result && $prov_result->num_rows > 0): ?>
                                <?php while($row = $prov_result->fetch_assoc()): ?>
                                    <tr class="province-row">
                                        <td style="font-weight: bold; color: #1e293b; white-space: normal;"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="text-align: right; font-weight: bold; color: #1d4ed8; font-size: 15px;"><?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td style="text-align: center; color: #1e293b; font-weight: bold;"><?php echo htmlspecialchars($row['latest_unit'] ?? 'ບໍ່ລະບຸ'); ?></td>
                                        <td style="color: #4b5563; font-size: 13px;">
                                            <?php echo !empty($row['last_update']) ? date('d-m-Y H:i:s', strtotime($row['last_update'])) : 'ບໍ່ມີຂໍ້ມູນ'; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #64748b;">ບໍ່ມີຂໍ້ມູນຍອດລວມແຂວງ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="section-title">2. ຍອດລວມສະສົມແຍກຕາມ ນະຄອນ/ເມືອງ</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ນະຄອນ/ເມືອງ</th>
                            <th>ຫົວຂໍ້ກິດຈະກຳ</th>
                            <th style="text-align: right;">ຍອດລວມສະສົມ</th>
                            <th style="text-align: center;">ຫົວໜ່ວຍ</th>
                            <th>ອັບເດດຫຼ້າສຸດ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($dist_result && $dist_result->num_rows > 0): ?>
                            <?php while($row = $dist_result->fetch_assoc()): 
                                if(!empty($row['last_update']) && ($row['last_update'] > $system_latest_update || $system_latest_update == "-")) {
                                    $system_latest_update = date('d-m-Y H:i:s', strtotime($row['last_update']));
                                }
                            ?>
                                <tr>
                                    <td style="font-weight: 700; color: #334155;"><?php echo htmlspecialchars($row['dist_name']); ?></td>
                                    <td style="font-weight: 500; white-space: normal;"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #2f855a;"><?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td style="text-align: center; color: #4a5568; font-weight: bold;"><?php echo htmlspecialchars($row['latest_unit'] ?? 'ບໍ່ລະບຸ'); ?></td>
                                    <td style="color: #64748b; font-size: 13px;">
                                        <?php echo !empty($row['last_update']) ? date('d-m-Y H:i:s', strtotime($row['last_update'])) : 'ບໍ່ມີຂໍ້ມູນ'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b;">ບໍ່ມີຂໍ້ມູນສະຖິຕິໃນສ່ວນນີ້</td></tr>
                        <?php endif; ?>
                        
                        <tr style="background: #f1f5f9; font-weight: bold;">
                            <td colspan="3" style="text-align: left; padding: 15px;">ສະຫຼຸບຍອດສະສົມລายປີປະຈຳປີ <?php echo $selected_year; ?></td>
                            <td colspan="2" style="text-align: right; padding: 15px; color: #2b6cb0;">ອັບເດດລ່າສຸດວັນທີ: <?php echo $system_latest_update; ?></td>
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