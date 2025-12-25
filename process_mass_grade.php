<?php
session_start();
require_once 'auth.php';
require_once 'database.php';

if (!isTeacher() && !isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mass_grade'])) {
    $date = $_POST['mass_date'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $teacher = $_SESSION['full_name'];
    $grades = $_POST['grades'] ?? [];
    $comments = $_POST['comments'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($grades as $student_id => $grade) {
            if (!empty($grade)) {
                $comment = $comments[$student_id] ?? '';
                
                // Проверяем, есть ли уже оценка на эту дату
                $check_stmt = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject = ? AND date = ?");
                $check_stmt->execute([$student_id, $subject, $date]);
                $existing = $check_stmt->fetch();
                
                if ($existing) {
                    // Обновляем существующую
                    $update_stmt = $pdo->prepare("UPDATE grades SET grade = ?, comment = ? WHERE id = ?");
                    $update_stmt->execute([$grade, $comment, $existing['id']]);
                } else {
                    // Добавляем новую
                    $insert_stmt = $pdo->prepare("INSERT INTO grades (student_id, subject, grade, date, comment, teacher) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert_stmt->execute([$student_id, $subject, $grade, $date, $comment, $teacher]);
                }
                
                $success_count++;
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Успешно сохранено $success_count оценок";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Ошибка: " . $e->getMessage();
    }
    
    header("Location: journal.php?class=$class&subject=$subject&date=$date");
    exit();
}
?>