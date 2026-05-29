<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$current_year = date('Y');
$msg = '';

$topic_query = $conn->query("SELECT title FROM topics WHERE id = $topic_id");
$topic_data = $topic_query->fetch_assoc();

$record_query = $conn->query("SELECT SUM(amount) as total_amount, unit FROM records WHERE topic_id = $topic_id AND YEAR(record_date) = '$current_year'");
$record_data = $record_query->fetch_assoc();
$current_amount = $record_data['total_amount'] ?? 0;
$current_unit = $record_data['unit'] ?? 'ໂຕນ';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_data'])) {
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0) {
        $conn->query("DELETE FROM records WHERE topic_id = $topic_id AND YEAR(record_date) = '$current_year'");
        $stmt = $conn->prepare("INSERT INTO records (topic_id, amount, unit, record_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("idss", $topic_id, $amount, $unit, $record_date);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $msg = "<p class='msg error'>ເກີດຂໍ້ຜິດພາດໃນການແກ້ໄຂຂໍ້ມູນ.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແກ້ໄຂຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding: 15px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { background: #ffffff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        h2 { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-top: 0; color: #1a202c; font-size: 18px; }
        label { display: block; margin: 15px 0 5px; color: #4a5568; font-size: 14px; font-weight: 600; }
        input, button { width: 100%; padding: 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #333; border-radius: 6px; font-size: 15px; }
        input:focus { border-color: #3182ce; background: #fff; outline: none; }
        button { background: #ecc94b; color: #1a202c; font-weight: bold; border: none; cursor: pointer; margin-top: 20px; }
        button:hover { background: #dd6b20; color: #fff; }
        .btn-back { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>✏️ ແກ້ໄຂຂໍ້ມູນ: <?php echo htmlspecialchars($topic_data['title'] ?? 'ຫົວຂໍ́'); ?></h2>
        <?php echo $msg; ?>
        <form method="POST">
            <label>ປ້ອນຈຳນວນຜົນຜະລິດໃໝ່ (ແກ້ໄຂທັບຄ່າເກົ່າ)</label>
            <input type="number" name="amount" step="any" value="<?php echo number_format($current_amount, 2, '.', ''); ?>" required>

            <label>ຫົວໜ່ວຍວັດແທກ</label>
            <input type="text" name="unit" value="<?php echo htmlspecialchars($current_unit); ?>" required>

            <label>ວັນທີບັນທຶກຂໍ້ມູນ</label>
            <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="update_data">💾 ບັນທຶກການແກ້ໄຂຂໍ້ມູນ</button>
        </form>
        <center><a href="dashboard.php" class="btn-back">← ຍົກເລີກ ແລະ ກັບຄືນ</a></center>
    </div>
</body>
</html>