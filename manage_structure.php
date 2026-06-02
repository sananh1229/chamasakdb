<?php
include 'db.php';

// ກວດສອບການເຂົ້າສູ່ລະບົບ (ໃຫ້ເຂົ້າໄດ້ທັງ admin ແລະ user)
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$msg = '';
$user_role = $_SESSION['role'];
$user_dept_id = intval($_SESSION['dept_id']);

/* =========================================
   1. ລະບົບປະມວນຜົນການບັນທຶກພະແນກ & ລົບພະແນກ (ສະເພາະ Admin ເທົ່ານັ້ນ)
   ========================================= */
if ($user_role === 'admin') {
    // ເພີ່ມພະແນກໃໝ່
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_dept'])) {
        $dept_name = trim($conn->real_escape_string($_POST['dept_name']));
        if (!empty($dept_name)) {
            $check_dept = $conn->query("SELECT id FROM departments WHERE name = '$dept_name'");
            if ($check_dept->num_rows > 0) {
                $msg = "<div class='msg error'>ຊື່ພະແນກນີ້ມີໃນລະບົບແລ້ວ ກະລຸນາປ່ຽນໃໝ່</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
                $stmt->bind_param("s", $dept_name);
                if ($stmt->execute()) {
                    $msg = "<div class='msg success'> \%)$. ບັນທຶກພະແນກໃໝ່ສຳເລັດແລ້ວ</div>";
                } else {
                    $msg = "<div class='msg error'>ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ</div>";
                }
            }
        }
    }

    // ລົບພະແນກ
    if (isset($_GET['delete_dept_id'])) {
        $del_dept_id = intval($_GET['delete_dept_id']);
        $check_relation = $conn->query("SELECT id FROM topics WHERE department_id = $del_dept_id");
        if ($check_relation->num_rows > 0) {
            $msg = "<div class='msg error'>ບໍ່ສາມາດລົບໄດ້ ເນື່ອງຈາກມີຫົວຂໍ້ກິດຈະກຳຜูກກັບພະແນກນີ້ຢູ່</div>";
        } else {
            $conn->query("DELETE FROM departments WHERE id = $del_dept_id");
            $msg = "<div class='msg success'>ລົບພະແນກອອກຈາກລະບົບສຳເລັດແລ້ວ</div>";
        }
    }
}

/* =========================================
   2. ລະບົບບັນທຶກຫົວຂໍ້ກິດຈະກຳໃໝ່ (ໃຫ້ທັງ Admin ແລະ User ບັນທຶກໄດ້)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_topic'])) {
    $title = trim($conn->real_escape_string($_POST['title']));
    $work_group = trim($conn->real_escape_string($_POST['work_group']));
    
    // ຖ້າເປັນ User ໃຫ້ດຶງພະແນກຂອງຕົນເອງອັດຕະໂนມັດ ຖ້າເປັນ Admin ໃຫ້ດຶງຈາກຟອມ Dropdown
    $department_id = ($user_role === 'admin') ? intval($_POST['department_id']) : $user_dept_id;

    if (!empty($title) && $department_id > 0 && !empty($work_group)) {
        $stmt = $conn->prepare("INSERT INTO topics (title, department_id, work_group) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $title, $department_id, $work_group);
        if ($stmt->execute()) {
            $msg = "<div class='msg success'> \%)$. ບັນທຶກຫົວຂໍ້ກິດຈະກຳໃໝ່ສຳເລັດແລ້ວ</div>";
        } else {
            $msg = "<div class='msg error'>ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຂໍ້ມູນ</div>";
        }
    }
}

/* =========================================
   3. ເງື່ອນໄຂການດຶງຂໍ້ມູນມາສະແດງໃນຕາຕະລາງ
   ========================================= */
if ($user_role === 'admin') {
    $depts_dropdown = $conn->query("SELECT * FROM departments ORDER BY id ASC");
    $depts_list = $conn->query("SELECT * FROM departments ORDER BY id DESC");
    $topics_list = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN departments d ON t.department_id = d.id ORDER BY t.id DESC");
} else {
    $topics_list = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dept_name FROM topics t JOIN departments d ON t.department_id = d.id WHERE t.department_id = $user_dept_id ORDER BY t.id DESC");
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງລະບົບ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 15px; color: #334155; }
        .container { max-width: 1100px; margin: auto; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 768px) { .grid-layout { grid-template-columns: <?php echo ($user_role === 'admin') ? '1fr 1fr' : '1fr'; ?>; } }
        
        .form-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        h2, h3 { margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-weight: 700; }
        
        label { display: block; margin: 12px 0 6px 0; font-weight: bold; font-size: 14px; color: #475569; }
        input[type="text"], select { width: 100%; padding: 11px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; height: 44px; background: #fff; color: #334155; }
        
        button { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 15px; }
        button:hover { background: #2b6cb0; }
        
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; text-align: center; font-size: 14px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        
        .table-responsive { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 450px; }
        th, td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f8fafc; color: #64748b; font-weight: 700; }
        
        .btn-del { background: #f56565; color: #fff; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-del:hover { background: #e53e3e; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #334155; font-weight: bold; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .form-box { padding: 15px; }
            input[type="text"], select, button { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>ຈັດການໂຄງສ້າງລະບົບ (ຫົວຂໍ້ກິດຈະກຳ)</h2>
    <?php echo $msg; ?>

    <div class="grid-layout">
        
        <?php if ($user_role === 'admin'): ?>
        <div>
            <div class="form-box">
                <h3>ເພີ່ມພະແນກໃໝ່</h3>
                <form method="POST">
                    <label>ຊື່ພະແນກ</label>
                    <input type="text" name="dept_name" placeholder="ປ້ອນຊື່ພະແນກໃໝ່..." required>
                    <button type="submit" name="add_dept">ບັນທຶກພະແນກ</button>
                </form>
            </div>

            <h3>ລາຍການພະແນກທັງໝົດ</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ຊື່ພະແນກ</th>
                            <th style="text-align: center; width: 80px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($depts_list && $depts_list->num_rows > 0): ?>
                            <?php while($d = $depts_list->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($d['name']); ?></td>
                                    <td style="text-align: center;">
                                        <a href="manage_structure.php?delete_dept_id=<?php echo $d['id']; ?>" class="btn-del" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບພະແນກນີ້?')">ລົບ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #64748b;">ບໍ່ມີຂໍ້ມູນພະແນກ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div>
            <div class="form-box">
                <h3>ເພີ່ມຫົວຂໍ້ກິດຈະກຳໃໝ່</h3>
                <form method="POST">
                    <label>ຊື່ຫົວຂໍ້ກິດຈະກຳ / ຫົວຂໍ້່າວ</label>
                    <input type="text" name="title" placeholder="ປ້ອນຊື່ຫົວຂໍ້..." required>

                    <?php if ($user_role === 'admin'): ?>
                        <label>ສັງກັດພະແນກ</label>
                        <select name="department_id" required>
                            <option value="">-- ເເລືອກພະແນກ --</option>
                            <?php 
                            if($depts_dropdown && $depts_dropdown->num_rows > 0) {
                                $depts_dropdown->data_seek(0);
                                while($dd = $depts_dropdown->fetch_assoc()) {
                                    echo "<option value='{$dd['id']}'>" . htmlspecialchars($dd['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    <?php endif; ?>

                    <label>ເລືອກກຸ່ມວຽກ</label>
                    <select name="work_group" required>
                        <option value="ກຸ່ມວຽກການເມືອງແນວຄິດ">ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>

                    <button type="submit" name="add_topic">ບັນທຶກຫົວຂໍ້</button>
                </form>
            </div>

            <h3>ລາຍການຫົວຂໍ້ກິດຈະກຳ</h3>
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
                        <?php if($topics_list && $topics_list->num_rows > 0): ?>
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
        </div>
        
    </div>

    <a href="dashboard.php" class="btn-back">ກັບ Dashboard</a>
</div>
</body>
</html>