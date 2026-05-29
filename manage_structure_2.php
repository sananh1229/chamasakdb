<?php
include 'db_2.php';
// เช็คแค่ว่าล็อกอินหรือยัง (User ทั่วไปก็สามารถเข้าหน้านี้ได้แล้ว)
if (!isset($_SESSION['user_id'])) { header("Location: login_2.php"); exit(); }

$user_role = $_SESSION['role'];
$user_dept_id = intval($_SESSION['dept_id']);

// 1. ຈັດການເພີ່ມ ພະແນກ (เฉพาะ Admin เท่านั้น)
if (isset($_POST['add_dept']) && $user_role === 'admin') {
    $name = $conn->real_escape_string($_POST['dept_name']);
    if(!empty($name)) $conn->query("INSERT INTO departments (name) VALUES ('$name')");
}

// 2. ຈັດການລົບ ພະແนກ (เฉพาะ Admin เท่านั้น)
if (isset($_GET['del_dept']) && $user_role === 'admin') {
    $id = intval($_GET['del_dept']);
    $conn->query("DELETE FROM departments WHERE id = $id");
}

// 3. ຈັດການເພີ່ມ ຫົວຂໍ້ (แอดมินเพิ่มได้ทุกพะแนก ส่วน User จะถูกระบบล็อกให้เพิ่มเฉพาะพะแนกตนเอง)
if (isset($_POST['add_topic'])) {
    if ($user_role === 'admin') {
        $dept_id = intval($_POST['dept_id']);
    } else {
        $dept_id = $user_dept_id; // ล็อกไอดีพะแนกตาม User ที่ล็อกอินเข้ามา
    }
    $title = $conn->real_escape_string($_POST['topic_title']);
    if(!empty($title) && $dept_id > 0) {
        $conn->query("INSERT INTO topics (department_id, title) VALUES ($dept_id, '$title')");
    }
}

// 4. ຈັດການລົບ ຫົວຂໍ້ (ลบหัวข้อได้ตามสิทธิ์)
if (isset($_GET['del_topic'])) {
    $id = intval($_GET['del_topic']);
    
    if ($user_role === 'admin') {
        $conn->query("DELETE FROM topics WHERE id = $id");
    } else {
        // หากเป็น User ทั่วไป จะลบได้เฉพาะหัวข้อที่อยู่ในพะแนกของตัวเองเท่านั้นเพื่อความปลอดภัย
        $conn->query("DELETE FROM topics WHERE id = $id AND department_id = $user_dept_id");
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງພະແນກ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f8fafc; color: #334155; padding: 15px; margin: 0; }
        .container { max-width: 900px; margin: auto; }
        .section { background: #ffffff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h3 { margin-top: 0; color: #0f172a; border-bottom: 1px solid #edf2f7; padding-bottom: 12px; font-size: 18px; }
        .form-group { display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; }
        @media (min-width: 576px) { .form-group { flex-direction: row; } }
        input, select, button { padding: 11px 15px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; font-size: 14px; }
        input, select { flex: 1; }
        input:focus, select:focus { border-color: #3182ce; outline: none; }
        button { background: #3182ce; color: #ffffff; font-weight: bold; cursor: pointer; border: none; }
        button:hover { background: #2b6cb0; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { background: #f8fafc; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; font-size: 14px; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; padding: 4px 8px; border-radius: 4px; }
        .btn-del:hover { background: #fff5f5; }
        .btn-back { color: #64748b; text-decoration: none; display: inline-block; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        .btn-back:hover { color: #3182ce; }
        .badge-dept { color:#64748b; font-weight:bold; background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:12px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_2.php" class="btn-back">← ກັບໄປໜ້າຫຼັກ Dashboard</a>
        
        <!-- ส่วนที่ 1: จัดการพะแนง (ระบบจะแสดงให้เห็นเฉพาะสิทธิ์ Admin เท่านั้น) -->
        <?php if ($user_role === 'admin'): ?>
        <div class="section">
            <h3>1. ຈັດການພະແນກ (ເພີ່ມ/ລົບ) <span style="color:#3182ce; font-size:12px;">[เฉพาะ Admin]</span></h3>
            <form method="POST" class="form-group">
                <input type="text" name="dept_name" placeholder="ປ້ອນຊື່ພະແນກໃໝ່..." required>
                <button type="submit" name="add_dept">ເພີ່ມພະແນກ</button>
            </form>
            <ul>
                <?php
                $depts = $conn->query("SELECT * FROM departments");
                while($d = $depts->fetch_assoc()) {
                    echo "<li><span>" . htmlspecialchars($d['name']) . "</span> <a href='manage_structure_2.php?del_dept={$d['id']}' class='btn-del' onclick='return confirm(\"ຕ້ອງການລົບພະແນກນີ້ ແລະ ຂໍ້ມູນທີ່ກ່ຽວຂ້ອງທັງໝົດແມ່ນບໍ່?\")'>ລົບ</a></li>";
                }
                ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- ส่วนที่ 2: จัดการหัวข้อ (เปิดสิทธิ์ให้ทั้ง Admin และ User เพิ่มเองได้) -->
        <div class="section">
            <h3>2. ຈັດການ ແລະ ເພີ່ມຫົວຂໍ້/ກິດຈະກຳ (Topic)</h3>
            <form method="POST" class="form-group">
                
                <?php if ($user_role === 'admin'): ?>
                    <!-- ถ้าเป็นแอดมิน จะเลือกพะแนงไหนก็ได้ -->
                    <select name="dept_id" required>
                        <option value="">-- ເລືອກພະແນກ --</option>
                        <?php
                        $depts_select = $conn->query("SELECT * FROM departments");
                        while($ds = $depts_select->fetch_assoc()) {
                            echo "<option value='{$ds['id']}'>" . htmlspecialchars($ds['name']) . "</option>";
                        }
                        ?>
                    </select>
                <?php else: ?>
                    <!-- ถ้าเป็น User ทั่วไป ระบบจะล็อกชื่อพะแนงของตัวเองแสดงผลให้ทราบเลย -->
                    <?php 
                    $my_dept = $conn->query("SELECT name FROM departments WHERE id = $user_dept_id")->fetch_assoc();
                    ?>
                    <input type="text" value="ສັງກັດພະແນກຂອງທ່ານ: <?php echo htmlspecialchars($my_dept['name']); ?>" disabled style="background:#e2e8f0; font-weight:bold;">
                <?php endif; ?>

                <input type="text" name="topic_title" placeholder="ປ້ອນຊື່ຫົວຂໍ້ໃໝ່ (ຕົວຢ່າງ: ການເກັບຜົນຜະລິດ, ການທ່ອງທ່ຽວ...)" required>
                <button type="submit" name="add_topic">➕ ເພີ່ມຫົວຂໍ້</button>
            </form>
            
            <ul>
                <?php
                // ดึงรายการหัวข้อขึ้นมาแสดงผลตามเงื่อนไขสิทธิ์
                if ($user_role === 'admin') {
                    $topics = $conn->query("SELECT t.*, d.name as dname FROM topics t JOIN departments d ON t.department_id = d.id ORDER BY t.department_id DESC");
                } else {
                    $topics = $conn->query("SELECT t.*, d.name as dname FROM topics t JOIN departments d ON t.department_id = d.id WHERE t.department_id = $user_dept_id ORDER BY t.id DESC");
                }
                
                if($topics && $topics->num_rows > 0):
                    while($t = $topics->fetch_assoc()) {
                        echo "<li><span><span class='badge-dept'>[" . htmlspecialchars($t['dname']) . "]</span> " . htmlspecialchars($t['title']) . "</span> <a href='manage_structure_2.php?del_topic={$t['id']}' class='btn-del' onclick='return confirm(\"ຕ້ອງການລົບຫົວຂໍ້ນີ້ບໍ່?\")'>ລົບ</a></li>";
                    }
                else:
                    echo "<p style='text-align:center; color:#64748b; font-size:14px; padding:10px;'>ບໍ່ມີຫົວຂໍ້ໃນລະບົບ</p>";
                endif;
                ?>
            </ul>
        </div>
    </div>
</body>
</html>