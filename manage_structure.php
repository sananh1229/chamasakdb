<?php
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$msg = '';

// 🚀 ລະບົບປ້ອນບັນທຶກພະແນກ/ເມືອງໃໝ່ ເຂົ້າຕາຕະລາງ districts ຂອງແທ້
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_dept'])) {
    $dept_name = trim($conn->real_escape_string($_POST['dept_name']));
    if (!empty($dept_name)) {
        $stmt = $conn->prepare("INSERT INTO districts (name) VALUES (?)");
        $stmt->bind_param("s", $dept_name);
        if ($stmt->execute()) {
            $msg = "<div class='msg success'>✓ ບັນທຶກພະແນກ / ສັງກັດໃໝ່ສຳເລັດແລ້ວ</div>";
        } else {
            $msg = "<div class='msg error'>❌ ເກີດຂໍ້ຜິດພາດ ບໍ່ສາມາດເພີ່ມຂໍ້ມູນໄດ້</div>";
        }
    }
}

// 📝 ລະບົບປ້ອນບັນທຶກຫົວຂໍ້ກິດຈະກຳໃໝ່ ເຂົ້າຕາຕະລາງ topics ໂດຍຜູກກັບ district_id ຂອງແທ້
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_topic'])) {
    $title = trim($conn->real_escape_string($_POST['title']));
    $district_id = intval($_POST['district_id']);
    $work_group = trim($conn->real_escape_string($_POST['work_group']));

    if (!empty($title) && $district_id > 0 && !empty($work_group)) {
        $stmt = $conn->prepare("INSERT INTO topics (title, district_id, work_group) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $title, $district_id, $work_group);
        if ($stmt->execute()) {
            $msg = "<div class='msg success'>✓ ບັນທຶກຫົວຂໍ້ກິດຈະກຳໃໝ່ສຳເລັດແລ້ວ</div>";
        } else {
            $msg = "<div class='msg error'>❌ ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຫົວຂໍ້</div>";
        }
    }
}

$depts = $conn->query("SELECT * FROM districts ORDER BY id ASC");
$topics_list = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN districts d ON t.district_id = d.id ORDER BY d.id ASC, t.id ASC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງລະບົບ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #ffffff; margin: 0; color: #334155; }
        .wrapper { display: flex; min-height: 100vh; }
        
        /* 🌟 ເພີ່ມ Sidebar ເມນູຄືກັນກັບໜ້າອື່ນໆ ຕາມຄຳຂໍ */
        .sidebar { width: 260px; background: #f8fafc; border-right: 1px solid #cbd5e1; padding: 20px; flex-shrink: 0; }
        .sidebar-brand { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; padding-bottom: 15px; border-bottom: 2px solid #cbd5e1; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 8px; }
        .sidebar-item a { display: block; padding: 12px 15px; color: #475569; text-decoration: none; font-weight: 500; border-radius: 6px; }
        .sidebar-item a:hover { background: #e2e8f0; color: #0f172a; }
        .sidebar-item.active a { background: #2563eb; color: #ffffff; font-weight: 600; }

        /* Main Content Layout */
        .main-content { flex: 1; padding: 25px; background: #ffffff; width: 100%; overflow: hidden; }
        h2 { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .sub-t { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        
        .grid-container { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px; }
        @media (min-width: 992px) { .grid-container { grid-template-columns: 1fr 1fr; } }
        
        /* ລົບລູກຫຼິ້ນ Effect ເງົາ ແລະ Hover ອອກໃຫ້ລຽບງ່າຍ */
        .form-box { background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #cbd5e1; }
        h3 { margin-top: 0; font-size: 16px; font-weight: 700; color: #1e293b; padding-bottom: 10px; margin-bottom: 15px; border-bottom: 1px solid #cbd5e1; }
        
        label { display: block; margin: 12px 0 6px 0; font-weight: 600; font-size: 14px; color: #475569; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 4px; border: 1px solid #cbd5e1; height: 42px; font-size: 14px; background: #ffffff; color: #0f172a; }
        input:focus, select:focus { border-color: #2563eb; outline: none; }
        
        button { background: #2563eb; color: #fff; font-weight: 700; border: none; cursor: pointer; height: 42px; border-radius: 4px; font-size: 14px; width: 100%; margin-top: 15px; }
        button:hover { background: #1d4ed8; }
        
        .msg { padding: 12px; border-radius: 4px; text-align: center; font-weight: 600; margin-bottom: 25px; font-size: 14px; }
        .success { background: #dcfce3; color: #16a34a; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #ef4444; border: 1px solid #fecdd3; }
        
        /* ຕາຕະລາງທາງການແບບລຽບງ່າຍ ບໍ່ມີ Icon */
        .table-responsive { width: 100%; overflow-x: auto; border: 1px solid #64748b; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 14px; }
        th { background: #cbd5e1; color: #0f172a; font-weight: 700; text-align: left; }
        tr:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽบ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <li class="sidebar-item active"><a href="manage_structure.php">ຈັດການໂຄງສ້າງລະບົບ</a></li>
                <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ຈັດການໂຄງສ້າງພະແນກ ແລະ ຫົວຂໍ້ກິດຈະກຳ</h2>
            <div class="sub-t">ເພີ່ມລາຍການພະແนກສັງກັດໃໝ່ ແລະ ສ້າງຫົວຂໍ້ຕົວຊີ້ວັດເພື່ອໃຊ້ໃນການປ້ອນຂໍ້ມູນຜົນผลิต</div>
            
            <?php echo $msg; ?>

            <div class="grid-container">
                <div class="form-box">
                    <h3>ເພີ່ມພະແນກ / ສັງກັດ / ເມືອງ ໃໝ່</h3>
                    <form method="POST">
                        <label>ຊື່ພະແນກ ຫຼື ຊື່ເມືອງ</label>
                        <input type="text" name="dept_name" required placeholder="ຕົວຢ່າງ: ພະແນກການເມືອງແנວຄິດ, ເມືອງສຸຂຸມາ">
                        <button type="submit" name="add_dept">ບັນທຶກເພີ່ມພະແນກ</button>
                    </form>
                </div>

                <div class="form-box">
                    <h3>ເພີ່ມຫົວຂໍ້ກິດຈະກຳ / ຕົວຊີ້ວັດສະຖິຕິ</h3>
                    <form method="POST">
                        <label>ຊື່ຫົວຂໍ້ກິດຈະກຳ</label>
                        <input type="text" name="title" required placeholder="ຕົວຢ່າງ: ຈຳນວນເປີດຊຸດອົບຮົມການເມືອງ">

                        <label>ເລືອກພະແນກທີ່ຮັບຜິດຊອບ</label>
                        <select name="district_id" required>
                            <option value="">-- ກະລຸນາເລືອກພະແນກ --</option>
                            <?php if($depts) { $depts->data_seek(0); while($d = $depts->fetch_assoc()) { echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>"; } } ?>
                        </select>

                        <label>ແຍກເຂົ້າກຸ່ມວຽກງານ</label>
                        <select name="work_group" required>
                            <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ">ກຸ່ມວຽກການເມືອງແנວຄິດ</option>
                            <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                            <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກิต</option>
                            <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                        </select>
                        <button type="submit" name="add_topic">บันທຶກຫົວຂໍ້ກິດຈະກຳ</button>
                    </form>
                </div>
            </div>

            <h3 style="border:none; padding:0; margin-bottom:15px; font-size:16px; color:#0f172a;">ລາຍການຫົວຂໍ້ຕົວຊີ້ວັດທังໝົດໃນລະບົບ</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ພະແນກສັງກັດ / ເມືອງ</th>
                            <th>ກຸ່ມວຽກງານ</th>
                            <th>ຊື່ຫົວຂໍ້ກິດຈະກຳຕົວຊີ້ວັດສະຖິຕິ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($topics_list && $topics_list->num_rows > 0): ?>
                            <?php while($t = $topics_list->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['dept_name']); ?></strong></td>
                                    <td><span style="color: #2563eb; font-weight: bold;"><?php echo htmlspecialchars($t['work_group']); ?></span></td>
                                    <td style="color:#334155; font-weight:500;"><?php echo htmlspecialchars($t['title']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #64748b; padding: 30px;">ບໍ່ມີຂໍ້ມູນຫົວຂໍ້ໃນລະບົບ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>