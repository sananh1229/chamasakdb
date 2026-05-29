<?php
include 'db.php';

// ເຊັກແຄ່ວ່າໄດ້ເຂົ້າສູ່ລະບົບແລ້ວບໍ່ (ທັງ Admin ແລະ User ສາມາດເຂົ້າໜ້ານີ້ໄດ້)
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_role = $_SESSION['role'];
$user_dept_id = intval($_SESSION['dept_id']);

// 1. ຈັດການເພີ່ມ ພະແນກ (ອະນຸຍາດສະເພາະ Admin ເທົ່ານັ້ນ)
if (isset($_POST['add_dept']) && $user_role === 'admin') {
    $name = $conn->real_escape_string($_POST['dept_name']);
    if(!empty($name)) $conn->query("INSERT INTO departments (name) VALUES ('$name')");
}

// 2. ຈັດການລົບ ພະແນກ (ອະນຸຍາດສະເພາະ Admin ເທົ່ານັ້ນ)
if (isset($_GET['del_dept']) && $user_role === 'admin') {
    $id = intval($_GET['del_dept']);
    $conn->query("DELETE FROM departments WHERE id = $id");
}

// 3. 🌟 ແກ້ໄຂແຖວນີ້ແລ້ວ: ຈັດການເພີ່ມ ຫົວຂໍ້/ກິດຈະກຳ (Topic) -> ເປີດໃຫ້ທັງ Admin ແລະ User ເພີ່ມເອງໄດ້
if (isset($_POST['add_topic'])) {
    if ($user_role === 'admin') {
        $dept_id = intval($_POST['dept_id']); // Admin ເລືອກພະແນກໃດກໍໄດ້
    } else {
        $dept_id = $user_dept_id; // ຖ້າເປັນ User ລະບົບຈະລັອກໃຫ້ເພີ່ມສະເພາະພະແນກຂອງຕົນເອງ
    }
    $title = $conn->real_escape_string($_POST['topic_title']);
    if(!empty($title) && $dept_id > 0) {
        $conn->query("INSERT INTO topics (department_id, title) VALUES ($dept_id, '$title')");
    }
}

// 4. ຈັດການລົບ ຫົວຂໍ້ (Topic)
if (isset($_GET['del_topic'])) {
    $id = intval($_GET['del_topic']);
    if ($user_role === 'admin') {
        $conn->query("DELETE FROM topics WHERE id = $id");
    } else {
        // ຖ້າເປັນ User ຈະລົບໄດ້ສະເພາະຫົວຂໍ້ທີ່ຢູ່ໃນພະແນກຂອງຕົນເອງເທົ່ານັ້ນ
        $conn->query("DELETE FROM topics WHERE id = $id AND department_id = $user_dept_id");
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງພະແນກ & ຫົວຂໍ້</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">← ກັບໄປໜ້າຫຼັກ Dashboard</a>
        
        <?php if ($user_role === 'admin'): ?>
        <div class="section">
            <h3>1. ຈັດການພະແนກ (ເພີ່ມ/ລົບ) <span style="color:#3182ce; font-size:12px;">[ສິດທິ Admin]</span></h3>
            <form method="POST" class="form-group">
                <input type="text" name="dept_name" placeholder="ປ້ອນຊື່ພະແນກໃໝ່..." required>
                <button type="submit" name="add_dept">ເພີ່ມພະແນກ</button>
            </form>
            <ul>
                <?php
                $depts = $conn->query("SELECT * FROM departments");
                while($d = $depts->fetch_assoc()) {
                    echo "<li><span>" . htmlspecialchars($d['name']) . "</span> <a href='manage_structure.php?del_dept={$d['id']}' class='btn-del' onclick='return confirm(\"ต้องการລົບພະແນກນີ້ ແລະ ຂໍ້ມູນທັງໝົດແມ່ນບໍ່?\")'>ລົບ</a></li>";
                }
                ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="section">
            <h3>2. ຈັດການ ແລະ ເພີ່ມຫົວຂໍ້/ກິດຈະກຳ (Topic)</h3>
            <form method="POST" class="form-group">
                
                <?php if ($user_role === 'admin'): ?>
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
                    <?php 
                    $my_dept = $conn->query("SELECT name FROM departments WHERE id = $user_dept_id")->fetch_assoc();
                    ?>
                    <input type="text" value="ພະແນກຂອງທ່ານ: <?php echo htmlspecialchars($my_dept['name']); ?>" disabled style="background:#edf2f7; font-weight:bold; color:#4a5568;">
                <?php endif; ?>

                <input type="text" name="topic_title" placeholder="...ປ້ອນຊື່ຫົວຂໍ້ໃໝ່" required>
                <button type="submit" name="add_topic">➕ ເພີ່ມຫົວຂໍ້</button>
            </form>
            
            <ul>
                <?php
                // ດຶງລາຍການຂຶ້ນມາສະແດງຜົນຕາມເງື່ອນໄຂສິດທິ
                if ($user_role === 'admin') {
                    $topics = $conn->query("SELECT t.*, d.name as dname FROM topics t JOIN departments d ON t.department_id = d.id ORDER BY t.department_id DESC");
                } else {
                    $topics = $conn->query("SELECT t.*, d.name as dname FROM topics t JOIN departments d ON t.department_id = d.id WHERE t.department_id = $user_dept_id ORDER BY t.id DESC");
                }
                
                if($topics && $topics->num_rows > 0):
                    while($t = $topics->fetch_assoc()) {
                        echo "<li><span><span class='badge-dept'>[" . htmlspecialchars($t['dname']) . "]</span> " . htmlspecialchars($t['title']) . "</span> <a href='manage_structure.php?del_topic={$t['id']}' class='btn-del' onclick='return confirm(\"ຕ້ອງການລົບຫົວຂໍ້ນີ້ບໍ່?\")'>ລົບ</a></li>";
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