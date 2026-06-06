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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Noto Sans Lao', sans-serif; }
        .login-container { background: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #cbd5e1; width: 100%; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* 🌟 ກ່ອງໃສ່ຮູບພາບໂລໂກ້ ສາມາດປ່ຽນ src ເປັນຮູບອົງກອນຂອງທ່ານໄດ້ໃນພາຍຫຼັງ */
        .login-logo { margin-bottom: 20px; text-align: center; }
        .login-logo img { max-width: 120px; height: auto; border-radius: 8px; background: #f1f5f9; padding: 10px; border: 1px dashed #cbd5e1; }
        
        h2 { margin-bottom: 25px; font-weight: 700; color: #0f172a; font-size: 22px; text-align: center; }
        input { width: 100%; padding: 12px 16px; margin: 10px 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 15px; color: #334155; }
        input:focus { border-color: #2563eb; outline: none; }
        button { width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 16px; }
        button:hover { background: #1d4ed8; }
        .error-msg { color: #ef4444; margin-top: 15px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="image/va.jfif" alt="ກະລຸນາໃສ່ຮູບພາບພາຍຫຼັງ">
        </div>
        <h2>ລະບົບຖານຂໍ້ມູນສະຖິຕິ</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ຊື່ຜູ້ໃຊ້ງານ" required>
            <input type="password" name="password" placeholder="ລະຫັດຜ່ານ" required>
            <button type="submit">ເຂົ້າສູ່ລະບົບ</button>
        </form>
    </div>
</body>
</html>