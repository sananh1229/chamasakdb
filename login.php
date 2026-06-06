<?php
include 'db.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            // ดึงรหัสไอดีนคร/เมืองผูกกับ Session แผนกเดิมเพื่อไม่ต้องตามแก้ส่วนอื่นให้ยุ่งยาก
            $_SESSION['dept_id'] = $user['district_id']; 
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!";
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຂົ້າສູ່ລະບົບ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Noto Sans Lao', sans-serif; }
        .login-container { background: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { margin-bottom: 25px; font-weight: 700; color: #1a202c; font-size: 24px; }
        input { width: 100%; padding: 12px 16px; margin: 10px 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        button { width: 100%; padding: 12px; background: #3182ce; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 16px; }
        button:hover { background: #2b6cb0; }
        .error-msg { color: #e53e3e; font-weight: bold; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>ລະບົບຖານຂໍ້ມູນສະຖິຕິ</h2>
        <?php if(!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ຊື່ຜູ້ໃຊ້ງານ" required>
            <input type="password" name="password" placeholder="ລະຫັດຜ່ານ" required>
            <button type="submit">ເຂົ້າສູ່ລະບົບ</button>
        </form>
    </div>
</body>
</html>