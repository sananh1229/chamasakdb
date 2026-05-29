<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$msg = '';

$topic_query = $conn->query("SELECT title FROM topics WHERE id = $topic_id");
$topic_data = $topic_query->fetch_assoc();

$record_query = $conn->query("SELECT SUM(amount) as total_amount, unit FROM records WHERE topic_id = $topic_id AND YEAR(record_date) = '$selected_year'");
$record_data = $record_query->fetch_assoc();
$current_amount = $record_data['total_amount'] ?? 0;
$current_unit = $record_data['unit'] ?? 'ໂຕນ';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_data'])) {
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0) {
        $conn->query("DELETE FROM records WHERE topic_id = $topic_id AND YEAR(record_date) = '$selected_year'");
        $stmt = $conn->prepare("INSERT INTO records (topic_id, amount, unit, record_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("idss", $topic_id, $amount, $unit, $record_date);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php?year=" . $selected_year);
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>✏️ ແກ້ໄຂຂໍ້ມູນປີ <?php echo $selected_year; ?>: <?php echo htmlspecialchars($topic_data['title'] ?? 'ຫົວຂໍ້'); ?></h2>
        <?php echo $msg; ?>
        <form method="POST">
            <label>%d9%a5້ອນຈຳນວນຜົນຜະລິດໃໝ່ (ແກ້ໄຂທັບຄ່າເກົ່າຂອງປີນີ້)</label>
            <input type="number" name="amount" step="any" value="<?php echo number_format($current_amount, 2, '.', ''); ?>" required>

            <label>ຫົວໜ່ວຍວັດແທກ</label>
            <input type="text" name="unit" value="<?php echo htmlspecialchars($current_unit); ?>" required>

            <input type="hidden" name="record_date" value="<?php echo $selected_year . '-12-31'; ?>">

            <button type="submit" name="update_data">💾 ບັນທຶກການແກ້ໄຂຂໍ້ມູນ</button>
        </form>
        <center><a href="dashboard.php?year=<?php echo $selected_year; ?>" class="btn-back">← ຍົກເລີກ ແລະ ກັບຄືນ</a></center>
    </div>
</body>
</html>