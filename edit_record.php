<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$record_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0);
$msg = '';

$record_query = $conn->query("SELECT r.*, t.title, t.district_id FROM records r JOIN topics t ON r.topic_id = t.id WHERE r.id = $record_id");

if ($record_query && $record_query->num_rows > 0) {
    $record_data = $record_query->fetch_assoc();
    
    if ($_SESSION['role'] !== 'admin' && intval($record_data['district_id']) !== intval($_SESSION['dept_id'])) {
        header("Location: insert_data.php");
        exit();
    }
    
    $current_amount = $record_data['amount'];
    $current_unit = $record_data['unit'];
    $current_date = $record_data['record_date'];
    $topic_title = $record_data['title'];
} else {
    header("Location: insert_data.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_data'])) {
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($record_id > 0 && !empty($unit) && !empty($record_date)) {
        $stmt = $conn->prepare("UPDATE records SET amount = ?, unit = ?, record_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $amount, $unit, $record_date, $record_id);
        
        if ($stmt->execute()) {
            $msg = "<div class='msg success'>ແກ້ໄຂ ແລະ ອັບເດດຂໍ້ມູນສະຖິຕິສຳເລັດແລ້ວ</div>";
            $current_amount = $amount;
            $current_unit = $unit;
            $current_date = $record_date;
        } else {
            $msg = "<div class='msg error'>ເກີດຂໍ́ຜິດພາດ ບໍ່ສາມາດແກ້ໄຂຂໍ້ມູນໄດ້</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແກ້ໄຂຂໍ້ມູນສະຖິຕິ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 20px; color: #334155; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-wrap-center { width: 100%; max-width: 550px; }
        .form-container-custom { background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; font-size: 20px; font-weight: 700; text-align: center; color: #1e293b; }
        label { display: block; margin: 15px 0 6px 0; font-weight: bold; font-size: 14px; color: #475569; }
        input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; height: 46px; font-size: 14px; background: #fff; color: #334155; }
        .btn-submit-edit { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 25px; height: 46px; }
        .btn-submit-edit:hover { background: #1d4ed8; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
        .msg { padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 14px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
    </style>
</head>
<body>
    <div class="form-wrap-center">
        <div class="form-container-custom">
            <h2>✏️ ແກ້ໄຂຂໍ້ມູນສະຖິຕິພະແນກ</h2>
            <p style="text-align:center; color:#64748b; font-size:14px; margin-bottom:20px;">
                ຫົວຂໍ້: <strong style="color: #1e293b;"><?php echo htmlspecialchars($topic_title); ?></strong>
            </p>
            
            <?php echo $msg; ?>
            
            <form method="POST">
                <label>ຈຳນວນ / ຕົວເລກສະຖິຕິໃໝ່</label>
                <input type="number" name="amount" step="any" value="<?php echo htmlspecialchars($current_amount); ?>" required>

                <label>ຫົວໜ່ວຍວັດແทກ</label>
                <input type="text" name="unit" value="<?php echo htmlspecialchars($current_unit); ?>" required>

                <label>ວັນທີບັນທຶກຂໍ້ມູນ</label>
                <input type="date" name="record_date" value="<?php echo htmlspecialchars($current_date); ?>" required>

                <button type="submit" name="update_data" class="btn-submit-edit">💾 ບັນທຶກການແກ້ໄຂຂໍ້ມູນ</button>
            </form>
            
            <a href="insert_data.php" class="btn-back">⬅️ ກັບຄືນໜ້າບັນທຶກຂໍ້ມູນ</a>
        </div>
    </div>
</body>
</html>