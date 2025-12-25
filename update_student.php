<?php
session_start();
require_once 'auth.php';
require_once 'database.php';

if (!isTeacher() && !isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $id = intval($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $class = $_POST['class'];
    $birth_date = $_POST['birth_date'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET 
            full_name = ?, 
            class = ?, 
            birth_date = ?, 
            phone = ?, 
            email = ?, 
            parent_name = ?, 
            parent_phone = ?, 
            address = ?,
            updated_at = NOW()
            WHERE id = ?");
        
        $stmt->execute([
            $full_name, $class, $birth_date, $phone, $email, 
            $parent_name, $parent_phone, $address, $id
        ]);
        
        $_SESSION['success'] = "Данные ученика успешно обновлены";
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при обновлении данных: " . $e->getMessage();
    }
    
    header('Location: students.php');
    exit();
}
?>