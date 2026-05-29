<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

if ($topic_id > 0) {
    // ดำเนินการลบประวัติและจำนวนข้อมูลที่บันทึกของหัวข้อนี้ทั้งหมดออกทันที
    $stmt = $conn->prepare("DELETE FROM records WHERE topic_id = ?");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
}

// เมื่อลบข้อมูลเสร็จสิ้นแล้ว ให้ทำการย้อนหน้าจอกลับไปยังหน้า Dashboard ทันที
header("Location: dashboard.php");
exit();
?>