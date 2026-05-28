<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$current_year = date('Y');
if ($current_year >= 2027) {
    $backup_file = 'backup_data_2026.sql';
    if (!file_exists($backup_file)) {
        $sql_backup = $conn->query("SELECT * FROM records WHERE YEAR(record_date) = 2026");
        $backup_content = "-- Backup Data 2026 \n";
        while($row = $sql_backup->fetch_assoc()){
            $backup_content .= "INSERT INTO records VALUES('{$row['id']}', '{$row['topic_id']}', '{$row['amount']}', '{$row['unit']}', '{$row['record_date']}', '{$row['created_at']}');\n";
        }
        file_put_contents($backup_file, $backup_content);
    }
}

$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$where_clause = "";
$where_topic = "";

if ($_SESSION['role'] == 'user') {
    $where_clause = "WHERE d.id = " . $_SESSION['dept_id'];
    $where_topic = "WHERE t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_clause = "WHERE d.id = $search_dept";
    $where_topic = "WHERE t.department_id = $search_dept";
}

$topic_summary_query = "SELECT t.title, d.name as dept_name, 
                        (SELECT SUM(r.amount) FROM records r WHERE r.topic_id = t.id AND YEAR(r.record_date) = '$current_year') as current_total,
                        (SELECT r.unit FROM records r WHERE r.topic_id = t.id ORDER BY r.id DESC LIMIT 1) as latest_unit
                        FROM topics t
                        JOIN departments d ON t.department_id = d.id
                        $where_topic";
$topic_summaries = $conn->query($topic_summary_query);

$query_string = "SELECT r.*, t.title, d.name as dept_name 
                 FROM records r 
                 JOIN topics t ON r.topic_id = t.id 
                 JOIN departments d ON t.department_id = d.id 
                 $where_clause ORDER BY r.record_date DESC";
$records = $conn->query($query_string);
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
        @media (min-width: 768px) {
            header { flex-direction: row; justify-content: space-between; align-items: center; }
        }
        header h2 { margin: 0; color: #0f172a; font-size: 22px; }
        header p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
        
        .grid-topics { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .card-topic { background: #ffffff; border-left: 4px solid #3182ce; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 1px solid #edf2f7; border-right: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; }
        .card-topic small { color: #64748b; font-size: 12px; font-weight: 500; }
        .card-topic h4 { margin: 5px 0; font-size: 16px; color: #1e293b; }
        .card-topic .total { font-size: 20px; font-weight: bold; color: #2f855a; margin-top: 5px; }

        .filter-box { background: #ffffff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-form-wrap { display: flex; gap: 10px; flex-wrap: wrap; width: 100%; }
        select, input, .btn { padding: 10px 15px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        select:focus, input:focus { border-color: #3182ce; outline: none; }
        
        .btn-main { background: #3182ce; color: #fff; font-weight: bold; border: none; text-align: center; width: 100%; }
        @media (min-width: 576px) { .btn-main { width: auto; } }
        .btn-main:hover { background: #2b6cb0; }
        .btn-success { background: #48bb78; color: #fff; font-weight: bold; border: none; }
        .btn-success:hover { background: #38a169; }
        
        .table-responsive { overflow-x: auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        tr:hover { background: #f8fafc; }
        h3 { margin-top: 30px; margin-bottom: 15px; color: #0f172a; border-left: 4px solid #3182ce; padding-left: 10px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h2>ລະບົບຈັດການຂໍ້ມູນພະແນກ</h2>
                <p>ຜູ້ໃຊ້: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role']; ?>)</p>
            </div>
            <a href="logout.php" class="btn" style="background:#e53e3e; color:#fff; border:none; font-weight:bold;">ອອກຈາກລະບົບ</a>
        </header>

        <div style="margin-bottom: 25px;">
            <a href="compare_report.php" class="btn btn-main" style="padding: 12px 20px; font-size: 15px; display: inline-block;">📊 ກົດເຂົ້າໄປໜ້າສະຫຼຸບສົມທຽບ (1, 3, 6, 12 ເດືອນ / ປີ)</a>
        </div>

        <h3>📋 ສະຫຼຸບຍອດລວມແຍກຕາມແຕ່ລະຫົວຂໍ້ (Topic) ປະຈຳປີ <?php echo $current_year; ?></h3>
        <div class="grid-topics">
            <?php if($topic_summaries->num_rows > 0): ?>
                <?php while($ts = $topic_summaries->fetch_assoc()): ?>
                    <div class="card-topic">
                        <small>[<?php echo $ts['dept_name']; ?>]</small>
                        <h4><?php echo $ts['title']; ?></h4>
                        <div class="total">
                            <?php 
                                $tot = $ts['current_total'] ?? 0;
                                $unit_name = $ts['latest_unit'] ?? '';
                                echo number_format($tot, 0) . " " . $unit_name; 
                            ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding: 10px; color:#64748b;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້</p>
            <?php endif; ?>
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
                    <button type="submit" class="btn" style="background:#e2e8f0; font-weight:bold;">ຄົ້ນຫາ</button>
                <?php endif; ?>
                <a href="export_excel.php?dept_id=<?php echo $search_dept; ?>" class="btn btn-success">Export Excel</a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="manage_structure.php" class="btn" style="background:#64748b; color:#fff; border:none;">ຈັດການພະແນກ & ຫົວຂໍ້</a>
                <?php endif; ?>
                <a href="insert_data.php" class="btn" style="background:#3182ce; color:#fff; border:none;">+ ບັນທຶກຂໍ້ມູນໃໝ່</a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="add_user.php" class="btn" style="background:#9f7aea; color:#fff; border:none;">+ ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a>
                <?php endif; ?>
            </form>
        </div>

        <h3>🕒 ປະຫວັດລາຍການບັນທຶກຂໍ້ມູນທັງໝົດ</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ພະແນກ</th>
                        <th>ຫົວຂໍ້/ກິດຈະກຳ</th>
                        <th>ຈຳນວນ/ຜົນຜະລິດ</th>
                        <th>ວັນທີບັນທຶກ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($records->num_rows > 0): ?>
                        <?php while($row = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['dept_name']; ?></td>
                                <td><?php echo $row['title']; ?></td>
                                <td><?php echo number_format($row['amount'], 0) . " " . $row['unit']; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['record_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b;">ບໍ່ມີຂໍ້ມູນ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>