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
            $_SESSION['dept_id'] = $user['department_id'];
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
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f4f6f9; color: #333333; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 15px; }
        .login-container { background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; width: 100%; max-width: 400px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); text-align: center; }
        h2 { margin-bottom: 25px; font-weight: 700; color: #1a202c; font-size: 24px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px 16px; margin: 10px 0; background: #f8fafc; border: 1px solid #cbd5e1; color: #333; border-radius: 6px; font-size: 15px; transition: all 0.3s; }
        input:focus { border-color: #3182ce; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5); }
        button { width: 100%; padding: 12px; background: #3182ce; color: #ffffff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 15px; font-size: 16px; }
        button:hover { background: #2b6cb0; }
        .error { color: #e53e3e; margin-bottom: 15px; font-size: 14px; background: #fff5f5; padding: 12px; border-radius: 6px; border: 1px solid #fed7d7; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>ເຂົ້າສູ່ລະບົບຖານຂໍ້ມູນ</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ຊື່ຜູ້ໃຊ້" required>
            <input type="password" name="password" placeholder="ລະຫັດຜ່ານ" required>
            <button type="submit">ເຂົ້າສູ່ລະບົບ</button>
        </form>
    </div>
</body>
</html>