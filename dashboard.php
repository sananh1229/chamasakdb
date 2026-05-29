<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';

$where_topic = "";
if ($_SESSION['role'] == 'user') {
    $where_topic = "WHERE t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic = "WHERE t.department_id = $search_dept";
}

$topic_summary_query = "SELECT t.id as topic_id, t.title, d.name as dept_name, 
                        SUM(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.amount ELSE 0 END) as current_total,
                        MAX(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.created_at ELSE NULL END) as latest_update,
                        (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit
                        FROM topics t
                        JOIN departments d ON t.department_id = d.id
                        LEFT JOIN records r ON r.topic_id = t.id
                        $where_topic
                        GROUP BY t.id, t.title, d.name";
$topic_summaries = $conn->query($topic_summary_query);

$system_latest_update = "-";
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ລະບົບຖານຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h2>ລະບົບຈັດການຂໍ້ມູນພະແນກ</h2>
                <p style="margin:5px 0 0 0; color:#64748b;">ຜູ້ໃຊ້: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role']; ?>)</p>
            </div>
            <a href="logout.php" class="btn" style="background:#e53e3e; color:#fff; border:none; font-weight:bold; width: auto;">ອອກຈາກລະບົບ</a>
        </header>

        <div style="margin-bottom: 25px;">
            <a href="compare_report.php" class="btn btn-main" style="padding: 12px 20px; font-size: 15px; display: inline-block; width: auto;">📊 ກົດເຂົ້າໄປໜ້າສະຫຼຸບສົມທຽບ</a>
        </div>

        <div class="filter-box">
            <form method="GET" class="filter-form-wrap">
                <select name="year" style="font-weight: bold; border-color: #3182ce; min-width: 120px;">
                    <option value="2025" <?php if($selected_year == 2025) echo 'selected'; ?>>ປີ 2025</option>
                    <option value="2026" <?php if($selected_year == 2026) echo 'selected'; ?>>ປີ 2026</option>
                    <option value="2027" <?php if($selected_year == 2027) echo 'selected'; ?>>ປີ 2027</option>
                </select>

                <?php if($_SESSION['role'] == 'admin'): ?>
                    <select name="dept_id" style="flex: 1; min-width: 200px;">
                        <option value="">-- ເລືອກທຸກພະແນກ --</option>
                        <?php
                        $depts = $conn->query("SELECT * FROM departments");
                        while($d = $depts->fetch_assoc()) {
                            $selected = ($search_dept == $d['id']) ? 'selected' : '';
                            echo "<option value='{$d['id']}' $selected>{$d['name']}</option>";
                        }
                        ?>
                    </select>
                <?php endif; ?>

                <button type="submit" class="btn" style="background:#cbd5e1; font-weight:bold;">🔍 ເລືອກມຸມມອງປີ</button>
                
                <a href="export_excel.php?dept_id=<?php echo $search_dept; ?>&year=<?php echo $selected_year; ?>" class="btn btn-success">Export Excel</a>
                <a href="manage_structure.php" class="btn" style="background:#64748b; color:#fff; border:none;">ຈັດການ & ເພີ່ມຫົວຂໍ້</a>
                <a href="insert_data.php" class="btn" style="background:#3182ce; color:#fff; border:none;">+ ບັນທຶກຂໍ້ມູນໃໝ່</a>
                
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="add_user.php" class="btn" style="background:#9f7aea; color:#fff; border:none; font-weight:bold;">+ ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a>
                <?php endif; ?>
            </form>
        </div>

        <h3>📋 ຕາຕະລາງລວມຍອດແຍກຕາມຫົວຂໍ້ (ມຸມມອງປະຈຳປີ: <?php echo $selected_year; ?>)</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ພະແນກ</th>
                        <th>ຫົວຂໍ້ / ກິດຈະກຳ</th>
                        <th style="text-align: right;">ຍອດລວມທັງໝົດ</th>
                        <th>ຫົວໜ່ວຍ (Unit)</th>
                        <th>ອັບເດດ/ແກ້ໄຂລ່າສຸດ</th>
                        <th style="text-align: center;">ຈັດການຂໍ້ມູນ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($topic_summaries && $topic_summaries->num_rows > 0): ?>
                        <?php while($ts = $topic_summaries->fetch_assoc()): 
                            if(!empty($ts['latest_update']) && ($ts['latest_update'] > $system_latest_update || $system_latest_update == "-")) {
                                $system_latest_update = date('d-m-Y H:i:s', strtotime($ts['latest_update']));
                            }
                        ?>
                            <tr>
                                <td><?php echo $ts['dept_name']; ?></td>
                                <td style="font-weight: 500; color: #1e293b;"><?php echo $ts['title']; ?></td>
                                <td style="text-align: right; font-weight: bold; color: #2f855a;">
                                    <?php echo number_format($ts['current_total'] ?? 0, 2); ?>
                                </td>
                                <td style="font-weight: bold; color: #4a5568;">
                                    <?php echo htmlspecialchars($ts['latest_unit'] ?? 'ບໍ່ທັນລະບຸ'); ?>
                                </td>
                                <td style="font-size: 13px; color: #64748b;">
                                    <?php echo !empty($ts['latest_update']) ? date('d-m-Y H:i:s', strtotime($ts['latest_update'])) : 'ບໍ່ມີຂໍ້ມູນໃນປີນີ້'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="edit_record.php?topic_id=<?php echo $ts['topic_id']; ?>&year=<?php echo $selected_year; ?>" class="btn-edit">ແກ້ໄຂ</a>
                                    <a href="delete_record.php?topic_id=<?php echo $ts['topic_id']; ?>" class="btn-del" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບຂໍ້ມູນທັງໝົດໃນຫົວຂໍ້ນີ້?')">ລົບ</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#64748b;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້</td></tr>
                    <?php endif; ?>
                    
                    <tr class="last-row">
                        <td colspan="3" style="text-align: left; padding: 15px;">📊 ກຳລັງສະແດງຜົນຂໍ້ມູນປີ <?php echo $selected_year; ?></td>
                        <td colspan="3" style="text-align: right; padding: 15px; color: #2b6cb0; font-size: 14px;">
                            🕒 ຂໍ້ມູນປີນີ້ອັບເດດລ່າສຸດວັນທີ: <span><?php echo $system_latest_update; ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>