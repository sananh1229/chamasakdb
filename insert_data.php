<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$msg = '';
$user_role = $_SESSION['role'];
$user_dist_id = intval($_SESSION['dept_id']); // ไอดีนคร/เมืองของผู้ล็อกอิน

/* ==========================================================================
   1. ระบบประมวลผลการบันทึกข้อมูลใหม่ (INSERT)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_data'])) {
    $topic_id = intval($_POST['topic_id']);
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0 && $amount >= 0 && !empty($record_date) && !empty($unit)) {
        $insert_query = "INSERT INTO records (topic_id, amount, unit, record_date, created_at) VALUES ($topic_id, $amount, '$unit', '$record_date', NOW())";
        if ($conn->query($insert_query)) {
            $msg = "<div class='msg success'>ບັນທຶກຂໍ້ມູນສຳເລັດແລ້ວ</div>";
        } else {
            $msg = "<div class='msg error'>ເກີດຄວາມຜິດພາດບໍ່ສາມາດແກ້ໄຂຂໍ້ມູນໄດ້/div>";
        }
    }
}

/* ==========================================================================
   2. ระบบประมวลผลการลบข้อมูล (DELETE)
   ========================================================================== */
if (isset($_GET['del_record_id'])) {
    $del_id = intval($_GET['del_record_id']);
    
    // ตรวจสอบสิทธิ์ก่อนลบข้อมูล (User ทั่วไปลบได้เฉพาะเมืองของตนเอง)
    if ($user_role === 'admin') {
        $check_del = $conn->query("SELECT id FROM records WHERE id = $del_id");
    } else {
        $check_del = $conn->query("SELECT r.id FROM records r JOIN topics t ON r.topic_id = t.id WHERE r.id = $del_id AND t.district_id = $user_dist_id");
    }

    if ($check_del && $check_del->num_rows > 0) {
        if ($conn->query("DELETE FROM records WHERE id = $del_id")) {
            $msg = "<div class='msg success'>ลบข้อมูลสถิติที่เลือกเรียบร้อยแล้ว</div>";
        }
    } else {
        $msg = "<div class='msg error'>คุณไม่มีสิทธิ์ในการลบข้อมูลรายการนี้</div>";
    }
}

/* ==========================================================================
   3. ดึงรายการหัวข้อกิจกรรมทั้งหมด เพื่อไปแยกกรองตามกลุ่มงานในฟอร์มหน้าเว็บ
   ========================================================================== */
$topics_arr = [];
if ($user_role === 'admin') {
    $dist_res = $conn->query("SELECT * FROM districts ORDER BY id ASC");
    $top_res = $conn->query("SELECT id, title, work_group, district_id FROM topics ORDER BY title ASC");
} else {
    $top_res = $conn->query("SELECT id, title, work_group, district_id FROM topics WHERE district_id = $user_dist_id ORDER BY title ASC");
}
while($t = $top_res->fetch_assoc()) { $topics_arr[] = $t; }

/* ==========================================================================
   4. ดึงประวัติรายการที่บันทึกล่าสุดมาแสดงในตารางด้านล่างเพื่อความสะดวก
   ========================================================================== */
if ($user_role === 'admin') {
    $history_query = "SELECT r.id as record_id, t.title, t.work_group, d.name as dist_name, r.amount, r.unit, r.record_date 
                      FROM records r 
                      JOIN topics t ON r.topic_id = t.id 
                      JOIN districts d ON t.district_id = d.id 
                      ORDER BY r.id DESC LIMIT 50";
} else {
    $history_query = "SELECT r.id as record_id, t.title, t.work_group, d.name as dist_name, r.amount, r.unit, r.record_date 
                      FROM records r 
                      JOIN topics t ON r.topic_id = t.id 
                      JOIN districts d ON t.district_id = d.id 
                      WHERE t.district_id = $user_dist_id 
                      ORDER BY r.id DESC LIMIT 50";
}
$history_list = $conn->query($history_query);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกข้อมูลสถิติประจำวัน</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f8fafc; margin: 0; color: #334155; padding: 0; }
        .wrapper { display: flex; min-height: 100vh; position: relative; }
        .main-content { flex: 1; padding: 20px; background: #ffffff; width: 100%; overflow: hidden; }
        
        h2 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 5px 0; }
        h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 30px 0 15px 0; padding-left: 5px; border-left: 4px solid #3182ce; }
        
        .form-container { max-width: 100%; background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 15px; margin-bottom: 35px; }
        .grid-form { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media (min-width: 768px) { .grid-form { grid-template-columns: 1fr 1fr; } }
        
        label { display: block; margin: 5px 0 6px 0; font-weight: bold; font-size: 14px; color: #475569; }
        input, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; height: 46px; font-size: 14px; background: #fff; color: #334155; transition: all 0.3s; }
        input:focus, select:focus { border-color: #3182ce; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15); }
        
        .btn-save { background: #3182ce; color: #fff; font-weight: bold; border: none; cursor: pointer; height: 46px; border-radius: 8px; font-size: 15px; width: 100%; margin-top: 20px; transition: background 0.2s; }
        .btn-save:hover { background: #2b6cb0; }
        
        .msg { padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 14px; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; white-space: nowrap; }
        th { background: #f1f5f9; color: #475569; font-weight: 700; }
        tr:hover { background-color: #f8fafc; }
        
        .btn-edit-action { background: #ecc94b; color: #1a202c; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-edit-action:hover { background: #d69e2e; }
        .btn-del-action { background: #f56565; color: #fff; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-block; }
        .btn-del-action:hover { background: #e53e3e; }
        
        @media (max-width: 768px) { .wrapper { flex-direction: column; } }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">ເມນູ</button>
    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item active"><a href="insert_data.php">ບັນທຶກຂໍ້ມູນໃໝ່</a></li>
                <?php if($user_role == 'admin'): ?>
                    <li class="sidebar-item"><a href="manage_structure.php">ຈັດການໂຄງສ້າງລະບົບ</a></li>
                    <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                    <a href="logout.php" style="background: #f56565; color: #fff; text-align: center;">ອອກຈາກລະບົບ</a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>ບັນທຶກຂໍ້ມູນສະຖິຕິປະຈຳວັນ</h2>
            <p style="color: #64748b; margin-top: 0; margin-bottom: 25px; font-size: 14px;">ເລືອກກຸ່ມວຽກ ແລະ ຫົວຂໍ້ກິດຈະກຳທັງໝົດເພື່ອປ້ອນຈຳນວນສະຖິຕິ</p>
            
            <?php echo $msg; ?>
            
            <div class="form-container">
                <form method="POST" action="insert_data.php">
                    <div class="grid-form">
                        
                        <?php if($user_role === 'admin'): ?>
                            <div>
                                <label>1. ເລືອກນະຄອນ/ເມືອງ</label>
                                <select id="district" onchange="filterWorkGroups()" required>
                                    <option value="">-- ເລືອກນະຄອນ/ເມືອງ --</option>
                                    <?php while($d = $dist_res->fetch_assoc()) { echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>"; } ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label><?php echo ($user_role === 'admin') ? '2.' : '1.'; ?> ເລືອກກຸ່ມວຽກ</label>
                            <select id="work_group" onchange="filterTopicsByGroup()" required>
                                <option value="">-- ເລືອກກຸ່ມວຽກ --</option>
                                <option value="ກຸ່ມວຽກການເມືອງແנວຄິດ">ກຸ່ມວຽກການເມືອງແນວຄິດ</option>
                                <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                                <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກິດ</option>
                                <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                            </select>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label>ເລືອກຫົວຂໍ້ກິດຈະກຳ (ສະແດງທັງໝົດຕາມກຸ່ມວຽກ)</label>
                            <select name="topic_id" id="topic" required>
                                <option value="">-- ກະລຸນາເລືອກກຸ່ມວຽກກ່ອນ --</option>
                            </select>
                        </div>

                        <div>
                            <label>ປ້ອນຕົວເລກ / ຈຳນວນ</label>
                            <input type="number" name="amount" step="any" required placeholder="0.00">
                        </div>

                        <div>
                            <label>ຫົວໜ່ວຍວັດແທກ</label>
                            <input type="text" name="unit" required placeholder="ໂຕນ, ກິໂລ, ເທື່ອ, ແຫ່ງ...">
                        </div>

                        <div>
                            <label>ວັນທີບັນທຶກຂໍ້ມູນ</label>
                            <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="save_data" class="btn-save">ບັນທຶກຂໍ້ມູນສະຖິຕິ</button>
                </form>
            </div>

            <h3>ປະຫວັດການບັນທຶກຂໍ້ມູນສະຖິຕິຫຼ້າສຸດ</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ນະຄອນ/ເມືອງ</th>
                            <th>ກຸ່ມວຽກ</th>
                            <th>ຫົວຂໍ້ກິດຈະກຳ</th>
                            <th style="text-align: right;">ຈຳນວນ</th>
                            <th style="text-align: center;">ຫົວໜ່ວຍ</th>
                            <th>ວັນທີບັນທຶກ</th>
                            <th style="text-align: center; width: 160px;">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_list && $history_list->num_rows > 0): ?>
                            <?php while($h = $history_list->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($h['dist_name']); ?></strong></td>
                                    <td><span style="color: #3182ce; font-weight: bold;"><?php echo htmlspecialchars($h['work_group']); ?></span></td>
                                    <td style="white-space: normal; min-width: 200px;"><?php echo htmlspecialchars($h['title']); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #2f855a;"><?php echo number_format($h['amount'], 2); ?></td>
                                    <td style="text-align: center; font-weight: bold; color: #475569;"><?php echo htmlspecialchars($h['unit']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($h['record_date'])); ?></td>
                                    <td style="text-align: center;">
                                        <a href="edit_record.php?id=<?php echo $h['record_id']; ?>" class="btn-edit-action">ແກ້ໄຂ</a>
                                        <a href="insert_data.php?del_record_id=<?php echo $h['record_id']; ?>" class="btn-del-action" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບລາຍການບັນທຶກນີ້?')">ລົບ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b;">ບໍ່ມີປະຫວັດການບັນທຶกຂໍ້ມູນໃນລະບົບ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        const topics = <?php echo json_encode($topics_arr); ?>;
        const is_admin = <?php echo ($user_role === 'admin') ? 'true' : 'false'; ?>;

        function filterWorkGroups() {
            document.getElementById('work_group').value = "";
            document.getElementById('topic').innerHTML = '<option value="">-- ກະລຸນາເລືອກກຸ່ມວຽກກ່ອນ --</option>';
        }

        function filterTopicsByGroup() {
            const groupVal = document.getElementById('work_group').value;
            const topicSelect = document.getElementById('topic');
            topicSelect.innerHTML = '<option value="">-- ເລືອກຫົວຂໍ້ກິດຈະກຳ --</option>';
            
            let filtered = [];
            
            if (is_admin) {
                const distId = document.getElementById('district').value;
                if (!distId) {
                    alert('ກະລຸນາເລືອກນະຄອນ/ເມືອງກ່ອນ');
                    document.getElementById('work_group').value = "";
                    return;
                }
                filtered = topics.filter(t => t.work_group === groupVal && t.district_id == distId);
            } else {
                filtered = topics.filter(t => t.work_group === groupVal);
            }

            if(filtered.length > 0) {
                filtered.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.title;
                    topicSelect.appendChild(opt);
                });
            } else {
                topicSelect.innerHTML = '<option value="">-- ບໍ່ມີຫົວຂໍ້ໃນກຸ່ມວຽກນີ້ --</option>';
            }
        }

        document.getElementById('menuToggle').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
            document.getElementById('menuToggle').innerText = sidebar.classList.contains('open') ? '✕ ປິດ' : '☰ ເມນູ';
        });
    </script>
</body>
</html>