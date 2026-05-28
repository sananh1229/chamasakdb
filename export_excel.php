<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { exit(); }

$search_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : '';
$where_clause = "";
if ($_SESSION['role'] == 'user') {
    $where_clause = "WHERE d.id = " . $_SESSION['dept_id'];
} elseif ($search_dept) {
    $where_clause = "WHERE d.id = $search_dept";
}

$query_string = "SELECT r.*, t.title, d.name as dept_name 
                 FROM records r 
                 JOIN topics t ON r.topic_id = t.id 
                 JOIN departments d ON t.department_id = d.id 
                 $where_clause ORDER BY r.record_date DESC";
$records = $conn->query($query_string);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Department_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// ໃສ່ UTF-8 BOM ເພື່ອໃຫ້ Excel ອ່ານພາສາລາວອອກ
echo "\xef\xbb\xbf"; 

echo "<table border='1'>";
echo "<tr>
        <th style='background-color:#252525; color:#fff;'>ພະແນກ</th>
        <th style='background-color:#252525; color:#fff;'>ຫົວຂໍ້/ກິດຈະກຳ</th>
        <th style='background-color:#252525; color:#fff;'>ຈຳນວນ</th>
        <th style='background-color:#252525; color:#fff;'>ວັນທີ</th>
      </tr>";

while($row = $records->fetch_assoc()) {
    echo "<tr>
            <td>{$row['dept_name']}</td>
            <td>{$row['title']}</td>
            <td>{$row['amount']}</td>
            <td>{$row['record_date']}</td>
          </tr>";
}
echo "</table>";
?>