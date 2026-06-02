<?php
include 'db.php';

// ກວດສອບສິດທິ (ຕ້ອງເຂົ້າສູ່ລະບົບ ແລະ ເປັນ Admin ເທົ່ານັ້ນ)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$msg = '';

// 1. ລະບົບປະມວນຜົນການລົບຜູ້ໃຊ້ງານ (Delete User)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // ປ້ອງກັນບໍ່ໃຫ້ Admin ລົບຕົນເອງໃນຂະນະທີ່ໃຊ້ງານຢູ່
    if ($delete_id === intval($_SESSION['user_id'])) {
        $msg = "<div class='msg error'>ບໍ່ສາມາດລົບຊື່ບັນຊີຂອງຕົນເອງໃນຂະນະທີ່ເຂົ້າສູ່ລະບົບໄດ້</div>";
    } else {
        $stmt_del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->bind_param("i", $delete_id);
        if ($stmt_del->execute()) {
            $msg = "<div class='msg success'>ລົບຂໍ້ມູນຜູ້ໃຊ້ງານອອກຈາກລະບົບສຳເລັດແລ້ວ</div>";
        } else {
            $msg = "<div class='msg error'>ເກີດຂໍ້ຜິດພາດ ບໍ່ສາມາດລົບຂໍ້ມູນຜູ້ໃຊ້ໄດ້</div>";
        }
    }
}

// 2. ລະບົບປະມວນຜົນເມື່ອກົດປຸ່ມບັນທຶກເພີ່ມຜູ້ໃຊ້ໃໝ່ (Add User)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    $department_id = intval($_POST['department_id']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        // ກວດຊື່ຜູ້ໃຊ້ຊ້ຳ
        $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $msg = "<div class='msg error'>ຊື່ຜູ້ໃຊ້ນີ້ມີໃນລະບົບແລ້ວ ກະລຸນາປ່ຽນໃໝ່</div>";
        } else {
            // ເຂົ້າລະຫັດຜ່ານເພື່ອຄວາມປອດໄພ
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, department_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $department_id);
            
            if ($stmt->execute()) {
                $msg = "<div class='msg success'>ບັນທຶກລົງທະບຽນຜູ້ໃຊ້ງານໃໝ່ສຳເລັດແລ້ວ</div>";
            } else {
                $msg = "<div class='msg error'>ເກີດຂໍ້ຜິດພາດ ບໍ່ສາມາດບັນທຶກຂໍ້ມູນໄດ້</div>";
            }
        }
    }
}

// ດຶງຂໍ້ມູນພະແນກທັງໝົດມາສະແດງໃນ Dropdown
$depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");

// ດຶງຂໍ້ມູນລາຍຊື່ຜູ້ໃຊ້ທັງໝົດເພື່ອມາສະແດງໃນຕາຕະລາງລາຍຊື່
$users_list = $conn->query("SELECT u.id, u.username, u.role, d.name as dept_name 
                            FROM users u 
                            LEFT JOIN departments d ON u.department_id = d.id 
                            ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຜູ້ໃຊ້ງານລະບົບ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; color: #334155; padding: 0; }
        
        .wrapper { display: flex; min-height: 100vh; position: relative; }
        .main-content { flex: 1; padding: 20px; background: #ffffff; width: 100%; overflow: hidden; }
        
        .form-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; }
        h2, h3 { margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-weight: 700; }
        
        label { display: block; margin: 12px 0 6px 0; font-weight: bold; font-size: 14px; color: #475569; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; height: 46px; background: #fff; color: #334155; }
        
        .btn-submit-user { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 15px; }
        .btn-submit-user:hover { background: #2b6cb0; }
        
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; font-size: 14px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        
        /* ຕາຕະລາງສະແດງລາຍຊື່ຜູ້ໃຊ້ */
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; -webkit-overflow-scrolling: touch; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        
        .role-badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; background: #e2e8f0; color: #475569; }
        .role-admin { background: #feebc8; color: #c05621; }
        
        .btn-delete-user { background: #f56565; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-block; }
        .btn-delete-user:hover { background: #e53e3e; }
        
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #334155; font-weight: bold; }

        @media (max-width: 768px) {
            .wrapper { flex-direction: column; }
            .main-content { padding: 15px; }
            .form-box { padding: 15px; }
            input[type="text"], input[type="password"], select, .btn-submit-user { width: 100%; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">ເມນູ</button>

    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນພະແນກ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <li class="sidebar-item"><a href="manage_structure.php">ຈັດການ ແລະ ເພີ່ມຫົວຂໍ້</a></li>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <li class="sidebar-item active"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                    <a href="logout.php" style="background: #f56565; color: #fff; text-align: center;">ອອກຈາກລະບົບ</a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ຈັດການຜູ້ໃຊ້ງານລະບົບສ່ວນກາງ</h2>
            
            <?php echo $msg; ?>

            <div class="form-box">
                <h3>ເພີ່ມບັນຊີຜູ້ໃຊ້ງານໃໝ່</h3>
                <form method="POST">
                    <label>ຊື່ຜູ້ໃຊ້ງານ (Username)</label>
                    <input type="text" name="username" placeholder="ປ້ອນຊື່ຜູ້ໃຊ້..." required>

                    <label>ລະຫັດຜ່ານ (Password)</label>
                    <input type="password" name="password" placeholder="ປ້ອນລະຫັດຜ່ານ..." required>

                    <label>ສິດທິການເຂົ້າເຖິງ (User Role)</label>
                    <select name="role" required>
                        <option value="user">User (ພະນັກງານປະຈຳນະຄອນ/ເມືອງ / ບັນທຶກຂໍ້ມູນ)</option>
                        <option value="admin">Admin (ຜູ້ດູແລລະບົບສ່ວນກາງ)</option>
                    </select>

                    <label>ສັງກັດພະແນກ (Department)</label>
                    <select name="department_id" required>
                        <option value="0">-- ສ່ວນກາງ (Admin ບໍ່ມີສັງກັດ) --</option>
                        <?php 
                        // ຣີເຊັດ pointer ຂອງ depts ຄືນໃໝ່
                        $depts->data_seek(0);
                        if($depts && $depts->num_rows > 0) {
                            while($d = $depts->fetch_assoc()) {
                                echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>

                    <button type="submit" name="add_user" class="btn-submit-user">ບັນທຶກລົງທະບຽນຜູ້ໃຊ້</button>
                </form>
            </div>

            <h3>ລາຍຊື່ຜູ້ໃຊ້ງານທັງໝົດໃນລະບົບ</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ຊື່ຜູ້ໃຊ້ງານ</th>
                            <th>ລະດັບສິດທິ</th>
                            <th>ພະແນກທີ່ສັງກັດ</th>
                            <th style="text-align: center;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users_list && $users_list->num_rows > 0): ?>
                            <?php while($u = $users_list->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo ($u['role'] == 'admin') ? 'role-admin' : ''; ?>">
                                            <?php echo ($u['role'] == 'admin') ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($u['dept_name']) ? htmlspecialchars($u['dept_name']) : 'ສ່ວນກາງ'; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($u['id'] !== intval($_SESSION['user_id'])): ?>
                                            <a href="add_user.php?delete_id=<?php echo $u['id']; ?>" class="btn-delete-user" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບຜູ້ໃຊ້ງານນີ້ອອກຈາກລະບົບ?')">ລົບອອກ</a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 13px;">ກຳລັງໃຊ້ງານ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b;">ບໍ່ມີຂໍ້ມູນຜູ້ໃຊ້ງານ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="dashboard.php" class="btn-back">ກັບ Dashboard</a>
        </main>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if(sidebar.classList.contains('open')) {
                menuToggle.innerText = '✕ ປິດ';
            } else {
                menuToggle.innerText = '☰ ເມນູ';
            }
        });
    </script>
</body>
</html>