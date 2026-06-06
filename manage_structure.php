<?php
include 'db.php';

// ตัดสิทธิ์ User: ล็อกให้เฉพาะสิทธิ์ admin เท่านั้นที่เข้าถึงหน้านี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit(); 
}

$msg = '';

// เพิ่ม นคร/เมือง ใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_district'])) {
    $district_name = trim($conn->real_escape_string($_POST['district_name']));
    if (!empty($district_name)) {
        $check_dist = $conn->query("SELECT id FROM districts WHERE name = '$district_name'");
        if ($check_dist->num_rows > 0) {
            $msg = "<div class='msg error'>ຊື່ນະຄອນ/ເມືອງນີ້ມີໃນລະບົບແລ້ວ ກະລຸນາປ່ຽนໃໝ່</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO districts (name) VALUES (?)");
            $stmt->bind_param("s", $district_name);
            if ($stmt->execute()) {
                $msg = "<div class='msg success'>ບັນທຶກນະຄອນ/ເມືອງໃໝ່ສຳເລັດແລ້ວ</div>";
            }
        }
    }
}

// ลบ นคร/เมือง
if (isset($_GET['delete_dist_id'])) {
    $del_dist_id = intval($_GET['delete_dist_id']);
    $check_relation = $conn->query("SELECT id FROM topics WHERE district_id = $del_dist_id");
    if ($check_relation->num_rows > 0) {
        $msg = "<div class='msg error'>ບໍ່ສາມາດລົບໄດ້ ເນື່ອງຈາກມີຫົວຂໍ້ກິດຈະກຳຜູກກັບນະຄອນ/ເມືອງນີ້ຢູ່</div>";
    } else {
        $conn->query("DELETE FROM districts WHERE id = $del_dist_id");
        $msg = "<div class='msg success'>ລົບນະຄອນ/ເມືອງອອກຈາກລະບົບສຳເລັດແລ້ວ</div>";
    }
}

// เพิ่มหัวข้อกิจกรรมใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_topic'])) {
    $title = trim($conn->real_escape_string($_POST['title']));
    $district_id = intval($_POST['district_id']);
    $work_group = trim($conn->real_escape_string($_POST['work_group']));

    if (!empty($title) && $district_id > 0 && !empty($work_group)) {
        $stmt = $conn->prepare("INSERT INTO topics (title, district_id, work_group) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $title, $district_id, $work_group);
        if ($stmt->execute()) {
            $msg = "<div class='msg success'>ບັນທຶກຫົວຂໍ້ກິດຈະກຳໃໝ່ສຳເລັດແລ້ວ</div>";
        }
    }
}

$districts_dropdown = $conn->query("SELECT * FROM districts ORDER BY id ASC");
$districts_list = $conn->query("SELECT * FROM districts ORDER BY id DESC");
$topics_list = $conn->query("SELECT t.id, t.title, t.work_group, d.name as dist_name FROM topics t JOIN districts d ON t.district_id = d.id ORDER BY t.id DESC");
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການໂຄງສ້າງລະບົບ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; padding: 15px; color: #334155; }
        .container { max-width: 1100px; margin: auto; }
        .grid-layout { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 768px) { .grid-layout { grid-template-columns: 1fr 1fr; } }
        .form-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        h2, h3 { margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-weight: 700; }
        label { display: block; margin: 12px 0 6px 0; font-weight: bold; font-size: 14px; color: #475569; }
        input[type="text"], select { width: 100%; padding: 11px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; height: 44px; background: #fff; color: #334155; }
        button { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; font-size: 15px; }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; text-align: center; font-size: 14px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .table-responsive { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 450px; }
        th, td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        th { background: #f8fafc; color: #64748b; font-weight: 700; }
        .btn-del { background: #f56565; color: #fff; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #334155; font-weight: bold; }
        @media (max-width: 768px) { .filter-form { flex-direction: column; } }
    </style>
</head>
<body>
<div class="container">
    <h2>ຈັດການໂຄງສ້າງລະບົບ (ນະຄອນ/ເມືອງ & ຫົວຂໍ້ກິດຈະກຳ)</h2>
    <?php echo $msg; ?>

    <div class="grid-layout">
        <div>
            <div class="form-box">
                <h3>ເພີ່ມນະຄອນ/ເມືອງໃໝ່</h3>
                <form method="POST">
                    <label>ຊື່ນະຄອນ/ເມືອງ</label>
                    <input type="text" name="district_name" placeholder="ປ້ອນຊື່ນະຄອນ/ເມືອງໃໝ່..." required>
                    <button type="submit" name="add_dist">ບັນທຶກນະຄອນ/ເມືອງ</button>
                </form>
            </div>
            <h3>ລາຍການນະຄອນ/ເມືອງທັງໝົດ</h3>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>ຊື່ນະຄອນ/ເມືອງ</th><th style="text-align: center; width: 80px;">ຈັດການ</th></tr></thead>
                    <tbody>
                        <?php while($d = $districts_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['name']); ?></td>
                                <td style="text-align: center;"><a href="manage_structure.php?delete_dist_id=<?php echo $d['id']; ?>" class="btn-del" onclick="return confirm('ທ່ານແנ່ໃຈບໍ່ວ່າຕ້ອງການລົບນະຄອນ/ເມືອງນີ້?')">ລົບ</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="form-box">
                <h3>ເພີ່ມຫົວຂໍ້ກິດຈະກຳໃໝ່</h3>
                <form method="POST">
                    <label>ຊື່ຫົວຂໍ້ກິດຈະກຳ</label>
                    <input type="text" name="title" placeholder="ປ້ອນຊື່ຫົວຂໍ້..." required>
                    <label>ສັງກັດນະຄອນ/ເມືອງ</label>
                    <select name="district_id" required>
                        <option value="">-- ເລືອກນະຄອນ/เມືອງ --</option>
                        <?php while($dd = $districts_dropdown->fetch_assoc()) { echo "<option value='{$dd['id']}'>" . htmlspecialchars($dd['name']) . "</option>"; } ?>
                    </select>
                    <label>ເລືອກກຸ່ມວຽກ</label>
                    <select name="work_group" required>
                        <option value="ກຸ່ມວຽກການເມືອງແນວຄິດ">ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                        <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                        <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກິດ</option>
                        <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                    </select>
                    <button type="submit" name="add_topic">ບັນທຶກຫົວຂໍ້</button>
                </form>
            </div>
            <h3>ລາຍການຫົວຂໍ້ກິດຈະກຳ</h3>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>ຫົວຂໍ້</th><th>ນະຄອນ/ເມືອງ</th><th>ກຸ່ມວຽກ</th></tr></thead>
                    <tbody>
                        <?php while($t = $topics_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['title']); ?></td>
                                <td><?php echo htmlspecialchars($t['dist_name']); ?></td>
                                <td><span style="color: #3182ce; font-weight: bold;"><?php echo htmlspecialchars($t['work_group']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn-back">ກັບ Dashboard</a>
</div>
</body>
</html>