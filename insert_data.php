<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_data'])) {
    $topic_id = intval($_POST['topic_id']);
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0 && $amount >= 0 && !empty($record_date) && !empty($unit)) {
        $stmt = $conn->prepare("INSERT INTO records (topic_id, amount, unit, record_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $topic_id, $amount, $unit, $record_date);
        if ($stmt->execute()) {
            $msg = "<p class='msg success'>ບັນທຶກຂໍ້ມູນສຳເລັດແລ້ວ!</p>";
        } else {
            $msg = "<p class='msg error'>ເກິດການຜິດພາດໃນการບັນທຶກ.</p>";
        }
    }
}

$dept_id_user = isset($_SESSION['dept_id']) ? intval($_SESSION['dept_id']) : 0;
$role = $_SESSION['role'];

if ($role == 'admin') {
    $dept_query = $conn->query("SELECT * FROM departments");
    $topic_query = $conn->query("SELECT * FROM topics");
} else {
    $dept_query = $conn->query("SELECT * FROM departments WHERE id = $dept_id_user");
    $topic_query = $conn->query("SELECT * FROM topics WHERE department_id = $dept_id_user");
}

$topics_arr = [];
while ($t = $topic_query->fetch_assoc()) {
    $topics_arr[] = $t;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding: 15px; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { background: #ffffff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        h2 { border-bottom: 1px solid #edf2f7; padding-bottom: 12px; margin-top: 0; color: #1a202c; font-size: 20px; }
        label { display: block; margin: 15px 0 5px; color: #4a5568; font-size: 14px; font-weight: 600; }
        select, input, button { width: 100%; padding: 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #333; border-radius: 6px; font-size: 16px; transition: all 0.3s; }
        select:focus, input:focus { border-color: #3182ce; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2); }
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
        <h2>+ ບັນທຶກຂໍ້ມູນຜົນຜະລິດ</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <label>ເລືອກພະແນກ</label>
            <select id="department" onchange="filterTopics()" required>
                <?php if($role == 'admin'): ?><option value="">-- ເລືອກພະແນກ --</option><?php endif; ?>
                <?php while($d = $dept_query->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>

            <label>ເລືອກຫົວຂໍ້/ກິດຈະກຳ (Topic)</label>
            <select name="topic_id" id="topic" required>
                <option value="">-- ກະລຸນາເລືອກພະແນກກ່ອນ --</option>
            </select>

            <label>ຈຳນວນ / ຜົນຜະລິດ</label>
            <input type="number" name="amount" step="any" placeholder="0.00" required>

            <label>ຫົວໜ່ວຍ (ກະລຸນາລະບຸ ເຊັ່ນ: ໂຕນ, ກิໂລ, ຄົນ, ກີບ)</label>
            <input type="text" name="unit" placeholder="ຕົວຢ່າງ: ໂຕນ" required>

            <label>ວັນທີບັນທຶກ</label>
            <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="save_data">ບັນທຶກຂໍ້ມູນ</button>
        </form>
        <a href="dashboard.php" class="btn-back">← ກັບໄປໜ້າຫຼັກ Dashboard</a>
    </div>

    <script>
        const topics = <?php echo json_encode($topics_arr); ?>;
        function filterTopics() {
            const deptId = document.getElementById('department').value;
            const topicSelect = document.getElementById('topic');
            topicSelect.innerHTML = '<option value="">-- ເລືອກຫົວຂໍ້ --</option>';
            
            const filtered = topics.filter(t => t.department_id == deptId);
            filtered.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.title;
                topicSelect.appendChild(opt);
            });
        }
        window.onload = function() { filterTopics(); };
    </script>
</body>
</html>