<?php
include 'db.php';

// ຕາຕະລາງກວດສອບສິດທິ (ຕ້ອງເຂົ້າສູ່ລະບົບ ແລະ ເປັນ Admin ເທົ່ານັ້ນ)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$msg = '';

// ລະບົບປະມວນຜົນເມື່ອກົດປຸ່ມບັນທຶກ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    $department_id = intval($_POST['department_id']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        // ກວດຊື່ຜູ້ໃຊ້ຊ້ຳ
        $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $msg = "<div class='msg error'>❌ ຊື່ຜູ້ໃຊ້ນີ້ມີໃນລະບົບແລ້ວ! ກະລຸນາປ່ຽນໃໝ່</div>";
        } else {
            // ເຂົ້າລະຫັດຜ່ານເພື່ອຄວາມປອດໄພ
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // ໃຊ້ SQL Insert ແບບກົງໄປກົງມາເພື່ອຫຼີກເວັ້ນ SQL Error ຂອງເກົ່າ
            $insert_query = "INSERT INTO users (username, password, role, department_id) 
                             VALUES ('$username', '$hashed_password', '$role', $department_id)";
            
            if ($conn->query($insert_query)) {
                $msg = "<div class='msg success'>✅ ເພີ່ມຜູ້ໃຊ້ງານໃໝ່ສຳເລັດແລ້ວ!</div>";
            } else {
                $msg = "<div class='msg error'>❌ ບໍ່ສາມາດບັນທຶກໄດ້: " . $conn->error . "</div>";
            }
        }
    } else {
        $msg = "<div class='msg error'>❌ ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ</div>";
    }
}

// ດຶງລາຍຊື່ພະແนກ
$depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* 🌟 ປັບແຕ່ງພິເສດເພື່ອໃຫ້ຟອມ Add User ຢູ່ເຄິ່ງກາງ ແລະ ສະອາດງາມຕາ */
        .form-wrap-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            width: 100%;
        }
        .form-container-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .form-container-custom h2 {
            margin-bottom: 20px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            text-align: center;
        }
        .form-container-custom label {
            display: block;
            margin-top: 14px;
            margin-bottom: 6px;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
        }
        .form-container-custom input, .form-container-custom select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .form-container-custom input:focus, .form-container-custom select:focus {
            border-color: #3182ce;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
        }
        .btn-submit-user {
            width: 100%;
            padding: 14px;
            background-color: #9f7aea;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 25px;
            transition: background 0.2s;
        }
        .btn-submit-user:hover {
            background-color: #805ad5;
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">☰ ເມນູ</button>

    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                📊 ລະບົບຖານຂໍ້ມູນພະແນກ
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="dashboard.php">🏠 ໜ້າຫຼັກ Dashboard</a>
                </li>
                <li class="sidebar-item">
                    <a href="compare_report.php">📊 ໜ້າສະຫຼຸບສົມທຽບ</a>
                </li>
                <li class="sidebar-item">
                    <a href="insert_data.php">➕ ບັນທຶກຂໍ້ມູນໃໝ່</a>
                </li>
                <li class="sidebar-item">
                    <a href="manage_structure.php">🛠️ ຈັດການ & ເພີ່ມຫົວຂໍ້</a>
                </li>
                
                <?php if($_SESSION['role'] == 'admin'): ?>
                <li class="sidebar-item active">
                    <a href="add_user.php" style="color: #9f7aea;">👤 ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a>
                </li>
                <?php endif; ?>

                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                    <a href="logout.php" style="background-color: #f56565; color: #fff; text-align: center; justify-content: center;">🚪 ອອກຈາກລະບົບ</a>
                </li>
            </ul>

            <div class="sidebar-user">
                <p>👤 ຜູ້ໃຊ້: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <p>🔑 ສິດທິ: <span style="text-transform: uppercase; font-weight: bold; color:#3182ce;"><?php echo $_SESSION['role']; ?></span></p>
            </div>
        </nav>

        <main class="main-content">
            <div class="form-wrap-center">
                <div class="form-container-custom">
                    <h2>👤 ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</h2>
                    
                    <?php echo $msg; ?>
                    
                    <form method="POST" action="add_user.php">
                        <label>ຊື່ຜູ້ໃຊ້ (Username)</label>
                        <input type="text" name="username" placeholder="ຕົວຢ່າງ: user_champasak" required autocomplete="off">

                        <label>ລະຫັດຜ່ານ (Password)</label>
                        <input type="password" name="password" placeholder="ປ້ອນລະຫັດຜ່ານ..." required>

                        <label>ສິດການເຂົ້າເຖິງລະບົບ (Role)</label>
                        <select name="role" required>
                            <option value="user">User (ພະນັກງານພະແນກ / ບັນທຶກຂໍ້ມູນ)</option>
                            <option value="admin">Admin (ຜູ້ດູແລລະບົບສ່ວນກາງ)</option>
                        </select>

                        <label>ສັງກັດພະແນກ (Department)</label>
                        <select name="department_id" required>
                            <option value="0">-- ສ່ວນກາງ (Admin ບໍ່ມີສັງກັດ) --</option>
                            <?php 
                            if($depts && $depts->num_rows > 0) {
                                while($d = $depts->fetch_assoc()) {
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>

                        <button type="submit" name="add_user" class="btn-submit-user">💾 ບັນທຶກລົງທะບຽນຜູ້ໃຊ້</button>
                    </form>
                </div>
            </div>
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