<?php
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

if (isset($_POST['add_dept'])) {
    $name = $conn->real_escape_string($_POST['dept_name']);
    if(!empty($name)) $conn->query("INSERT INTO departments (name) VALUES ('$name')");
}
if (isset($_GET['del_dept'])) {
    $id = intval($_GET['del_dept']);
    $conn->query("DELETE FROM departments WHERE id = $id");
}
if (isset($_POST['add_topic'])) {
    $dept_id = intval($_POST['dept_id']);
    $title = $conn->real_escape_string($_POST['topic_title']);
    if(!empty($title) && $dept_id > 0) $conn->query("INSERT INTO topics (department_id, title) VALUES ($dept_id, '$title')");
}
if (isset($_GET['del_topic'])) {
    $id = intval($_GET['del_topic']);
    $conn->query("DELETE FROM topics WHERE id = $id");
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
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">← ກັບໄປໜ້າຫຼັກ Dashboard</a>
        
        <div class="section">
            <h3>1. ຈັດການພະແນກ (ເພີ່ມ/ລົບ)</h3>
            <form method="POST" class="form-group">
                <input type="text" name="dept_name" placeholder="ປ້ອນຊື່ພະແນກໃໝ່..." required>
                <button type="submit" name="add_dept">ເພີ່ມພະແນກ</button>
            </form>
            <ul>
                <?php
                $depts = $conn->query("SELECT * FROM departments");
                while($d = $depts->fetch_assoc()) {
                    echo "<li><span>" . htmlspecialchars($d['name']) . "</span> <a href='manage_structure.php?del_dept={$d['id']}' class='btn-del' onclick='return confirm(\"ຕ້ອງການລົບພະແນກນີ້ ແລະ ຂໍ້ມູນທີ່ກ່ຽວຂ້ອງທັງໝົດແມ່ນບໍ່?\")'>ລົບ</a></li>";
                }
                ?>
            </ul>
        </div>

        <div class="section">
            <h3>2. ຈັດการຫົວຂໍ້/ກິດຈະກຳ ແຕ່ລະພະແนກ</h3>
            <form method="POST" class="form-group">
                <select name="dept_id" required>
                    <option value="">-- ເລືອກພະແນກ --</option>
                    <?php
                    $depts_select = $conn->query("SELECT * FROM departments");
                    while($ds = $depts_select->fetch_assoc()) {
                        echo "<option value='{$ds['id']}'>" . htmlspecialchars($ds['name']) . "</option>";
                    }
                    ?>
                </select>
                <input type="text" name="topic_title" placeholder="ຕົວຢ່າງ: ການເກັບຜົນຜະລິດ, ການທ່ອງທ່ຽວ..." required>
                <button type="submit" name="add_topic">ເພີ່ມຫົວຂໍ້</button>
            </form>
            <ul>
                <?php
                $topics = $conn->query("SELECT t.*, d.name as dname FROM topics t JOIN departments d ON t.department_id = d.id ORDER BY t.department_id DESC");
                while($t = $topics->fetch_assoc()) {
                    echo "<li><span><small style='color:#64748b; font-weight:bold;'>[" . htmlspecialchars($t['dname']) . "]</small> " . htmlspecialchars($t['title']) . "</span> <a href='manage_structure.php?del_topic={$t['id']}' class='btn-del' onclick='return confirm(\"ຕ້ອງການລົບຫົວຂໍ້ນີ້ບໍ່?\")'>ລົບ</a></li>";
                }
                ?>
            </ul>
        </div>
    </div>
</body>
</html>