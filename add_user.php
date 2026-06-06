<?php
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

$msg = '';
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id === intval($_SESSION['user_id'])) {
        $msg = "<div class='msg error'>ບໍ່ສາມາດລົບຊື່ບັນຊີຂອງຕົນເອງໄດ້</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) { $msg = "<div class='msg success'>ລົບຜູ້ໃຊ້ງານສຳເລັດແລ້ວ</div>"; }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    $district_id = intval($_POST['district_id']);

    if (!empty($username) && !empty($password)) {
        $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $msg = "<div class='msg error'>ຊື່ຜູ້ໃຊ້ນີ້ມີໃນລະບົບແລ້ວ</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, district_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $district_id);
            if ($stmt->execute()) { $msg = "<div class='msg success'>ລົງທະບຽນຜູ້ໃຊ້ໃໝ່ສຳເລັດ</div>"; }
        }
    }
}
$districts = $conn->query("SELECT * FROM districts ORDER BY id ASC");
$users_list = $conn->query("SELECT u.id, u.username, u.role, d.name as dist_name FROM users u LEFT JOIN districts d ON u.district_id = d.id ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຜູ້ໃຊ້ງານ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; color: #334155; }
        .wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; background: #fff; }
        .form-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; max-width: 600px; }
        label { display: block; margin: 12px 0 6px 0; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; height: 46px; background: #fff; }
        .btn-submit-user { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .msg { padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; margin-bottom: 15px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; } .error { background: #fff5f5; color: #c53030; }
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f1f5f9; }
        .btn-delete-user { background: #f56565; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; }
        @media (max-width: 768px) { .wrapper { flex-direction: column; } }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">ເມນູ</button>
    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <li class="sidebar-item"><a href="manage_structure.php">%E0%BB%80%E0%BB%84ັດການໂຄງສ້າງລະບົບ</a></li>
                <li class="sidebar-item active"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 15px;"><a href="logout.php" style="background: #f56565; color: #fff; text-align: center;">ອອກຈາກລະບົບ</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ຈັດການຜູ້ໃຊ້ງານລະບົບ</h2>
            <?php echo $msg; ?>
            <div class="form-box">
                <form method="POST">
                    <label>ຊື່ຜູ້ໃຊ້ງານ (Username)</label><input type="text" name="username" required>
                    <label>ລະຫັດຜ່ານ (Password)</label><input type="password" name="password" required>
                    <label>ສິດທິການເຂົ້າເຖິງ</label>
                    <select name="role" required><option value="user">User (ປະຈຳນະຄອນ/ເມືອງ)</option><option value="admin">Admin (ສ່ວນກາງແຂວງ)</option></select>
                    <label>ສັງກັດນະຄອນ/ເມືອງ</label>
                    <select name="district_id" required><option value="0">-- ສ່ວນກາງແຂວງ --</option><?php while($d = $districts->fetch_assoc()) { echo "<option value='{$d['id']}'>".htmlspecialchars($d['name'])."</option>"; } ?></select>
                    <button type="submit" name="add_user" class="btn-submit-user">ບັນທຶກຜູ້ໃຊ້</button>
                </form>
            </div>
            <h3>ລາຍຊື່ຜູ້ໃຊ້ທັງໝົດ</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>ຊື່ຜູ້ໃຊ້</th><th>ສິດທິ</th><th>ນະຄອນ/ເມືອງ</th><th>ຈັດການ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users_list->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($u['username']); ?></td><td><?php echo $u['role']; ?></td><td><?php echo !empty($u['dist_name']) ? htmlspecialchars($u['dist_name']) : 'ສ່ວນກາງ'; ?></td><td><?php if($u['id'] !== intval($_SESSION['user_id'])): ?><a href="add_user.php?delete_id=<?php echo $u['id']; ?>" class="btn-delete-user" onclick="return confirm('ຕ້ອງການລົບແທ້ບໍ່?')">ລົບ</a><?php else: echo "ກຳລັງໃຊ້ງານ"; endif; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>document.getElementById('menuToggle').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('open'); });</script>
</body>
</html>