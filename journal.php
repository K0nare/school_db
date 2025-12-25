<?php
session_start();

// Включаем отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Простая проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Подключаем базу данных
require_once 'database.php';
$pdo = Database::getConnection();

// Получаем текущего пользователя
$current_user = $_SESSION['full_name'];

// Получаем список предметов
$subjects = [
    'Математика' => ['teacher' => 'Иванова М.П.', 'color' => 'primary'],
    'Русский язык' => ['teacher' => 'Петрова С.И.', 'color' => 'success'],
    'Физика' => ['teacher' => 'Сидоров А.В.', 'color' => 'info'],
    'История' => ['teacher' => 'Кузнецова О.В.', 'color' => 'warning'],
    'Химия' => ['teacher' => 'Михайлов Д.С.', 'color' => 'danger'],
    'Биология' => ['teacher' => 'Фёдорова Е.М.', 'color' => 'dark'],
    'Английский язык' => ['teacher' => 'Смирнова Е.А.', 'color' => 'purple'],
    'Литература' => ['teacher' => 'Петрова С.И.', 'color' => 'teal']
];

// Получаем список классов
$classes = ['10-А', '10-Б', '11-А', '11-Б'];

// Обработка параметров - исправленная версия без рекурсии
$selected_class = isset($_GET['class']) ? $_GET['class'] : '10-А';
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : 'Математика';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Безопасная проверка значений
if (!in_array($selected_class, $classes)) {
    $selected_class = '10-А';
}
if (!array_key_exists($selected_subject, $subjects)) {
    $selected_subject = 'Математика';
}

// Если ученик, показываем только его оценки
$is_student = ($_SESSION['role'] ?? '') === 'student';
$is_teacher = ($_SESSION['role'] ?? '') === 'teacher';
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Получаем учеников выбранного класса
$students = [];
try {
    if ($is_teacher || $is_admin) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? ORDER BY full_name");
        $stmt->execute([$selected_class]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($is_student) {
        // Для ученика показываем только его
        $student_id = $_SESSION['user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($students)) {
            $students = [['id' => $student_id, 'full_name' => $_SESSION['full_name'], 'class' => '10-А']];
        }
    }
} catch (Exception $e) {
    // Если таблицы нет, создаем тестовых учеников
    $students = [
        ['id' => 1, 'full_name' => 'Иванов Александр', 'class' => '10-А'],
        ['id' => 2, 'full_name' => 'Петрова Мария', 'class' => '10-А'],
        ['id' => 3, 'full_name' => 'Сидоров Дмитрий', 'class' => '10-А'],
        ['id' => 4, 'full_name' => 'Козлова Анна', 'class' => '10-А'],
        ['id' => 5, 'full_name' => 'Николаев Владимир', 'class' => '10-А']
    ];
}

// Функция для получения оценок
function getGrades($pdo, $student_id, $subject, $month = null) {
    try {
        $sql = "SELECT * FROM grades WHERE student_id = ? AND subject = ?";
        $params = [$student_id, $subject];
        
        if ($month) {
            $sql .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
            $params[] = $month;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Получаем оценки для всех учеников
$all_grades = [];
$students_grades_stats = [];

foreach ($students as $student) {
    $grades = getGrades($pdo, $student['id'], $selected_subject, $selected_month);
    $all_grades[$student['id']] = $grades;
    
    // Подсчитываем статистику
    $total_grades = 0;
    $sum_grades = 0;
    $grade_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0];
    
    foreach ($grades as $grade) {
        $total_grades++;
        $sum_grades += $grade['grade'];
        $grade_counts[$grade['grade']]++;
    }
    
    $average = $total_grades > 0 ? round($sum_grades / $total_grades, 2) : 0;
    
    // Сохраняем статистику
    $students_grades_stats[$student['id']] = [
        'average' => $average,
        'total' => $total_grades,
        'counts' => $grade_counts
    ];
}

// Обработка добавления оценки (ТОЛЬКО для учителей и админов)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_grade']) && ($is_teacher || $is_admin)) {
    $student_id = $_POST['student_id'] ?? 0;
    $grade = $_POST['grade'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $comment = $_POST['comment'] ?? '';
    
    // Валидация данных
    if ($student_id > 0 && $grade >= 2 && $grade <= 5) {
        try {
            // Проверяем, есть ли уже оценка на эту дату
            $check_stmt = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject = ? AND date = ?");
            $check_stmt->execute([$student_id, $selected_subject, $date]);
            $existing = $check_stmt->fetch();
            
            if ($existing) {
                // Обновляем существующую оценку
                $update_stmt = $pdo->prepare("UPDATE grades SET grade = ?, comment = ?, teacher = ? WHERE id = ?");
                $update_stmt->execute([$grade, $comment, $current_user, $existing['id']]);
                $message = "Оценка обновлена";
            } else {
                // Добавляем новую оценку
                $insert_stmt = $pdo->prepare("INSERT INTO grades (student_id, subject, grade, date, comment, teacher) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$student_id, $selected_subject, $grade, $date, $comment, $current_user]);
                $message = "Оценка добавлена";
            }
            
            $_SESSION['success'] = $message;
            // БЕЗ редиректа - показываем сообщение на той же странице
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Некорректные данные";
    }
}

// Обработка удаления оценки (ТОЛЬКО для учителей и админов)
if (isset($_GET['delete_grade']) && ($is_teacher || $is_admin)) {
    $grade_id = intval($_GET['delete_grade']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->execute([$grade_id]);
        $_SESSION['success'] = "Оценка удалена";
        // БЕЗ редиректа - показываем сообщение на той же странице
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при удалении: " . $e->getMessage();
    }
}

// Рассчитываем общую статистику класса
$class_average = 0;
$class_total_grades = 0;
$class_grade_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0];

foreach ($students_grades_stats as $stats) {
    $class_average += $stats['average'];
    $class_total_grades += $stats['total'];
    foreach ($stats['counts'] as $grade => $count) {
        $class_grade_counts[$grade] += $count;
    }
}

$class_students_count = count($students);
$class_average = $class_students_count > 0 ? round($class_average / $class_students_count, 2) : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Электронный журнал</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .grade-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 2px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .grade-badge:hover {
            transform: scale(1.1);
        }
        
        .grade-5 { background-color: #d1e7dd; color: #0f5132; border: 2px solid #badbcc; }
        .grade-4 { background-color: #cfe2ff; color: #084298; border: 2px solid #b6d4fe; }
        .grade-3 { background-color: #fff3cd; color: #664d03; border: 2px solid #ffecb5; }
        .grade-2 { background-color: #f8d7da; color: #842029; border: 2px solid #f5c2c7; }
        
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .badge-primary { background: linear-gradient(135deg, #4a6fa5, #2c3e50); }
        .badge-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .badge-info { background: linear-gradient(135deg, #17a2b8, #138496); }
        .badge-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .badge-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .badge-dark { background: linear-gradient(135deg, #343a40, #23272b); }
        .badge-purple { background: linear-gradient(135deg, #6f42c1, #4e2d8c); }
        .badge-teal { background: linear-gradient(135deg, #20c997, #17a589); }
        
        .filter-card {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(74, 111, 165, 0.05);
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 1px;
        }
        
        .month-selector {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .day-with-grade {
            background-color: #e8f4ff;
            color: #0056b3;
            font-weight: bold;
            border-radius: 5px;
            padding: 5px;
        }
        
        .analytics-card {
            border-left: 4px solid var(--primary-color);
        }
        
        /* Отладочная информация */
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .debug-info pre {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            max-height: 200px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <!-- Шапка -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
            <span class="navbar-text text-white">
                <i class="bi bi-journal-text"></i> Электронный журнал
                <small class="ms-2">(<?php echo $_SESSION['role'] ?? 'гость'; ?>)</small>
            </span>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Панель фильтров -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filter-card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Класс</label>
                                <select name="class" class="form-select">
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class; ?>" 
                                            <?php echo $selected_class == $class ? 'selected' : ''; ?>>
                                            <?php echo $class; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Предмет</label>
                                <select name="subject" class="form-select">
                                    <?php foreach ($subjects as $subject => $info): ?>
                                        <option value="<?php echo $subject; ?>" 
                                            <?php echo $selected_subject == $subject ? 'selected' : ''; ?>
                                            data-color="<?php echo $info['color']; ?>">
                                            <?php echo $subject; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Дата</label>
                                <input type="date" name="date" class="form-control" 
                                       value="<?php echo $selected_date; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Месяц</label>
                                <input type="month" name="month" class="form-control" 
                                       value="<?php echo $selected_month; ?>">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Применить фильтры</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Статистика класса -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Средний балл</h6>
                                <h2 class="card-title"><?php echo $class_average; ?></h2>
                                <p class="card-text small">по предмету</p>
                            </div>
                            <i class="bi bi-graph-up" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Всего оценок</h6>
                                <h2 class="card-title"><?php echo $class_total_grades; ?></h2>
                                <p class="card-text small">за текущий месяц</p>
                            </div>
                            <i class="bi bi-journal-check" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="card-title">Распределение оценок</h6>
                        <div class="row">
                            <div class="col-3 text-center">
                                <span class="grade-badge grade-5">5</span>
                                <div class="mt-1 small"><?php echo $class_grade_counts[5]; ?></div>
                            </div>
                            <div class="col-3 text-center">
                                <span class="grade-badge grade-4">4</span>
                                <div class="mt-1 small"><?php echo $class_grade_counts[4]; ?></div>
                            </div>
                            <div class="col-3 text-center">
                                <span class="grade-badge grade-3">3</span>
                                <div class="mt-1 small"><?php echo $class_grade_counts[3]; ?></div>
                            </div>
                            <div class="col-3 text-center">
                                <span class="grade-badge grade-2">2</span>
                                <div class="mt-1 small"><?php echo $class_grade_counts[2]; ?></div>
                            </div>
                        </div>
                        <div class="progress mt-2">
                            <?php
                            $total = array_sum($class_grade_counts);
                            $width_5 = $total > 0 ? ($class_grade_counts[5] / $total * 100) : 0;
                            $width_4 = $total > 0 ? ($class_grade_counts[4] / $total * 100) : 0;
                            $width_3 = $total > 0 ? ($class_grade_counts[3] / $total * 100) : 0;
                            $width_2 = $total > 0 ? ($class_grade_counts[2] / $total * 100) : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $width_5; ?>%" 
                                 data-bs-toggle="tooltip" title="Пятерки: <?php echo $class_grade_counts[5]; ?>"></div>
                            <div class="progress-bar bg-primary" style="width: <?php echo $width_4; ?>%"
                                 data-bs-toggle="tooltip" title="Четверки: <?php echo $class_grade_counts[4]; ?>"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo $width_3; ?>%"
                                 data-bs-toggle="tooltip" title="Тройки: <?php echo $class_grade_counts[3]; ?>"></div>
                            <div class="progress-bar bg-danger" style="width: <?php echo $width_2; ?>%"
                                 data-bs-toggle="tooltip" title="Двойки: <?php echo $class_grade_counts[2]; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Основная таблица журнала -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-journal-text"></i> 
                            <?php echo $selected_subject; ?> 
                            <span class="badge bg-<?php echo $subjects[$selected_subject]['color'] ?? 'primary'; ?>">
                                <?php echo $selected_class; ?>
                            </span>
                            <small class="text-muted ms-2">
                                Преподаватель: <?php echo $subjects[$selected_subject]['teacher'] ?? 'Не указан'; ?>
                            </small>
                        </h5>
                        <div>
                            <?php if ($is_teacher || $is_admin): ?>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#massGradeModal">
                                <i class="bi bi-plus-circle"></i> Массовое оценивание
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-primary btn-sm" onclick="exportToCSV()">
                                <i class="bi bi-file-earmark-excel"></i> Экспорт
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px">№</th>
                                        <th>ФИО ученика</th>
                                        <th style="width: 150px">Оценки за <?php echo date('F Y', strtotime($selected_month)); ?></th>
                                        <th style="width: 100px">Средний балл</th>
                                        <th style="width: 100px">Последняя оценка</th>
                                        <?php if ($is_teacher || $is_admin): ?>
                                        <th style="width: 100px">Действия</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="<?php echo ($is_teacher || $is_admin) ? '6' : '5'; ?>" class="text-center py-5">
                                                <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                                                <h5 class="mt-3">В этом классе нет учеников</h5>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $index => $student): 
                                            $grades = $all_grades[$student['id']] ?? [];
                                            $stats = $students_grades_stats[$student['id']] ?? ['average' => 0, 'total' => 0];
                                            $latest_grade = !empty($grades) ? $grades[0] : null;
                                        ?>
                                        <tr>
                                            <td class="text-muted"><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap">
                                                    <?php foreach ($grades as $grade): 
                                                        $grade_date = date('d', strtotime($grade['date']));
                                                    ?>
                                                        <span class="grade-badge grade-<?php echo $grade['grade']; ?> me-1 mb-1"
                                                           data-bs-toggle="tooltip" 
                                                           title="<?php echo date('d.m.Y', strtotime($grade['date'])); ?><br>Комментарий: <?php echo htmlspecialchars($grade['comment'] ?? 'нет'); ?>">
                                                            <?php echo $grade['grade']; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (empty($grades)): ?>
                                                        <span class="text-muted small">Нет оценок</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary fs-6">
                                                    <?php echo $stats['average']; ?>
                                                </span>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo min($stats['average'] * 20, 100); ?>%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($latest_grade): ?>
                                                    <span class="grade-badge grade-<?php echo $latest_grade['grade']; ?>">
                                                        <?php echo $latest_grade['grade']; ?>
                                                    </span>
                                                    <div class="small text-muted">
                                                        <?php echo date('d.m', strtotime($latest_grade['date'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($is_teacher || $is_admin): ?>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary action-btn"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#addGradeModal"
                                                            onclick="setStudentForGrade(<?php echo $student['id']; ?>, '<?php echo addslashes($student['full_name']); ?>')">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info action-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#studentAnalyticsModal"
                                                            onclick="showStudentAnalytics(<?php echo $student['id']; ?>, '<?php echo addslashes($student['full_name']); ?>')">
                                                        <i class="bi bi-graph-up"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Аналитика по предмету -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card analytics-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-star"></i> Лучшие ученики</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php
                            // Сортируем учеников по среднему баллу
                            $sorted_students = $students;
                            usort($sorted_students, function($a, $b) use ($students_grades_stats) {
                                $avg_a = $students_grades_stats[$a['id']]['average'] ?? 0;
                                $avg_b = $students_grades_stats[$b['id']]['average'] ?? 0;
                                return $avg_b <=> $avg_a;
                            });
                            
                            $top_students = array_slice($sorted_students, 0, 3);
                            ?>
                            
                            <?php foreach ($top_students as $i => $student): 
                                $avg = $students_grades_stats[$student['id']]['average'] ?? 0;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2"><?php echo $i + 1; ?></span>
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                    </div>
                                    <span class="badge bg-success"><?php echo $avg; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-week"></i> Календарь оценок</h6>
                    </div>
                    <div class="card-body">
                        <div class="month-selector">
                            <?php
                            try {
                                $month_start = new DateTime($selected_month . '-01');
                                $month_end = clone $month_start;
                                $month_end->modify('last day of this month');
                                
                                // Получаем все дни месяца
                                $period = new DatePeriod(
                                    $month_start,
                                    new DateInterval('P1D'),
                                    $month_end
                                );
                                
                                // Собираем все даты с оценками
                                $grade_dates = [];
                                foreach ($students as $student) {
                                    foreach ($all_grades[$student['id']] ?? [] as $grade) {
                                        $grade_date = date('Y-m-d', strtotime($grade['date']));
                                        if (!isset($grade_dates[$grade_date])) {
                                            $grade_dates[$grade_date] = 0;
                                        }
                                        $grade_dates[$grade_date]++;
                                    }
                                }
                                ?>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <h6><?php echo $month_start->format('F Y'); ?></h6>
                                    <div>
                                        <small class="text-muted">
                                            Всего оценок: <?php echo array_sum($grade_dates); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="row g-1">
                                    <?php foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $day): ?>
                                        <div class="col text-center text-muted small">
                                            <?php echo $day; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php
                                    $first_day = (int)$month_start->format('N') - 1;
                                    for ($i = 0; $i < $first_day; $i++): ?>
                                        <div class="col"></div>
                                    <?php endfor; ?>
                                    
                                    <?php foreach ($period as $date): 
                                        $date_str = $date->format('Y-m-d');
                                        $day_num = $date->format('j');
                                        $has_grades = isset($grade_dates[$date_str]);
                                    ?>
                                        <div class="col text-center p-1">
                                            <div class="<?php echo $has_grades ? 'day-with-grade' : ''; ?>">
                                                <?php echo $day_num; ?>
                                                <?php if ($has_grades): ?>
                                                    <div class="small text-primary">
                                                        <?php echo $grade_dates[$date_str]; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php } catch (Exception $e) { ?>
                                <div class="alert alert-warning">
                                    Некорректный месяц для отображения календаря
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Отладочная информация (можно убрать после тестирования) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="debug-info">
                    <h6>Отладочная информация:</h6>
                    <pre>GET параметры: <?php print_r($_GET); ?></pre>
                    <pre>SESSION данные: <?php print_r($_SESSION); ?></pre>
                    <pre>Количество учеников: <?php echo count($students); ?></pre>
                    <pre>Выбранный класс: <?php echo $selected_class; ?></pre>
                    <pre>Выбранный предмет: <?php echo $selected_subject; ?></pre>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Модальное окно добавления оценки (ТОЛЬКО для учителей и админов) -->
    <?php if ($is_teacher || $is_admin): ?>
    <div class="modal fade" id="addGradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Добавить оценку</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="student_id">
                        <input type="hidden" name="class" value="<?php echo $selected_class; ?>">
                        <input type="hidden" name="subject" value="<?php echo $selected_subject; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Ученик</label>
                            <input type="text" class="form-control" id="student_name" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Оценка *</label>
                                    <select name="grade" class="form-select" required>
                                        <option value="">Выберите оценку</option>
                                        <option value="5">5 (Отлично)</option>
                                        <option value="4">4 (Хорошо)</option>
                                        <option value="3">3 (Удовлетворительно)</option>
                                        <option value="2">2 (Неудовлетворительно)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Дата *</label>
                                    <input type="date" name="date" class="form-control" 
                                           value="<?php echo $selected_date; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="3" 
                                      placeholder="Например: 'Ответил у доски', 'Контрольная работа'"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_grade" class="btn btn-primary">Сохранить оценку</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Модальное окно массового оценивания (ТОЛЬКО для учителей и админов) -->
    <?php if ($is_teacher || $is_admin): ?>
    <div class="modal fade" id="massGradeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="massGradeForm">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Массовое оценивание</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Вы можете выставить оценку всем ученикам сразу
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Дата *</label>
                                <input type="date" name="mass_date" class="form-control" 
                                       value="<?php echo $selected_date; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Оценка по умолчанию</label>
                                <select name="default_grade" class="form-select">
                                    <option value="">Без оценки</option>
                                    <option value="5">5</option>
                                    <option value="4">4</option>
                                    <option value="3">3</option>
                                    <option value="2">2</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ученик</th>
                                        <th style="width: 100px">Оценка</th>
                                        <th>Комментарий</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>
                                            <select name="grades[<?php echo $student['id']; ?>]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                <option value="5">5</option>
                                                <option value="4">4</option>
                                                <option value="3">3</option>
                                                <option value="2">2</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="comments[<?php echo $student['id']; ?>]" 
                                                   class="form-control form-control-sm" placeholder="Комментарий">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-success" onclick="saveMassGrades()">Сохранить все оценки</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Модальное окно аналитики ученика -->
    <div class="modal fade" id="studentAnalyticsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="analyticsStudentName"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="analyticsContent">
                        <!-- Контент будет загружен динамически -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Установка ученика для добавления оценки
        function setStudentForGrade(studentId, studentName) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('student_name').value = studentName;
        }
        
        // Показать аналитику ученика
        function showStudentAnalytics(studentId, studentName) {
            document.getElementById('analyticsStudentName').textContent = 'Аналитика: ' + studentName;
            
            // Для примера покажем простой контент
            document.getElementById('analyticsContent').innerHTML = `
                <div class="text-center">
                    <h4>${studentName}</h4>
                    <p class="text-muted">Аналитика успеваемости</p>
                </div>
                
                <div class="mt-3">
                    <h6>Последние оценки</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Предмет</th>
                                <th>Оценка</th>
                                <th>Комментарий</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${new Date().toLocaleDateString('ru-RU')}</td>
                                <td>Математика</td>
                                <td><span class="grade-badge grade-5">5</span></td>
                                <td>Отлично справился с контрольной</td>
                            </tr>
                            <tr>
                                <td>${new Date().toLocaleDateString('ru-RU')}</td>
                                <td>Русский язык</td>
                                <td><span class="grade-badge grade-4">4</span></td>
                                <td>Хорошо написал диктант</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // Экспорт в CSV
        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let row of rows) {
                const cells = row.querySelectorAll('td, th');
                const rowData = [];
                
                for (let cell of cells) {
                    let text = cell.textContent.trim();
                    text = text.replace(/\s+/g, ' ');
                    rowData.push(`"${text}"`);
                }
                
                csv.push(rowData.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            const date = new Date().toISOString().split('T')[0];
            link.href = URL.createObjectURL(blob);
            link.download = `Журнал_${'<?php echo $selected_class; ?>'}_${'<?php echo $selected_subject; ?>'}_${date}.csv`;
            link.click();
        }
        
        // Сохранение массовых оценок
        function saveMassGrades() {
            alert('Функция массового оценивания находится в разработке');
        }
        
        // Инициализация tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>