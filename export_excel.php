<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { exit(); }

// 🌟 ຮັບຄ່າຕົວແປປີມາຈາກ Dashboard ເພື່ອໃຫ້ລາຍງານ Excel ອອກມາກົງກັນ
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';

$where_topic = "";
if ($_SESSION['role'] == 'user') {
    $where_topic = "WHERE t.department_id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_topic = "WHERE t.department_id = $search_dept";
}

// 🌟 ຄິວຣີກັ່ນກອງຂໍ້ມູນໃຫ້ແຍກປີ ແລະ ດຶງຍູນິດສະເພາະຂອງແຕ່ລະສ່ວນ
$query_string = "SELECT t.title, d.name as dept_name, 
                        SUM(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.amount ELSE 0 END) as current_total,
                        MAX(CASE WHEN YEAR(r.record_date) = '$selected_year' THEN r.created_at ELSE NULL END) as latest_update,
                        (SELECT r2.unit FROM records r2 WHERE r2.topic_id = t.id ORDER BY r2.id DESC LIMIT 1) as latest_unit
                 FROM topics t
                 JOIN departments d ON t.department_id = d.id
                 LEFT JOIN records r ON r.topic_id = t.id
                 $where_topic
                 GROUP BY t.id, t.title, d.name
                 ORDER BY d.id ASC, t.id ASC";
$records = $conn->query($query_string);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Summary_Report_$selected_year.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xef\xbb\xbf"; // UTF-8 BOM

echo "<table border='1' style='font-family: \"Noto Sans Lao\", sans-serif; border-collapse: collapse;'>";
echo "<tr><th colspan='5' style='height:40px; text-align:center; font-size:16px; font-weight:bold;'>ລາຍງານສະຫຼຸບຍອດລວມຂໍ້ມູນຫຼ້າສຸດ ປະຈຳປີ $selected_year</th></tr>";
echo "<tr style='background-color: #f1f5f9;'>
        <th>ພະແນກ</th>
        <th>ຫົວຂໍ້ / ກິດຈະກຳ</th>
        <th style='text-align:right;'>ຍອດລວມທັງໝົດ</th>
        <th>ຫົວໜ່ວຍວັດແທກ (Unit)</th>
        <th>ອັບເດດຫຼ້າສຸດ</th>
      </tr>";

$system_latest = "-";

if($records && $records->num_rows > 0) {
    while($row = $records->fetch_assoc()) {
        $total_val = $row['current_total'] ?? 0;
        $unit_val = htmlspecialchars($row['latest_unit'] ?? 'ບໍ່ທັນລະບຸ');
        $update_time = !empty($row['latest_update']) ? date('d-m-Y H:i:s', strtotime($row['latest_update'])) : 'ບໍ່ມີການອັບເດດ';
        
        if(!empty($row['latest_update']) && ($row['latest_update'] > $system_latest || $system_latest == "-")) {
            $system_latest = date('d-m-Y H:i:s', strtotime($row['latest_update']));
        }

        echo "<tr>";
        echo "<td style='padding:8px;'>" . htmlspecialchars($row['dept_name']) . "</td>";
        echo "<td style='padding:8px; font-weight:500;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding:8px; text-align:right; font-weight:bold; color:#2f855a;'>" . number_format($total_val, 2, '.', '') . "</td>";
        // 🌟 ບອກຍູນິດໃນໄຟລ໌ Excel ໃຫ້ຕົງຕາມຈິງ
        echo "<td style='padding:8px; text-align:center; font-weight:bold; color:#4a5568;'>" . $unit_val . "</td>";
        echo "<td style='padding:8px; color:#64748b;'>" . $update_time . "</td>";
        echo "</tr>";
    }
}
echo "<tr style='background-color:#f8fafc; font-weight:bold;'>
        <td colspan='2' style='padding:12px;'>📊 ສະຫຼຸບຂໍ້ມູນສະເພາະປະຈຳປີ $selected_year</td>
        <td colspan='3' style='padding:12px; text-align:right; color:#2b6cb0;'>🕒 ລະບົບອັບເດดຫຼ້າສຸດວັນທີ: $system_latest</td>
      </tr>";
echo "</table>";
?>