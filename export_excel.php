<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { exit(); }

$current_year = date('Y');
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';

// เงื่อนไขการจำกัดสิทธิ์ข้อมูลตามแผนก
$where_topic = "";
if ($_SESSION['role'] == 'user') {
    $where_topic = "WHERE t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic = "WHERE t.department_id = $search_dept";
}

// 🌟 ปรับปรุงคิวรีใหม่: ให้ดึงเฉพาะยอดรวมสรุปล่าสุดของแต่ละหัวข้อ ไม่ดึงประวัติแถวซ้ำเก่าๆ
$query_string = "SELECT t.title, d.name as dept_name, 
                        SUM(r.amount) as current_total,
                        MAX(r.created_at) as latest_update,
                        (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit
                 FROM topics t
                 JOIN departments d ON t.department_id = d.id
                 LEFT JOIN records r ON r.topic_id = t.id AND YEAR(r.record_date) = '$current_year'
                 $where_topic
                 GROUP BY t.id, t.title, d.name
                 ORDER BY d.id ASC, t.id ASC";
$records = $conn->query($query_string);

// ตั้งค่าหัว Header สำหรับดาวน์โหลดไฟล์ Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Department_Summary_Report_$current_year.xls");
header("Pragma: no-cache");
header("Expires: 0");

// ใส่ UTF-8 BOM เพื่อให้ Excel รองรับและอ่านภาษาลาว/ภาษาไทยได้ถูกต้อง ไม่เป็นภาษาต่างดาว
echo "\xef\xbb\xbf"; 

// สร้างตารางใน Excel สไตล์โทนสีขาว-เทาอ่อน ดูสะอาดตาและเป็นระเบียบ
echo "<table border='1' style='font-family: \"Noto Sans Lao\", sans-serif; border-collapse: collapse;'>";
echo "<tr>
        <th colspan='5' style='background-color:#ffffff; color:#0f172a; font-size:16px; font-weight:bold; height:40px; text-align:center;'>
            ລາຍງານສະຫຼຸບຍອດລວມຂໍ້ມູນຫຼ້າສຸດ ປະຈຳປີ $current_year
        </th>
      </tr>";
echo "<tr style='background-color: #f1f5f9;'>
        <th style='padding: 10px; color:#475569; font-weight:bold;'>ພະແนກ (Department)</th>
        <th style='padding: 10px; color:#475569; font-weight:bold;'>ຫົວຂໍ້ / ກິດຈະກຳ (Topic)</th>
        <th style='padding: 10px; color:#475569; font-weight:bold; text-align:right;'>ຍອດລວມທັງໝົດ (Total)</th>
        <th style='padding: 10px; color:#475569; font-weight:bold;'>ຫົວໜ່ວຍ (Unit)</th>
        <th style='padding: 10px; color:#475569; font-weight:bold;'>ອັບເດດຫຼ້າສຸດ (Latest Update)</th>
      </tr>";

$system_latest = "-";

if($records && $records->num_rows > 0) {
    while($row = $records->fetch_assoc()) {
        $total_val = $row['current_total'] ?? 0;
        $unit_val = htmlspecialchars($row['latest_unit'] ?? '-');
        $update_time = !empty($row['latest_update']) ? date('d-m-Y H:i:s', strtotime($row['latest_update'])) : 'ບໍ່ມີການອັບເດດ';
        
        // หาเวลาอัปเดตล่าสุดของระบบทั้งหมด
        if(!empty($row['latest_update']) && ($row['latest_update'] > $system_latest || $system_latest == "-")) {
            $system_latest = date('d-m-Y H:i:s', strtotime($row['latest_update']));
        }

        echo "<tr>";
        echo "<td style='padding: 8px; background-color:#ffffff; color:#334155;'>" . htmlspecialchars($row['dept_name']) . "</td>";
        echo "<td style='padding: 8px; background-color:#ffffff; color:#334155; font-weight:500;'>" . htmlspecialchars($row['title']) . "</td>";
        // แสดงผลตัวเลขแบบจุดทศนิยม 2 ตำแหน่ง (.2f) ตามเงื่อนไขหน้าเว็บ
        echo "<td style='padding: 8px; background-color:#ffffff; color:#2f855a; text-align:right; font-weight:bold;'>" . number_format($total_val, 2, '.', '') . "</td>";
        echo "<td style='padding: 8px; background-color:#ffffff; color:#334155; text-align:center;'>" . $unit_val . "</td>";
        echo "<td style='padding: 8px; background-color:#ffffff; color:#64748b; font-size:12px;'>" . $update_time . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#64748b;'>ບໍ່ມີຂໍ້ມູນຫົວຂໍ້ໃນລະບົບ</td></tr>";
}

// แถวสุดท้ายของตารางแสดงเวลาอัปเดตล่าสุดของทั้งระบบ สอดคล้องกับหน้าเว็บหลัก
echo "<tr style='background-color: #f8fafc; font-weight:bold;'>
        <td colspan='2' style='padding: 12px; color:#64748b; text-align:left;'>📊 ໝາຍເຫດ: ຂໍ້ມູນນີ້ຖືກດຶງສະເພາະຍອດລວມຫຼ້າສຸດ</td>
        <td colspan='3' style='padding: 12px; color:#2b6cb0; text-align:right;'>🕒 ລະບົບອັບເດດຫຼ້າສຸດທັງໝົດວັນທີ: $system_latest</td>
      </tr>";

echo "</table>";
?>