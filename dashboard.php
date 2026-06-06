<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_role = $_SESSION['role'];
$username = $_SESSION['username'];

// ดึงสถิติภาพรวมสั้นๆ มาโชว์ในหน้าแดชบอร์ดเพื่อให้เห็นข้อมูลแบบเรียบหรู ไม่เป็นตาราง Excel
$count_districts = $conn->query("SELECT COUNT(*) as total FROM districts")->fetch_assoc()['total'];
$count_topics = $conn->query("SELECT COUNT(*) as total FROM topics")->fetch_assoc()['total'];
$count_records = $conn->query("SELECT COUNT(*) as total FROM records")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແຜງຄວບຄຸມລະບົບ (Dashboard)</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Noto Sans Lao', sans-serif; }
        body { background: #f1f5f9; margin: 0; color: #1e293b; }
        .wrapper { display: flex; min-height: 100vh; flex-direction: column; }
        
        /* Header Top Bar */
        .top-bar { background: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .brand-title { font-size: 20px; font-weight: 700; color: #2563eb; }
        .user-profile { font-size: 14px; font-weight: 500; color: #475569; }
        .btn-logout { background: #ef4444; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: bold; margin-left: 10px; }

        /* Main Container */
        .main-container { flex: 1; padding: 40px 20px; max-width: 1200px; width: 100%; margin: 0 auto; }
        
        .welcome-section { text-align: center; margin-bottom: 40px; }
        .welcome-section h1 { font-size: 28px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0; }
        .welcome-section p { font-size: 15px; color: #64748b; margin: 0; }

        /* Quick Mini Stats Badges (ไม่ใช่ตาราง) */
        .stats-strip { display: grid; grid-template-columns: repeat(1, 1fr); gap: 15px; margin-bottom: 40px; }
        @media (min-width: 768px) { .stats-strip { grid-template-columns: repeat(3, 1fr); } }
        .stat-mini-box { background: #ffffff; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .stat-mini-box span { font-size: 14px; font-weight: 500; color: #64748b; }
        .stat-mini-box strong { font-size: 20px; font-weight: 700; color: #0f172a; }

        /* 🌟 Menu Grid Layout (ศูนย์รวมเมนูทั้งหมดในหน้าแดชบอร์ด) */
        .menu-dashboard-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 20px; }
        @media (min-width: 768px) { .menu-dashboard-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .menu-dashboard-grid { grid-template-columns: repeat(3, 1fr); } }

        /* Card Menu Styling */
        .menu-card { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 30px 25px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        
        .menu-title { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
        .menu-desc { font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 25px; min-height: 42px; }
        
        /* ปุ่มกดของแต่ละเมนู แยกสีสันชัดเจนตามหมวดหมู่ */
        .btn-menu-action { display: block; width: 100%; padding: 12px; text-decoration: none; color: #ffffff; font-weight: 700; font-size: 14px; border-radius: 8px; transition: opacity 0.2s; }
        .btn-menu-action:hover { opacity: 0.9; }

        /* สีสันประจำหมวดหมู่เมนู */
        .color-blue { background: #2563eb; }
        .color-teal { background: #0d9488; }
        .color-orange { background: #ea580c; }
        .color-purple { background: #7c3aed; }
        .color-green { background: #16a34a; }
    </style>
</head>
<body>
    <div class="wrapper">
        <header class="top-bar">
            <div class="brand-title">ລະບົບຖານຂໍ້ມູນສະຖິຕິ</div>
            <div class="user-profile">
                ສະບາຍດີ, <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo strtoupper($user_role); ?>)
                <a href="logout.php" class="btn-logout">ອອກຈາກລະບົບ</a>
            </div>
        </header>

        <main class="main-container">
            <div class="welcome-section">
                <h1>ແຜງຄວບຄຸມ ແລະ ເມນູລະບົບທັງໝົດ</h1>
                <p>ຍິນດີຕ້ອນຮັບເຂົ້າສູ່ລະບົບຈັດການຂໍ້ມູນສະຖິຕິ ກະລຸນາເລືອກເມນູການເຮັດວຽກທີ່ທ່ານຕ້ອງການດ້ານລຸ່ມນີ້</p>
            </div>

            <div class="stats-strip">
                <div class="stat-mini-box"><span>ພະແนກ/ເມືອງທັງໝົດ:</span> <strong><?php echo $count_districts; ?></strong></div>
                <div class="stat-mini-box"><span>ຫົວຂໍ້ຕົວຊີ້ວັດທັງໝົດ:</span> <strong><?php echo $count_topics; ?></strong></div>
                <div class="stat-mini-box"><span>ຈຳນວນຂໍ້ມູນທີ່ບັນທຶກ:</span> <strong><?php echo $count_records; ?></strong></div>
            </div>

            <div class="menu-dashboard-grid">
                
                <div class="menu-card">
                    <div>
                        <div class="menu-title">ສະຫຼຸບຍອດລວມສະສົມ</div>
                        <div class="menu-desc">ເບິ່ງລາຍງານສະຫຼຸບຜົນຜະລິດ ແລະ ຍອດລວມສະສົມຕົວເລກສະຖິຕິແຍກຕາມລາຍປີຢ່າງຊັດເຈນ.</div>
                    </div>
                    <a href="province_summary.php" class="btn-menu-action color-blue">ເປີດໜ້າສະຫຼຸບຍອດລວມ</a>
                </div>

                <div class="menu-card">
                    <div>
                        <div class="menu-title">ລາຍງານສົມທຽບຂໍ້ມູນສະຖິຕິ</div>
                        <div class="menu-desc">ສົມທຽບຜົນຜະລິດລະຫວ່າງ 1 ເດືອນ, 3 ເດືອນ, 6 ເດືອນ ແລະ 12 ເດືອນ ຂອງປີນີ້ກັບປີຜ່ານມາແບບມີສີສັນ.</div>
                    </div>
                    <a href="compare_report.php" class="btn-menu-action color-teal">ເປີດໜ້າສົມທຽບຂໍ້ມູນ</a>
                </div>

                <div class="menu-card">
                    <div>
                        <div class="menu-title">ບັນທຶກຂໍ້ມູນປະຈຳວັນ</div>
                        <div class="menu-desc">ປ້ອນຕົວເລກຈຳນວນສະຖິຕິຜົນຜະລິດໃໝ່ເຂົ້າສູ່ລະບົບ ໂດຍຄັດກອງຕາມກຸ່ມວຽກງານ ແລະ ພະແນກສັງກັດ.</div>
                    </div>
                    <a href="insert_data.php" class="btn-menu-action color-orange">ເປີດໜ້າບັນທຶກຂໍ້ມູນໃໝ່</a>
                </div>

                <?php if ($user_role === 'admin'): ?>
                    <div class="menu-card">
                        <div>
                            <div class="menu-title">ຈັດການໂຄງສ້າງລະບົບ</div>
                            <div class="menu-desc">ເພີ່ມລາຍຊື່ພະແນກ, ສັງກັດ, ເມືອງ ແລະ ເພີ່ມຫົວຂໍ້ກິດຈະກຳຕົວຊີ້ວັດສະຖິຕິໃໝ່ເຂົ້າຖານຂໍ້ມູນ.</div>
                        </div>
                        <a href="manage_structure.php" class="btn-menu-action color-purple">ເປີດໜ້າຈັດການໂຄງສ້າງ</a>
                    </div>

                    <div class="menu-card">
                        <div>
                            <div class="menu-title">ເພີ່ມຜູ້ໃຊ້ງານໃໝ່</div>
                            <div class="menu-desc">ສ້າງບັນຊີຜູ້ໃຊ້ງານ ແລະ ກຳນົດສິດທິການເຂົ້າເຖິງຂໍ້ມູນ (Admin / User) ຂອງແຕ່ລະເມືອງ.</div>
                        </div>
                        <a href="add_user.php" class="btn-menu-action color-green">ເປີດໜ້າຈັດການຜູ້ໃຊ້ງານ</a>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>