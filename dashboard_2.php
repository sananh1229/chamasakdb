<?php
include 'db_2.php';
if (!isset($_SESSION['user_id'])) { header("Location: login_2.php"); exit(); }

$current_year = date('Y');
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';

// เงื่อนไขการจำกัดสิทธิ์ข้อมูลพะแนก
$where_topic = "";
if ($_SESSION['role'] == 'user') {
    $where_topic = "WHERE t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic = "WHERE t.department_id = $search_dept";
}

// คิวรีดึงข้อมูลตารางรวมหัวข้อ พร้อมสรุปจำนวนยอดรวม และดึงค่าเวลาอัปเดตล่าสุด
$topic_summary_query = "SELECT t.id as topic_id, t.title, d.name as dept_name, 
                        SUM(r.amount) as current_total,
                        MAX(r.created_at) as latest_update,
                        (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit
                        FROM topics t
                        JOIN departments d ON t.department_id = d.id
                        LEFT JOIN records r ON r.topic_id = t.id AND YEAR(r.record_date) = '$current_year'
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
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f8fafc; color: #334155; margin: 0; padding: 15px; }
        .container { max-width: 1200px; margin: auto; }
        header { display: flex; flex-direction: column; gap: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        @media (min-width: 768px) { header { flex-direction: row; justify-content: space-between; align-items: center; } }
        header h2 { margin: 0; color: #0f172a; font-size: 22px; }
        
        .filter-box { background: #ffffff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-form-wrap { display: flex; gap: 10px; flex-wrap: wrap; width: 100%; }
        select, input, .btn { padding: 10px 15px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        
        .btn-main { background: #3182ce; color: #fff; font-weight: bold; border: none; }
        .btn-main:hover { background: #2b6cb0; }
        .btn-success { background: #48bb78; color: #fff; font-weight: bold; border: none; }
        .btn-edit { background: #ecc94b; color: #1a202c; padding: 6px 12px; font-size: 13px; font-weight: bold; border-radius: 4px; text-decoration: none; margin-right: 5px; display: inline-block; }
        .btn-del { background: #f56565; color: #fff; padding: 6px 12px; font-size: 13px; font-weight: bold; border-radius: 4px; text-decoration: none; display: inline-block; }
        
        .table-responsive { overflow-x: auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        tr:hover { background: #f8fafc; }
        .last-row { background: #f1f5f9; font-weight: bold; color: #0f172a; border-top: 2px solid #cbd5e1; }
        h3 { margin-top: 30px; margin-bottom: 15px; color: #0f172a; border-left: 4px solid #3182ce; padding-left: 10px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h2>ລະບົບຈັດການຂໍ້ມູນພະແນກ</h2>
                <p style="margin:5px 0 0 0; color:#64748b;">ຜູ້ໃຊ້: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role']; ?>)</p>
            </div>
            <a href="logout_2.php" class="btn" style="background:#e53e3e; color:#fff; border:none; font-weight:bold;">ອອກຈາກລະບົບ</a>
        </header>

        <div style="margin-bottom: 25px;">
            <a href="compare_report_2.php" class="btn btn-main" style="padding: 12px 20px; font-size: 15px; display: inline-block;">📊 ກົດເຂົ້າໄປໜ້າສະຫຼຸບສົມທຽບ (1, 3, 6, 12 ເດືອນ / ປີ)</a>
        </div>

        <div class="filter-box">
            <form method="GET" class="filter-form-wrap">
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
                    <button type="submit" class="btn" style="background:#cbd5e1; font-weight:bold;">ຄົ້ນຫາ</button>
                <?php endif; ?>
                
                <a href="export_excel_2.php?dept_id=<?php echo $search_dept; ?>" class="btn btn-success">Export Excel</a>
                
                <!-- ปุ่มจัดการพะแนงและหัวข้อ (เปิดให้สิทธิ์ทั้ง Admin และ User ทั่วไปเข้าได้) -->
                <a href="manage_structure_2.php" class="btn" style="background:#64748b; color:#fff; border:none;">🛠️ ຈັດການ & ເພີ່ມຫົວຂໍ້ (Topic)</a>
                
                <a href="insert_data_2.php" class="btn" style="background:#3182ce; color:#fff; border:none;">+ ບັນທຶກຂໍ້ມູນໃໝ່</a>
            </form>
        </div>

        <h3>📋 ຕาຕະລາງລวມຍອດແຍກຕາມຫົວຂໍ້ (ປີ <?php echo $current_year; ?>)</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ພະແນກ</th>
                        <th>ຫົວຂໍ້ / ກິດຈະກຳ</th>
                        <th style="text-align: right;">ຍອດລວມທັງໝົດ</th>
                        <th>ຫົວໜ່ວຍ</th>
                        <th>ອັບເດດ/ແກ້ໄຂຫຼ້າສຸດ</th>
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
                                    <?php echo number_format($ts['current_total'] ?? 0, 0); ?>
                                </td>
                                <td><?php echo htmlspecialchars($ts['latest_unit'] ?? '-'); ?></td>
                                <td style="font-size: 13px; color: #64748b;">
                                    <?php echo !empty($ts['latest_update']) ? date('d-m-Y H:i:s', strtotime($ts['latest_update'])) : 'ບໍ່ມີການອັບເດດ'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="edit_record.php?topic_id=<?php echo $ts['topic_id']; ?>" class="btn-edit">ແກ້ໄຂ</a>
                                    <a href="delete_record.php?topic_id=<?php echo $ts['topic_id']; ?>" class="btn-del" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບຂໍ້ມູນທັງໝົດໃນຫົວຂໍ້ນີ້?')">ລົບ</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#64748b;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້</td></tr>
                    <?php endif; ?>
                    
                    <tr class="last-row">
                        <td colspan="3" style="text-align: left; padding: 15px;">📊 ຂໍ້ມູນທັງໝົດສະແດງຜົນສະເພາະປີປະຈຸບັນ</td>
                        <td colspan="3" style="text-align: right; padding: 15px; color: #2b6cb0; font-size: 14px;">
                            🕒 ຂໍ້ມູນນີ້ອັບເດດ ແລະ ແກ້ໄຂຫຼ້າສຸດວັນທີ: <span style="text-weight: bold; text-decoration: underline;"><?php echo $system_latest_update; ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>