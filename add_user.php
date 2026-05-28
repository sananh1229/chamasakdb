<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);
    $department_id = intval($_POST['department_id']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $msg = "<p class='msg error'>ຊື່ຜູ້ໃຊ້ນີ້ມີໃນລະບົບແລ້ວ! ກະລຸນາປ່ຽນໃໝ່</p>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, department_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $department_id);
            
            if ($stmt->execute()) {
                $msg = "<p class='msg success'>ເພີ່ມຜູ້ໃຊ້ງານແລ້ວ!</p>";
            } else {
                $msg = "<p class='msg error'>ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ.</p>";
            }
        }
    }
}

$depts = $conn->query("SELECT * FROM departments ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding: 15px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { background: #ffffff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        h2 { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-top: 0; font-weight: 700; color: #1a202c; font-size: 20px; }
        label { display: block; margin: 15px 0 5px; color: #4a5568; font-size: 14px; font-weight: 600; }
        select, input, button { width: 100%; padding: 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #333; border-radius: 6px; font-size: 16px; transition: all 0.3s; }
        input:focus, select:focus { border-color: #3182ce; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2); }
        button { background: #3182ce; color: #ffffff; font-weight: bold; border: none; cursor: pointer; margin-top: 25px; }
        button:hover { background: #2b6cb0; }
        .btn-back { display: inline-block; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: bold; }
        .btn-back:hover { color: #3182ce; }
        .msg { padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .success { background: #f0fff4; border: 1px solid #c6f6d5; color: #38a169; }
        .error { background: #fff5f5; border: 1px solid #fed7d7; color: #e53e3e; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>+ ເພີ່ມຜູ້ໃຊ້ງານໃໝ່ (Add User)</h2>
        <?php echo $msg; ?>
        
        <form method="POST">
            <label>ຊື່ຜູ້ໃຊ້ (Username)</label>
            <input type="text" name="username" placeholder="ຕົວຢ່າງ: user_kasi" required>

            <label>ລະຫັດຜ່ານ (Password)</label>
            <input type="password" name="password" placeholder="ປ້ອນລະຫັດຜ່ານ" required>

            <label>ສິດການເຂົ້າເຖິງ (Role)</label>
            <select name="role" required>
                <option value="user">User (ພະນັກງານທົ່ວໄປ)</option>
                <option value="admin">Admin (ຜູ້ດູແລລະບົບ)</option>
            </select>

            <label>ສັງກັດພະແນກ (Department)</label>
            <select name="department_id" required>
                <option value="0">-- ບໍ່ມີສັງກັດພະແເນກ (ຫຼື Admin ລະບົບສ່ວນກາງ) --</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="add_user">ບັນທຶກຜູ້ໃຊ້ງານ</button>
        </form>
        
        <a href="dashboard.php" class="btn-back">← ກັບໄປໜ້າຫຼັກ Dashboard</a>
    </div>
</body>
</html>