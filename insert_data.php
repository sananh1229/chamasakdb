<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$msg = '';
$user_role = $_SESSION['role'];
$user_dept_id = intval($_SESSION['dept_id']); 

/* ==========================================================================
   1. ระบบประมวลผลการบันทึกข้อมูลใหม่ (INSERT) หรือ อัปเดตข้อมูลเก่า (UPDATE)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_data'])) {
    $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $topic_id = intval($_POST['topic_id']);
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0 && $amount >= 0 && !empty($record_date) && !empty($unit)) {
        if ($record_id > 0) {
            // ✏️ กรณีเป็นการแก้ไขข้อมูลเดิม (UPDATE)
            $update_query = "UPDATE records SET topic_id = $topic_id, amount = $amount, unit = '$unit', record_date = '$record_date' WHERE id = $record_id";
            if ($conn->query($update_query)) {
                $msg = "<div class='msg success'>✓ ອັບເດດແກ້ໄຂຂໍ້ມູນສະຖິຕິສຳເລັດແລ້ວ</div>";
            } else {
                $msg = "<div class='msg error'>❌ ເກີດຂໍ້ຜິດພາດ ບໍ່ສາມາດອັບເດດຂໍ້ມູນໄດ້</div>";
            }
        } else {
            // 🚀 กรณีเป็นการบันทึกข้อมูลใหม่ (INSERT)
            $insert_query = "INSERT INTO records (topic_id, amount, unit, record_date, created_at) VALUES ($topic_id, $amount, '$unit', '$record_date', NOW())";
            if ($conn->query($insert_query)) {
                $msg = "<div class='msg success'>✓ ບັນທຶກຂໍ້ມູນສະຖິຕິພະແນກສຳເລັດແລ້ວ</div>";
            } else {
                $msg = "<div class='msg error'>❌ ເກີดຂໍ້ຜິດພາດ ບໍ່ສາມາດບັນທຶກໄດ້</div>";
            }
        }
    }
}

/* ==========================================================================
   2. ระบบประมวลผลการลบข้อมูล (DELETE)
   ========================================================================== */
if (isset($_GET['del_record_id'])) {
    $del_id = intval($_GET['del_record_id']);
    if ($user_role === 'admin') {
        $check_del = $conn->query("SELECT id FROM records WHERE id = $del_id");
    } else {
        $check_del = $conn->query("SELECT r.id FROM records r JOIN topics t ON r.topic_id = t.id WHERE r.id = $del_id AND t.district_id = $user_dept_id");
    }
    if ($check_del && $check_del->num_rows > 0) {
        $conn->query("DELETE FROM records WHERE id = $del_id");
        $msg = "<div class='msg success'>✓ ລົບຂໍ້ມູນສະຖິຕິສຳເລັດແລ້ວ</div>";
    }
}

/* ==========================================================================
   3. ดึงข้อมูลหัวข้อกิจกรรมเพื่อส่งต่อไปยัง JavaScript (Link Cascade)
   ========================================================================== */
$topics_arr = [];
if ($user_role === 'admin') {
    $dept_res = $conn->query("SELECT * FROM districts ORDER BY id ASC");
    $top_res = $conn->query("SELECT id, title, work_group, district_id FROM topics ORDER BY title ASC");
} else {
    $top_res = $conn->query("SELECT id, title, work_group, district_id FROM topics WHERE district_id = $user_dept_id ORDER BY title ASC");
}
while($t = $top_res->fetch_assoc()) { $topics_arr[] = $t; }

/* ==========================================================================
   4. คิวรี่ดึงประวัติการบันทึกข้อมูลล่าสุด 40 รายการมาแสดงในหน้าเดียว
   ========================================================================== */
if ($user_role === 'admin') {
    $history_query = "SELECT r.id as record_id, t.id as topic_id, t.title, t.work_group, d.id as dept_id, d.name as dept_name, r.amount, r.unit, r.record_date 
                      FROM records r 
                      JOIN topics t ON r.topic_id = t.id 
                      JOIN districts d ON t.district_id = d.id 
                      ORDER BY r.id DESC LIMIT 40";
} else {
    $history_query = "SELECT r.id as record_id, t.id as topic_id, t.title, t.work_group, d.id as dept_id, d.name as dept_name, r.amount, r.unit, r.record_date 
                      FROM records r 
                      JOIN topics t ON r.topic_id = t.id 
                      JOIN districts d ON t.district_id = d.id 
                      WHERE t.district_id = $user_dept_id 
                      ORDER BY r.id DESC LIMIT 40";
}
$history_list = $conn->query($history_query);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກ ແລະ ແກ້ໄຂຂໍ້ມູນສະຖິຕິ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f4f6f9; margin: 0; color: #334155; }
        .wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar Menu Layout */
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #cbd5e1; padding: 20px; flex-shrink: 0; }
        .sidebar-brand { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center; padding-bottom: 15px; border-bottom: 2px solid #cbd5e1; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 8px; }
        .sidebar-item a { display: block; padding: 12px 15px; color: #475569; text-decoration: none; font-weight: 500; border-radius: 6px; }
        .sidebar-item.active a { background: #2563eb; color: #ffffff; font-weight: 600; }

        .main-content { flex: 1; padding: 30px; background: #ffffff; border-radius: 20px 0 0 20px; box-shadow: -5px 0 25px rgba(0,0,0,0.03); width: 100%; overflow: hidden; }
        h2 { font-size: 26px; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
        .sub-t { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        
        /* Form Box Section */
        .form-container { background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 35px; }
        .grid-form { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 768px) { .grid-form { grid-template-columns: 1fr 1fr; } }
        
        label { display: block; margin: 0 0 6px 0; font-weight: 600; font-size: 14px; color: #475569; }
        input, select { width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid #cbd5e1; height: 46px; font-size: 14px; background: #fff; transition: all 0.3s; color: #0f172a; }
        input:focus, select:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        
        .btn-save { background: #2563eb; color: #fff; font-weight: 600; border: none; cursor: pointer; height: 46px; border-radius: 8px; font-size: 15px; width: 100%; margin-top: 25px; transition: all 0.2s; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel-edit { display: none; background: #64748b; color: #fff; font-weight: 600; border: none; cursor: pointer; height: 46px; border-radius: 8px; font-size: 15px; width: 100%; margin-top: 10px; text-align: center; line-height: 46px; text-decoration: none; }
        
        .msg { padding: 14px; border-radius: 8px; text-align: center; font-weight: 600; margin-bottom: 25px; font-size: 14px; }
        .success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .error { background: #fff5f5; color: #e11d48; border: 1px solid #fecdd3; }
        
        /* Table Style */
        .table-container { width: 100%; overflow-x: auto; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #cbd5e1; font-size: 14px; }
        th { background: #e2e8f0; color: #1e293b; font-weight: 700; }
        tr:nth-child(even) { background-color: #f8fafc; }
        
        .btn-edit-action { background: #eab308; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-del-action { background: #fee2e2; color: #b91c1c; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; display: inline-block; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item"><a href="dashboard.php">ໜ້າຫຼັກ Dashboard</a></li>
                <li class="sidebar-item"><a href="province_summary.php">ສະຫຼຸບຍອດລວມສະສົມ</a></li>
                <li class="sidebar-item"><a href="compare_report.php">ໜ້າສະຫຼຸບສົມທຽບ</a></li>
                <li class="sidebar-item active"><a href="insert_data.php">บันທຶກຂໍ້ມູນໃໝ່</a></li>
                <?php if($user_role == 'admin'): ?>
                    <li class="sidebar-item"><a href="manage_structure.php">ຈັດການໂຄງສ້າງລະບົບ</a></li>
                    <li class="sidebar-item"><a href="add_user.php">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="main-content">
            <h2 id="form-title">ບັນທຶກຂໍ້ມູນສະຖິຕິປະຈຳວັນ</h2>
            <div class="sub-t">ປ້ອນຂໍ້ມູນໃໝ່ ຫຼື ຄລິກປຸ່ມແກ້ໄຂລາຍການດ້ານລຸ່ມເພື່ອອັບເດດຂໍ້ມູນໃນໜ້ານີ້ໄດ້ທັນທີ</div>
            
            <?php echo $msg; ?>
            
            <div class="form-container">
                <form method="POST" action="insert_data.php" id="mainForm">
                    <input type="hidden" name="record_id" id="record_id" value="0">

                    <div class="grid-form">
                        <?php if($user_role === 'admin'): ?>
                            <div>
                                <label>1. ເລືອກພະແນກ / ສັງກັດ / ເມືອງ</label>
                                <select id="department" onchange="filterWorkGroups()" required>
                                    <option value="">-- ກະລຸນາເລືອກພະແນກ --</option>
                                    <?php while($d = $dept_res->fetch_assoc()) { echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>"; } ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label><?php echo ($user_role === 'admin') ? '2.' : '1.'; ?> ເລືອກກຸ່ມວຽກງານ</label>
                            <select id="work_group" onchange="filterTopicsByGroup()" required>
                                <option value="">-- ເລືອກກຸ່ມວຽກງານ --</option>
                                <option value="ກຸ່ມວຽກການεມືອງແנວຄິດ">ກຸ່ມວຽກການເມືອງແנວຄິດ</option>
                                <option value="ກຸ່ມວຽກ ປກຊ-ປກສ">ກຸ່ມວຽກ ປກຊ-ປກສ</option>
                                <option value="ກຸ່ມວຽກເສດຖະກິດ">ກຸ່ມວຽກເສດຖະກິດ</option>
                                <option value="ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ">ກຸ່ມວຽກວັດທະນະທຳສັງຄົມ</option>
                            </select>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label>ເລືອກຫົວຂໍ້ກິດຈະກຳຕົວຊີ້ວັດ</label>
                            <select name="topic_id" id="topic" required>
                                <option value="">-- ກະລຸນາເລືອກກຸ່ມວຽກງານກ່ອນ --</option>
                            </select>
                        </div>

                        <div>
                            <label>ປ້ອນຕົວເລกຈຳນວນສະຖິຕິ</label>
                            <input type="number" name="amount" id="amount" step="any" required placeholder="0.00">
                        </div>

                        <div>
                            <label>ຫົວໜ່ວຍວັດແທກ</label>
                            <input type="text" name="unit" id="unit" required placeholder="ຕົວຢ່າງ: ເທື່ອ, ແຫ່ງ, ໂຕນ, ຄົນ">
                        </div>

                        <div>
                            <label>ວັນທີບັນທຶກຂໍ້ມູນ</label>
                            <input type="date" name="record_date" id="record_date_val" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="save_data" id="submit-btn" class="btn-save">ບັນທຶກຂໍ້ມູນສະຖິຕິ</button>
                    <a href="insert_data.php" id="cancel-btn" class="btn-cancel-edit">ຍົກເລີກການແກ້ໄຂ</a>
                </form>
            </div>

            <h3>ປະຫວັດການບັນທຶກຂໍ້ມູນ ແລະ ການຈັດການ</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ພະແนກສັງກັດ</th>
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
                                    <td><strong><?php echo htmlspecialchars($h['dept_name']); ?></strong></td>
                                    <td><span style="color: #2563eb; font-weight: 600;"><?php echo htmlspecialchars($h['work_group']); ?></span></td>
                                    <td style="white-space: normal; min-width: 200px;"><?php echo htmlspecialchars($h['title']); ?></td>
                                    <td style="text-align: right; font-weight: 700; color: #16a34a;"><?php echo number_format($h['amount'], 2); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($h['unit']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($h['record_date'])); ?></td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-edit-action" onclick="startEdit(<?php echo htmlspecialchars(json_encode($h)); ?>)">ແກ້ໄຂ</button>
                                        <a href="insert_data.php?del_record_id=<?php echo $h['record_id']; ?>" class="btn-del-action" onclick="return confirm('ຕ້ອງການລົບແທ້ບໍ່?')">ລົບ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding:30px;">ບໍ່ມີປະຫວັດການບັນທຶກຂໍ້ມູນ</td></tr>
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

        function filterTopicsByGroup(callback = null) {
            const groupVal = document.getElementById('work_group').value;
            const topicSelect = document.getElementById('topic');
            topicSelect.innerHTML = '<option value="">-- ເລືອກຫົວຂໍ້ກິດຈະກຳ --</option>';
            
            let filtered = [];
            if (is_admin) {
                const deptId = document.getElementById('department').value;
                if (!deptId && !callback) { alert('ກະລຸນາເລືອກພະແนກກ່ອນ'); document.getElementById('work_group').value = ""; return; }
                filtered = topics.filter(t => t.work_group === groupVal && t.district_id == deptId);
            } else {
                filtered = topics.filter(t => t.work_group === groupVal);
            }

            if(filtered.length > 0) {
                filtered.forEach(t => {
                    const opt = document.createElement('option'); opt.value = t.id; opt.textContent = t.title; topicSelect.appendChild(opt);
                });
            } else {
                topicSelect.innerHTML = '<option value="">-- ບໍ່ມີຫົວຂໍ້ໃນກຸ່ມວຽກນີ້ --</option>';
            }

            if (typeof callback === 'function') callback();
        }

        // 🌟 ฟังก์ชันดึงข้อมูลจากตารางด้านล่าง ส่งขึ้นไปเปิดโหมดแก้ไขที่ฟอร์มด้านบนทันที
        function startEdit(data) {
            document.getElementById('form-title').innerText = "✏️ ແກ້ໄຂຂໍ້ມູນສະຖິຕິ";
            document.getElementById('record_id').value = data.record_id;
            document.getElementById('amount').value = data.amount;
            document.getElementById('unit').value = data.unit;
            document.getElementById('record_date_val').value = data.record_date;
            document.getElementById('submit-btn').innerText = "💾 ອັບເດດການແກ້ໄຂຂໍ້ມູນ";
            document.getElementById('cancel-btn').style.display = "block";

            if (is_admin) {
                document.getElementById('department').value = data.dept_id;
            }
            document.getElementById('work_group').value = data.work_group;
            
            // สั่งโหลดข้อมูลหัวข้อให้สัมพันธ์กันก่อนทำการเลือกค่าเดิมให้ตรงล็อก
            filterTopicsByGroup(() => {
                document.getElementById('topic').value = data.topic_id;
            });

            // เลื่อนหน้าจอกลับขึ้นไปด้านบนอย่างนุ่มนวล
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>