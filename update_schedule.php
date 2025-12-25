<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

require_once 'database.php';
$pdo = Database::getConnection();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_lesson'])) {
    $lesson_id = intval($_POST['lesson_id']);
    $class = $_POST['class'];
    $day_of_week = $_POST['day_of_week'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $subject = $_POST['subject'];
    $teacher = $_POST['teacher'];
    $room = $_POST['room'];
    $lesson_type = $_POST['lesson_type'];
    
    try {
        // Проверяем конфликты (исключая текущий урок)
        $check_sql = "SELECT * FROM schedule 
                      WHERE id != ? 
                      AND day_of_week = ? 
                      AND room = ? 
                      AND ((time_start <= ? AND time_end > ?) 
                      OR (time_start < ? AND time_end >= ?)
                      OR (time_start >= ? AND time_end <= ?))";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            $lesson_id, $day_of_week, $room, 
            $time_start, $time_start, $time_end, $time_end, $time_start, $time_end
        ]);
        $conflict = $check_stmt->fetch();
        
        if ($conflict) {
            $_SESSION['error'] = "Конфликт расписания! В это время в кабинете $room уже идет урок: {$conflict['subject']} ({$conflict['class']})";
        } else {
            // Проверяем конфликты у учителя
            $teacher_check = "SELECT * FROM schedule 
                             WHERE id != ? 
                             AND day_of_week = ? 
                             AND teacher = ? 
                             AND ((time_start <= ? AND time_end > ?) 
                             OR (time_start < ? AND time_end >= ?))";
            $teacher_stmt = $pdo->prepare($teacher_check);
            $teacher_stmt->execute([$lesson_id, $day_of_week, $teacher, $time_start, $time_start, $time_end, $time_end]);
            $teacher_conflict = $teacher_stmt->fetch();
            
            if ($teacher_conflict) {
                $_SESSION['error'] = "Учитель $teacher уже ведет урок в это время: {$teacher_conflict['subject']} ({$teacher_conflict['class']})";
            } else {
                // Обновляем урок
                $update_sql = "UPDATE schedule SET 
                    class = ?, 
                    day_of_week = ?, 
                    time_start = ?, 
                    time_end = ?, 
                    subject = ?, 
                    teacher = ?, 
                    room = ?, 
                    lesson_type = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    $class, $day_of_week, $time_start, $time_end, 
                    $subject, $teacher, $room, $lesson_type, $lesson_id
                ]);
                
                $_SESSION['success'] = "Урок успешно обновлен";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при обновлении урока: " . $e->getMessage();
    }
    
    header('Location: schedule.php');
    exit();
}
?>