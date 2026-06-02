<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
// 🌟 ล็อกค่าปีที่ส่งต่อมาจากหน้าหลัก เพื่อควบคุมการบันทึกแยกปี
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

    if ($topic_id > 0) {
        // 🌟 เคลียร์ข้อมูลเก่าเฉพาะปีที่กำลังแก้ไข เพื่อให้ค่าใหม่เป็นค่าล่าสุดจริงของปีนั้น ไม่สะสมปนกัน
        $conn->query("DELETE FROM records WHERE topic_id = $topic_id AND YEAR(record_date) = '$selected_year'");
        
        $record_date = $selected_year . "-12-31"; // บันทึกลงวันสิ้นปีของปีนั้นๆ
        $stmt = $conn->prepare("INSERT INTO records (topic_id, amount, unit, record_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("idss", $topic_id, $amount, $unit, $record_date);
        
        if ($stmt->execute()) {
            // บันทึกเสร็จ ส่งกลับหน้าแรกพร้อมรักษาค่าปีเดิมที่กำลังดูอยู่
            header("Location: dashboard.php?year=" . $selected_year);
            exit();
        } else {
            $msg = "<div class='msg error'>❌ ເກີດຂໍ້ຜິດພາດໃນການແກ້ໄຂຂໍ້ມູນ.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແກ້ໄຂຂໍ້ມູນປະຈຳປີ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-wrap-center { display: flex; justify-content: center; align-items: center; min-height: 80vh; width: 100%; }
        .form-container-custom { background: #ffffff; border: 1px solid #e2e8f0; padding: 30px; border-radius: 16px; width: 100%; max-width: 500px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        .form-container-custom h2 { margin-bottom: 20px; color: #0f172a; font-size: 18px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; text-align: center; }
        .form-container-custom label { display: block; margin-top: 14px; margin-bottom: 6px; color: #475569; font-size: 14px; font-weight: 600; }
        .form-container-custom input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc; }
        .btn-submit-edit { width: 100%; padding: 14px; background-color: #ecc94b; color: #1a202c; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 25px; }
        .btn-submit-edit:hover { background-color: #d69e2e; }
    </style>
</head>
<body>
    <div class="form-wrap-center">
        <div class="form-container-custom">
            <h2>✏️ ແກ້ໄຂຂໍ້ມູນປີ <span style="color:#3182ce; font-weight:bold; text-decoration:underline;"><?php echo $selected_year; ?></span></h2>
            <p style="text-align:center; color:#64748b; font-size:14px; margin-bottom:15px;">ຫົວຂໍ້: <strong><?php echo htmlspecialchars($topic_data['title'] ?? ''); ?></strong></p>
            
            <?php echo $msg; ?>
            
            <form method="POST">
                <label>ປ້ອນຈຳນວນຜົນผลิตໃໝ່ (ຂຽນທັບຂໍ້ມູນເກົ່າຂອງປີນີ້)</label>
                <input type="number" name="amount" step="any" value="<?php echo number_format($current_amount, 2, '.', ''); ?>" required>

                <label>ຫົວໜ່ວຍວັດແທກ (Unit)</label>
                <input type="text" name="unit" value="<?php echo htmlspecialchars($current_unit); ?>" required>

                <button type="submit" name="update_data" class="btn-submit-edit">💾 ບັນທຶກການແກ້ໄຂຂໍ້ມູນ</button>
            </form>
            <center style="margin-top:20px;"><a href="dashboard.php?year=<?php echo $selected_year; ?>" class="btn-back">← ຍົກເລີກ ແລະ ກັບຄືນ</a></center>
        </div>
    </div>
</body>
</html>