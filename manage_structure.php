<?php
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$msg = '';

// ระบบบันทึกหัวข้อใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_topic'])) {
    $title = trim($conn->real_escape_string($_POST['title']));
    $department_id = intval($_POST['department_id']);
    $work_group = trim($conn->real_escape_string($_POST['work_group']));

    if (!empty($title) && $department_id > 0 && !empty($work_group)) {
        $stmt = $conn->prepare("INSERT INTO topics (title, department_id, work_group) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $title, $department_id, $work_group);
        if ($stmt->execute()) {
            $msg = "<div class='msg success'>บันทึกหัวข้อกิจกรรมใหม่สำเร็จแล้ว</div>";
        } else {
            $msg = "<div class='msg error'>เกิดข้อผิดพลาดในการบันทึกข้อมูล</div>";
        }
    }
}

$depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
$topics_list = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN departments d ON t.department_id = d.id ORDER BY t.id DESC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງຫົວຂໍ້</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 15px; color: #334155; }
        .container { max-width: 1000px; margin: auto; }
        .form-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        h2, h3 { margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        label { display: block; margin: 12px 0 6px 0; font-weight: bold; font-size: 14px; }
        input[type="text"], select { width: 100%; padding: 11px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; height: 44px; background: #fff; }
        button { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 15px; }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; text-align: center; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .table-responsive { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; }
        th, td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f8fafc; color: #64748b; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #334155; font-weight: bold; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .form-box { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>ຈັດການ ແລະ ເພີ່ມຫົວຂໍ້ກິດຈະກຳ</h2>
    <?php echo $msg; ?>

    <div class="form-box">
        <form method="POST">
            <label>ຊື່ຫົວຂໍ້ກິດຈະກຳ / ຫົວຂໍ້ຂ່າວ</label>
            <input type="text" name="title" placeholder="ປ້ອນຊື່ຫົວຂໍ້..." required>

            <label>ສັງກັດພະແนກ</label>
            <select name="department_id" required>
                <option value="">-- ເລືອກພະແນກ --</option>
                <?php while($d = $depts->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                <?php endwhile; ?>
            </select>

            <label>ເລືອກກຸ່ມວຽກ</label>
            <select name="work_group" required>
                <option value="ກຸ່ມວຽກການເມືອງແນວຄິດ">ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກິດ</option>
                <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
            </select>

            <button type="submit" name="add_topic">บันทึกหัวข้อ</button>
        </form>
    </div>

    <h3>ລາຍການຫົວຂໍ້ທັງໝົດໃນລະບົບ</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ຫົວຂໍ້</th>
                    <th>ພະແນກ</th>
                    <th>ກຸ່ມວຽກ</th>
                </tr>
            </thead>
            <tbody>
                <?php if($topics_list->num_rows > 0): ?>
                    <?php while($t = $topics_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['title']); ?></td>
                            <td><?php echo htmlspecialchars($t['dept_name']); ?></td>
                            <td><span style="font-weight: bold; color: #3182ce;"><?php echo htmlspecialchars($t['work_group'] ?? 'ກຸ່ມວຽກເສດຖະກິດ'); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #64748b;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="btn-back">ກັບ Dashboard</a>
</div>
</body>
</html>