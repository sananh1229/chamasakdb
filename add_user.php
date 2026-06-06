<?php
include 'db.php';
// ກວດສອບສິດ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

$msg = '';

// 1. ລະບົບລົບຜູ້ໃຊ້ງານ
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

// 2. ລະບົບເພີ່ມຜູ້ໃຊ້ງານ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    $district_id = intval($_POST['district_id']); 

    if (!empty($username) && !empty($password)) {
        $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $msg = "<div class='msg error'>ຊື່ຜູ້ໃຊ້ນີ້ມີຢູ່ແລ້ວ</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, district_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $district_id);
            if ($stmt->execute()) { $msg = "<div class='msg success'>ເພີ່ມຜູ້ໃຊ້ງານສຳເລັດແລ້ວ</div>"; }
        }
    }
}

$users_list = $conn->query("SELECT u.*, d.name as dist_name FROM users u LEFT JOIN districts d ON u.district_id = d.id ORDER BY u.id DESC");
$districts = $conn->query("SELECT * FROM districts ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຜູ້ໃຊ້ງານ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #ffffff; margin: 0; color: #334155; }
        .wrapper { display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background: #f8fafc; border-right: 1px solid #cbd5e1; padding: 20px; flex-shrink: 0; }
        .sidebar-brand { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; padding-bottom: 15px; border-bottom: 2px solid #cbd5e1; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 8px; }
        .sidebar-item a { display: block; padding: 12px 15px; color: #475569; text-decoration: none; font-weight: 500; border-radius: 6px; }
        .sidebar-item a:hover { background: #e2e8f0; color: #0f172a; }
        .sidebar-item.active a { background: #2563eb; color: #ffffff; font-weight: 600; }

        .main-content { flex: 1; padding: 25px; width: 100%; }
        h2 { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 20px 0; }
        
        .form-container { background: #f8fafc; padding: 20px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 30px; max-width: 600px; }
        label { display: block; margin: 10px 0 5px 0; font-weight: 600; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 10px; }
        button { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: 600; }
        .success { background: #dcfce3; color: #16a34a; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #ef4444; border: 1px solid #fecdd3; }
        
        .table-container { border: 1px solid #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #cbd5e1; font-size: 14px; }
        th { background: #cbd5e1; text-align: left; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .btn-del { color: #ef4444; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <li class="sidebar-item"><a href="manage_structure.php">ຈັດການໂຄງສ້າງລະບົບ</a></li>
                <li class="sidebar-item active"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</h2>
            <?php echo $msg; ?>
            
            <div class="form-container">
                <form method="POST">
                    <label>ຊື່ຜູ້ໃຊ້ງານ</label>
                    <input type="text" name="username" required>
                    
                    <label>ລະຫັດຜ່ານ</label>
                    <input type="password" name="password" required>
                    
                    <label>ສິດທິການເຂົ້າເຖິງ</label>
                    <select name="role">
                        <option value="user">User (ຜູ້ໃຊ້ງານເມືອງ)</option>
                        <option value="admin">Admin (ຜູ້ເບິ່ງແຍງລະບົບ)</option>
                    </select>
                    
                    <label>ເລືອກນະຄອນ/ເມືອງ</label>
                    <select name="district_id" required>
                        <option value="0">-- ສ່ວນກາງແຂວງ --</option>
                        <?php while($d = $districts->fetch_assoc()) { 
                            echo "<option value='{$d['id']}'>".htmlspecialchars($d['name'])."</option>"; 
                        } ?>
                    </select>
                    
                    <button type="submit" name="add_user">ບັນທຶກຜູ້ໃຊ້ງານ</button>
                </form>
            </div>

            <h3>ລາຍຊື່ຜູ້ໃຊ້ທັງໝົດໃນລະບົບ</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ຊື່ຜູ້ໃຊ້</th>
                            <th>ສິດທິ</th>
                            <th>ນະຄອນ/ເມືອງ</th>
                            <th>ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $users_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><?php echo !empty($u['dist_name']) ? htmlspecialchars($u['dist_name']) : 'ສ່ວນກາງ'; ?></td>
                            <td>
                                <?php if($u['id'] !== intval($_SESSION['user_id'])): ?>
                                    <a href="add_user.php?delete_id=<?php echo $u['id']; ?>" class="btn-del" onclick="return confirm('ຕ້ອງການລົບແທ້ບໍ່?')">ລົບ</a>
                                <?php else: ?>
                                    <span style="color:#10b981; font-weight:bold;">ກຳລັງໃຊ້ງານ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>