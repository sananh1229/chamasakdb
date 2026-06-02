<?php
include 'db.php';

// ตรวจสอบสิทธิ์การเข้าใช้งาน (ต้อง Login แล้วเท่านั้น)
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$msg = '';

// 🌟 ระบบประมวลผลการบันทึกข้อมูลใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_data'])) {
    $topic_id = intval($_POST['topic_id']);
    $amount = floatval($_POST['amount']);
    $unit = trim($conn->real_escape_string($_POST['unit']));
    $record_date = $conn->real_escape_string($_POST['record_date']);

    if ($topic_id > 0 && $amount >= 0 && !empty($record_date) && !empty($unit)) {
        // ใช้คำสั่ง INSERT ดั้งเดิมเพื่อความแม่นยำในการทำงานร่วมกับโครงสร้างฐานข้อมูลเดิมของคุณ
        $insert_query = "INSERT INTO records (topic_id, amount, unit, record_date, created_at) 
                         VALUES ($topic_id, $amount, '$unit', '$record_date', NOW())";
        
        if ($conn->query($insert_query)) {
            $msg = "<div class='msg success'>✅ ບັນທຶກຂໍ້ມູນຜົນຜະລິດສຳເລັດແລ້ວ!</div>";
        } else {
            $msg = "<div class='msg error'>❌ ບໍ່ສາມາດບັນທຶກໄດ້: " . $conn->error . "</div>";
        }
    } else {
        $msg = "<div class='msg error'>❌ ກະລຸນາປ້อนຂໍ້ມູນໃຫ້ຄົບຖ້ວນ ແລະ ຖືກຕ້ອງ</div>";
    }
}

// 🌟 จัดสิทธิ์การดึงแผนกและหัวข้อตาม Role ของผู้ใช้งาน
$dept_id_user = isset($_SESSION['dept_id']) ? intval($_SESSION['dept_id']) : 0;
$role = $_SESSION['role'];

if ($role == 'admin') {
    // แอดมิน ดึงข้อมูลแผนกทั้งหมดขึ้นมาเลือก
    $dept_query = $conn->query("SELECT * FROM departments ORDER BY id ASC");
    $topics_query = $conn->query("SELECT id, department_id, title FROM topics ORDER BY id ASC");
} else {
    // ยูสเซอร์ทั่วไป ดึงเฉพาะแผนกที่ตัวเองสังกัดอยู่เท่านั้น
    $dept_query = $conn->query("SELECT * FROM departments WHERE id = $dept_id_user");
    $topics_query = $conn->query("SELECT id, department_id, title FROM topics WHERE department_id = $dept_id_user ORDER BY id ASC");
}

// เตรียม Array ข้อมูลหัวข้อส่งต่อไปยัง Javascript เพื่อทำ Dropdown สัมพันธ์ (Dynamic Dropdown)
$topics_arr = [];
if ($topics_query && $topics_query->num_rows > 0) {
    while($t = $topics_query->fetch_assoc()) {
        $topics_arr[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນໃໝ່</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* จัดวางกล่องฟอร์มให้อยู่กึ่งกลางพื้นที่หน้าจออย่างสวยงาม ไม่ซ้อนทับเมนู */
        .form-wrap-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            width: 100%;
        }
        .form-container-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .form-container-custom h2 {
            margin-bottom: 20px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            text-align: center;
        }
        .form-container-custom label {
            display: block;
            margin-top: 14px;
            margin-bottom: 6px;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
        }
        .form-container-custom input, .form-container-custom select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .form-container-custom input:focus, .form-container-custom select:focus {
            border-color: #3182ce;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
        }
        .btn-submit-save {
            width: 100%;
            padding: 14px;
            background-color: #3182ce;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 25px;
            transition: background 0.2s;
        }
        .btn-submit-save:hover {
            background-color: #2b6cb0;
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">☰ ເມນູ</button>

    <div class="wrapper">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                📊 ລະບົບຖານຂໍ້ມູນພະແນກ
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="dashboard.php">🏠 ໜ້າຫຼັກ Dashboard</a>
                </li>
                <li class="sidebar-item">
                    <a href="compare_report.php">📊 ໜ້າສະຫຼຸບສົມທຽບ</a>
                </li>
                <li class="sidebar-item active">
                    <a href="insert_data.php">➕ ບັນທຶກຂໍ້ມູນໃໝ່</a>
                </li>
                <li class="sidebar-item">
                    <a href="manage_structure.php">🛠️ ຈັດການ & ເພີ່ມຫົວຂໍ້</a>
                </li>
                
                <?php if($_SESSION['role'] == 'admin'): ?>
                <li class="sidebar-item">
                    <a href="add_user.php" style="color: #9f7aea;">👤 ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</a>
                </li>
                <?php endif; ?>

                <li class="sidebar-item" style="margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                    <a href="logout.php" style="background-color: #f56565; color: #fff; text-align: center; justify-content: center;">🚪 ອອກຈາກລະບົບ</a>
                </li>
            </ul>

            <div class="sidebar-user">
                <p>👤 ຜູ້ໃຊ້: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <p>🔑 ສິດທິ: <span style="text-transform: uppercase; font-weight: bold; color:#3182ce;"><?php echo $_SESSION['role']; ?></span></p>
            </div>
        </nav>

        <main class="main-content">
            <div class="form-wrap-center">
                <div class="form-container-custom">
                    <h2>➕ ບັນທຶກຂໍ້ມູນ</h2>
                    
                    <?php echo $msg; ?>
                    
                    <form method="POST" action="insert_data.php">
                        <label>ເລືອກນະຄອນ/ເມືອງ (Select Department)</label>
                        <select name="dept_id" id="department" onchange="filterTopics()" required>
                            <option value="">-- ເມນູເລືອກ --</option>
                            <?php 
                            if ($dept_query && $dept_query->num_rows > 0) {
                                while($d = $dept_query->fetch_assoc()) {
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>

                        <label>ເລືອກຫົວຂໍ້ / ກິດຈະກຳ (Select Topic)</label>
                        <select name="topic_id" id="topic" required>
                            <option value="">-- ກະລຸນາເລືອກນະຄອນ/ເມືອງກ່ອນ --</option>
                        </select>

                        <label>ຈຳນວນ / ຜົນຜະລິດ (Amount)</label>
                        <input type="number" name="amount" step="any" placeholder="ປ້ອນຕົວເລກຈຳນວນ... 0.00" required>

                        <label>ຫົວໜ່ວຍວັດແທກ (Unit)</label>
                        <input type="text" name="unit" placeholder="ຕົວຢ່າງ: ໂຕນ, ກິໂລ, ຄົນ, ກີບ" required>

                        <label>ວันທີບັນທຶກຂໍ້ມູນ (Record Date)</label>
                        <input type="date" name="record_date" value="<?php echo date('Y-m-d'); ?>" required>

                        <button type="submit" name="save_data" class="btn-submit-save">💾 ບັນທຶກລົງຖານຂໍ້ມູນ</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ดึงรายการหัวข้อทั้งหมดมารอรับคัดกรองในฝั่ง Client
        const topics = <?php echo json_encode($topics_arr); ?>;
        
        function filterTopics() {
            const deptId = document.getElementById('department').value;
            const topicSelect = document.getElementById('topic');
            
            // ล้างข้อมูลตัวเลือกเก่าออกก่อน
            topicSelect.innerHTML = '<option value="">-- ເລືອກຫົວຂໍ້ກິດຈະກຳ --</option>';
            
            // กรองดึงเอาเฉพาะรายชื่อหัวข้อที่สังกัดอยู่ในแผนกที่ถูกเลือก
            const filtered = topics.filter(t => t.department_id == deptId);
            
            filtered.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.title;
                topicSelect.appendChild(opt);
            });
        }

        // สำหรับดึงและใช้งานเมนู Sidebar บนโทรศัพท์มือถือ
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if(sidebar.classList.contains('open')) {
                menuToggle.innerText = '✕ ປິດ';
            } else {
                menuToggle.innerText = '☰ ເມນູ';
            }
        });
    </script>
</body>
</html>