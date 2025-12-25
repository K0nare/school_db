<?php
session_start();

// Включаем отладку (можно убрать после тестирования)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Подключаем базу данных
require_once 'database.php';
$pdo = Database::getConnection();


// Получаем текущего пользователя
$current_user = $_SESSION['full_name'];
$current_role = $_SESSION['role'];

// Параметры фильтров
$selected_class = isset($_GET['class']) ? $_GET['class'] : '10-А';
$selected_day = isset($_GET['day']) ? intval($_GET['day']) : date('N'); // 1-ПН, 7-ВС
$selected_teacher = isset($_GET['teacher']) ? $_GET['teacher'] : '';
$selected_room = isset($_GET['room']) ? $_GET['room'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'weekly'; // weekly, daily, teacher, room

// Список классов
$classes = ['10-А', '10-Б', '11-А', '11-Б', '9-А', '9-Б', '8-А', '8-Б'];

// Дни недели
$week_days = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота'
];

// Предметы
$subjects = [
    'Математика', 'Русский язык', 'Физика', 'История', 'Химия',
    'Биология', 'Английский язык', 'Литература', 'География',
    'Информатика', 'Обществознание', 'Физкультура', 'ИЗО', 'Музыка', 'Технология'
];

// Учителя
$teachers = [
    'Иванова М.П.', 'Петрова С.И.', 'Сидоров А.В.', 'Кузнецова О.В.',
    'Михайлов Д.С.', 'Фёдорова Е.М.', 'Смирнова Е.А.', 'Васильев И.С.',
    'Николаева Т.В.', 'Алексеев П.А.', 'Сергеева Л.Н.', 'Павлов В.Г.'
];

// Кабинеты
$rooms = ['101', '102', '103', '104', '105', '201', '202', '203', '204', '205', 
          '301', '302', '303', '304', '305', '401', '402', '403', '404', '405'];

// Создаем таблицу schedule если ее нет
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule (
        id INT PRIMARY KEY AUTO_INCREMENT,
        class VARCHAR(20) NOT NULL,
        day_of_week INT NOT NULL,
        time_start TIME NOT NULL,
        time_end TIME NOT NULL,
        subject VARCHAR(100) NOT NULL,
        teacher VARCHAR(100) NOT NULL,
        room VARCHAR(20) NOT NULL,
        lesson_type ENUM('урок', 'практика', 'лабораторная', 'консультация') DEFAULT 'урок',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_class (class),
        INDEX idx_day (day_of_week),
        INDEX idx_teacher (teacher)
    )");
} catch (Exception $e) {
    // Таблица уже существует
}

// Заполняем тестовыми данными если таблица пуста
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM schedule");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $test_schedule = [
            // Понедельник (10-А)
            ['10-А', 1, '08:30:00', '09:15:00', 'Математика', 'Иванова М.П.', '302', 'урок'],
            ['10-А', 1, '09:25:00', '10:10:00', 'Русский язык', 'Петрова С.И.', '205', 'урок'],
            ['10-А', 1, '10:25:00', '11:10:00', 'Физика', 'Сидоров А.В.', '404', 'практика'],
            ['10-А', 1, '11:25:00', '12:10:00', 'История', 'Кузнецова О.В.', '105', 'урок'],
            
            // Вторник (10-А)
            ['10-А', 2, '08:30:00', '09:15:00', 'Химия', 'Михайлов Д.С.', '306', 'лабораторная'],
            ['10-А', 2, '09:25:00', '10:10:00', 'Биология', 'Фёдорова Е.М.', '308', 'урок'],
            ['10-А', 2, '10:25:00', '11:10:00', 'Английский язык', 'Смирнова Е.А.', '308', 'урок'],
            
            // Среда (10-А)
            ['10-А', 3, '08:30:00', '09:15:00', 'Математика', 'Иванова М.П.', '302', 'урок'],
            ['10-А', 3, '09:25:00', '10:10:00', 'Литература', 'Петрова С.И.', '205', 'урок'],
            ['10-А', 3, '10:25:00', '11:10:00', 'Физкультура', 'Васильев И.С.', 'спортзал', 'практика'],
            
            // 10-Б класс
            ['10-Б', 1, '08:30:00', '09:15:00', 'Физика', 'Сидоров А.В.', '404', 'урок'],
            ['10-Б', 1, '09:25:00', '10:10:00', 'Химия', 'Михайлов Д.С.', '306', 'лабораторная'],
            ['10-Б', 2, '08:30:00', '09:15:00', 'Математика', 'Иванова М.П.', '302', 'урок'],
            
            // 11-А класс
            ['11-А', 1, '08:30:00', '09:15:00', 'Информатика', 'Алексеев П.А.', '401', 'практика'],
            ['11-А', 1, '09:25:00', '10:10:00', 'Обществознание', 'Николаева Т.В.', '106', 'урок'],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO schedule (class, day_of_week, time_start, time_end, subject, teacher, room, lesson_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($test_schedule as $lesson) {
            $stmt->execute($lesson);
        }
    }
} catch (Exception $e) {
    // Игнорируем ошибки при заполнении
}

// Получаем расписание в зависимости от выбранных фильтров
$schedule_data = [];
$sql = "SELECT * FROM schedule WHERE 1=1";
$params = [];

if (!empty($selected_class) && $selected_class !== 'all') {
    $sql .= " AND class = ?";
    $params[] = $selected_class;
}

if (!empty($selected_day) && $selected_day > 0) {
    $sql .= " AND day_of_week = ?";
    $params[] = $selected_day;
}

if (!empty($selected_teacher) && $selected_teacher !== 'all') {
    $sql .= " AND teacher = ?";
    $params[] = $selected_teacher;
}

if (!empty($selected_room) && $selected_room !== 'all') {
    $sql .= " AND room = ?";
    $params[] = $selected_room;
}

$sql .= " ORDER BY day_of_week, time_start, class";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedule_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $schedule_data = [];
}

// Группируем данные по дням и классам для отображения
$grouped_schedule = [];
foreach ($schedule_data as $lesson) {
    $day = $lesson['day_of_week'];
    $class = $lesson['class'];
    if (!isset($grouped_schedule[$day])) {
        $grouped_schedule[$day] = [];
    }
    if (!isset($grouped_schedule[$day][$class])) {
        $grouped_schedule[$day][$class] = [];
    }
    $grouped_schedule[$day][$class][] = $lesson;
}

// Обработка добавления урока
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lesson']) && ($current_role === 'teacher' || $current_role === 'admin')) {
    $class = $_POST['class'];
    $day_of_week = $_POST['day_of_week'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $subject = $_POST['subject'];
    $teacher = $_POST['teacher'];
    $room = $_POST['room'];
    $lesson_type = $_POST['lesson_type'];
    
    try {
        // Проверяем, нет ли конфликта по времени в том же кабинете
        $check_sql = "SELECT * FROM schedule 
                      WHERE day_of_week = ? 
                      AND room = ? 
                      AND ((time_start <= ? AND time_end > ?) 
                      OR (time_start < ? AND time_end >= ?)
                      OR (time_start >= ? AND time_end <= ?))";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$day_of_week, $room, $time_start, $time_start, $time_end, $time_end, $time_start, $time_end]);
        $conflict = $check_stmt->fetch();
        
        if ($conflict) {
            $_SESSION['error'] = "Конфликт расписания! В это время в кабинете $room уже идет урок: {$conflict['subject']} ({$conflict['class']})";
        } else {
            // Проверяем, нет ли у учителя другого урока в это время
            $teacher_check = "SELECT * FROM schedule 
                             WHERE day_of_week = ? 
                             AND teacher = ? 
                             AND ((time_start <= ? AND time_end > ?) 
                             OR (time_start < ? AND time_end >= ?))";
            $teacher_stmt = $pdo->prepare($teacher_check);
            $teacher_stmt->execute([$day_of_week, $teacher, $time_start, $time_start, $time_end, $time_end]);
            $teacher_conflict = $teacher_stmt->fetch();
            
            if ($teacher_conflict) {
                $_SESSION['error'] = "Учитель $teacher уже ведет урок в это время: {$teacher_conflict['subject']} ({$teacher_conflict['class']})";
            } else {
                // Добавляем урок
                $insert_sql = "INSERT INTO schedule (class, day_of_week, time_start, time_end, subject, teacher, room, lesson_type) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$class, $day_of_week, $time_start, $time_end, $subject, $teacher, $room, $lesson_type]);
                
                $_SESSION['success'] = "Урок успешно добавлен в расписание";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при добавлении урока: " . $e->getMessage();
    }
}

// Обработка удаления урока
if (isset($_GET['delete_lesson']) && ($current_role === 'teacher' || $current_role === 'admin')) {
    $lesson_id = intval($_GET['delete_lesson']);
    
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
        $delete_stmt->execute([$lesson_id]);
        $_SESSION['success'] = "Урок удален из расписания";
        
        // Обновляем текущую страницу без параметра удаления
        header("Location: schedule.php?" . http_build_query(array_diff_key($_GET, ['delete_lesson' => ''])));
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при удалении урока: " . $e->getMessage();
    }
}

// Получаем статистику
$stats = [];
try {
    // Количество уроков в день
    $stmt = $pdo->query("SELECT day_of_week, COUNT(*) as count FROM schedule GROUP BY day_of_week ORDER BY day_of_week");
    $stats['lessons_per_day'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Занятость кабинетов
    $stmt = $pdo->query("SELECT room, COUNT(*) as count FROM schedule GROUP BY room ORDER BY count DESC LIMIT 10");
    $stats['busy_rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Нагрузка учителей
    $stmt = $pdo->query("SELECT teacher, COUNT(*) as count FROM schedule GROUP BY teacher ORDER BY count DESC LIMIT 10");
    $stats['teacher_load'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Если статистика недоступна
    $stats = [];
}

// Определяем текущую неделю
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание занятий</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .schedule-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
            border: none;
        }
        
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }
        
        .day-header {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px;
        }
        
        .lesson-item {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s;
        }
        
        .lesson-item:hover {
            background-color: #f8f9fa;
            border-left-width: 6px;
        }
        
        .lesson-type-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .time-badge {
            background-color: #e9ecef;
            color: var(--dark-color);
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .room-badge {
            background-color: #e8f4ff;
            color: var(--primary-color);
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .teacher-badge {
            background-color: #f0f9ff;
            color: var(--info-color);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .class-badge {
            background: linear-gradient(135deg, var(--success-color), #1e7e34);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .filter-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            color: white;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: scale(1.02);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .timetable-view {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .timetable-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
        }
        
        .timetable-cell {
            border: 1px solid #dee2e6;
            padding: 10px;
            min-height: 80px;
            background: white;
        }
        
        .timetable-cell.empty {
            background-color: #f8f9fa;
        }
        
        .timetable-cell.current {
            background-color: #fff3cd;
            border-color: var(--warning-color);
        }
        
        .timetable-time {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 15px 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .lesson-popup {
            position: absolute;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 15px;
            min-width: 250px;
            border: 1px solid #dee2e6;
            display: none;
        }
        
        .view-toggle .btn {
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .week-navigation {
            background: white;
            border-radius: 10px;
            padding: 10px 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .day-column {
            min-height: 400px;
        }
        
        .conflict-warning {
            border-left: 4px solid var(--danger-color);
            background-color: #f8d7da;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .timetable-view {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .lesson-item {
                break-inside: avoid;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        .drag-handle {
            cursor: move;
            color: #adb5bd;
            margin-right: 10px;
        }
        
        .drag-handle:hover {
            color: var(--primary-color);
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Цвета для типов уроков */
        .type-lesson { border-left-color: var(--primary-color); }
        .type-practice { border-left-color: var(--info-color); }
        .type-lab { border-left-color: var(--success-color); }
        .type-consultation { border-left-color: var(--warning-color); }
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
                <i class="bi bi-calendar-week"></i> Расписание занятий
            </span>
            <div class="d-flex align-items-center">
                <?php if ($current_role === 'teacher' || $current_role === 'admin'): ?>
                <button class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                    <i class="bi bi-plus-circle"></i> Добавить урок
                </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-light" onclick="window.print()">
                    <i class="bi bi-printer"></i> Печать
                </button>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Панель фильтров -->
        <div class="filter-panel no-print">
            <div class="row align-items-center">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Класс</label>
                    <select name="class" class="form-select" onchange="updateFilters()" id="classSelect">
                        <option value="all">Все классы</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class; ?>" <?php echo $selected_class == $class ? 'selected' : ''; ?>>
                                <?php echo $class; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">День недели</label>
                    <select name="day" class="form-select" onchange="updateFilters()" id="daySelect">
                        <option value="0">Все дни</option>
                        <?php foreach ($week_days as $day_num => $day_name): ?>
                            <option value="<?php echo $day_num; ?>" <?php echo $selected_day == $day_num ? 'selected' : ''; ?>>
                                <?php echo $day_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Учитель</label>
                    <select name="teacher" class="form-select" onchange="updateFilters()" id="teacherSelect">
                        <option value="all">Все учителя</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher; ?>" <?php echo $selected_teacher == $teacher ? 'selected' : ''; ?>>
                                <?php echo $teacher; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Кабинет</label>
                    <select name="room" class="form-select" onchange="updateFilters()" id="roomSelect">
                        <option value="all">Все кабинеты</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room; ?>" <?php echo $selected_room == $room ? 'selected' : ''; ?>>
                                <?php echo $room; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="view-toggle">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary <?php echo $view_mode == 'weekly' ? 'active' : ''; ?>" 
                                        onclick="changeViewMode('weekly')">
                                    <i class="bi bi-calendar-week"></i> Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary <?php echo $view_mode == 'daily' ? 'active' : ''; ?>" 
                                        onclick="changeViewMode('daily')">
                                    <i class="bi bi-calendar-day"></i> День
                                </button>
                                <?php if ($current_role === 'teacher' || $current_role === 'admin'): ?>
                                <button type="button" class="btn btn-outline-primary <?php echo $view_mode == 'teacher' ? 'active' : ''; ?>" 
                                        onclick="changeViewMode('teacher')">
                                    <i class="bi bi-person"></i> По учителям
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="week-navigation">
                            <span class="text-muted me-3">
                                <i class="bi bi-calendar"></i> Неделя: 
                                <strong><?php echo date('d.m.Y', strtotime($week_start)); ?> - <?php echo date('d.m.Y', strtotime($week_end)); ?></strong>
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="changeWeek(-1)">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="changeWeek(1)">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="row mb-4 no-print">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), #2c3e50);">
                    <i class="bi bi-journal-bookmark"></i>
                    <h4><?php echo count($schedule_data); ?></h4>
                    <p class="mb-0">Всего уроков</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--info-color), #138496);">
                    <i class="bi bi-people"></i>
                    <h4><?php echo count(array_unique(array_column($schedule_data, 'class'))); ?></h4>
                    <p class="mb-0">Классов</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--success-color), #1e7e34);">
                    <i class="bi bi-person-check"></i>
                    <h4><?php echo count(array_unique(array_column($schedule_data, 'teacher'))); ?></h4>
                    <p class="mb-0">Учителей</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--warning-color), #e0a800);">
                    <i class="bi bi-door-closed"></i>
                    <h4><?php echo count(array_unique(array_column($schedule_data, 'room'))); ?></h4>
                    <p class="mb-0">Кабинетов</p>
                </div>
            </div>
        </div>
        
        <!-- Основное расписание -->
        <div class="row">
            <div class="col-12">
                <?php if ($view_mode == 'weekly'): ?>
                    <!-- Недельный вид -->
                    <div class="timetable-view animate-fade-in">
                        <div class="timetable-header">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-week"></i> Расписание на неделю
                                <?php if ($selected_class != 'all'): ?>
                                    <span class="badge bg-light text-dark ms-2">Класс: <?php echo $selected_class; ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Время</th>
                                        <?php foreach ($week_days as $day_num => $day_name): ?>
                                            <th class="text-center <?php echo $day_num == date('N') ? 'table-warning' : ''; ?>">
                                                <?php echo $day_name; ?>
                                                <div class="small"><?php echo date('d.m', strtotime("+".($day_num-1)." days", strtotime($week_start))); ?></div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Временные слоты
                                    $time_slots = [
                                        ['08:30', '09:15'],
                                        ['09:25', '10:10'],
                                        ['10:25', '11:10'],
                                        ['11:25', '12:10'],
                                        ['12:25', '13:10'],
                                        ['13:20', '14:05'],
                                        ['14:15', '15:00']
                                    ];
                                    
                                    foreach ($time_slots as $slot): 
                                        $time_start = $slot[0];
                                        $time_end = $slot[1];
                                    ?>
                                    <tr>
                                        <td class="timetable-time">
                                            <?php echo $time_start; ?><br>
                                            <small class="text-muted"><?php echo $time_end; ?></small>
                                        </td>
                                        
                                        <?php foreach ($week_days as $day_num => $day_name): 
                                            $lessons_in_slot = array_filter($schedule_data, function($lesson) use ($day_num, $time_start) {
                                                return $lesson['day_of_week'] == $day_num && 
                                                       substr($lesson['time_start'], 0, 5) == $time_start;
                                            });
                                        ?>
                                            <td class="timetable-cell <?php echo empty($lessons_in_slot) ? 'empty' : ''; ?>">
                                                <?php foreach ($lessons_in_slot as $lesson): 
                                                    $type_class = 'type-' . str_replace(['лабораторная'], ['lab'], $lesson['lesson_type']);
                                                ?>
                                                    <div class="lesson-item <?php echo $type_class; ?>">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <strong><?php echo $lesson['subject']; ?></strong>
                                                                <span class="lesson-type-badge bg-light text-dark ms-2">
                                                                    <?php echo $lesson['lesson_type']; ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <span class="room-badge"><?php echo $lesson['room']; ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <span class="teacher-badge">
                                                                    <i class="bi bi-person"></i> <?php echo $lesson['teacher']; ?>
                                                                </span>
                                                            </div>
                                                            <div class="text-end">
                                                                <small class="text-muted"><?php echo $lesson['class']; ?></small>
                                                            </div>
                                                        </div>
                                                        <?php if ($current_role === 'teacher' || $current_role === 'admin'): ?>
                                                        <div class="mt-2 text-end">
                                                            <a href="schedule.php?<?php echo http_build_query(array_merge($_GET, ['delete_lesson' => $lesson['id']])); ?>" 
                                                               class="btn btn-sm btn-outline-danger" 
                                                               onclick="return confirm('Удалить этот урок из расписания?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($view_mode == 'daily'): ?>
                    <!-- Дневной вид -->
                    <div class="row animate-fade-in">
                        <?php foreach ($week_days as $day_num => $day_name): 
                            if ($selected_day > 0 && $day_num != $selected_day) continue;
                            
                            $day_lessons = array_filter($schedule_data, function($lesson) use ($day_num) {
                                return $lesson['day_of_week'] == $day_num;
                            });
                            
                            // Группируем по классам
                            $grouped_by_class = [];
                            foreach ($day_lessons as $lesson) {
                                $class = $lesson['class'];
                                if (!isset($grouped_by_class[$class])) {
                                    $grouped_by_class[$class] = [];
                                }
                                $grouped_by_class[$class][] = $lesson;
                            }
                        ?>
                            <div class="col-md-<?php echo $selected_day > 0 ? '12' : '6'; ?> mb-4">
                                <div class="schedule-card">
                                    <div class="day-header">
                                        <h5 class="mb-0">
                                            <?php echo $day_name; ?>
                                            <small class="opacity-75"><?php echo date('d.m.Y', strtotime("+".($day_num-1)." days", strtotime($week_start))); ?></small>
                                            <span class="badge bg-light text-dark float-end">
                                                <?php echo count($day_lessons); ?> уроков
                                            </span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($day_lessons)): ?>
                                            <div class="empty-state">
                                                <i class="bi bi-calendar-x"></i>
                                                <h6>Нет уроков</h6>
                                                <p class="text-muted">На этот день уроки не расписаны</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($grouped_by_class as $class => $lessons): ?>
                                                <div class="mb-4">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <span class="class-badge"><?php echo $class; ?></span>
                                                        <span class="ms-3 text-muted small">
                                                            <?php echo count($lessons); ?> урока(ов)
                                                        </span>
                                                    </div>
                                                    
                                                    <?php 
                                                    // Сортируем уроки по времени
                                                    usort($lessons, function($a, $b) {
                                                        return strtotime($a['time_start']) - strtotime($b['time_start']);
                                                    });
                                                    
                                                    foreach ($lessons as $lesson): 
                                                        $type_class = 'type-' . str_replace(['лабораторная'], ['lab'], $lesson['lesson_type']);
                                                    ?>
                                                        <div class="lesson-item <?php echo $type_class; ?> mb-3">
                                                            <div class="d-flex align-items-start">
                                                                <div class="time-badge me-3">
                                                                    <?php echo substr($lesson['time_start'], 0, 5); ?> - <?php echo substr($lesson['time_end'], 0, 5); ?>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            <h6 class="mb-1"><?php echo $lesson['subject']; ?></h6>
                                                                            <div class="d-flex flex-wrap gap-2">
                                                                                <span class="teacher-badge">
                                                                                    <i class="bi bi-person"></i> <?php echo $lesson['teacher']; ?>
                                                                                </span>
                                                                                <span class="room-badge">
                                                                                    <i class="bi bi-door-closed"></i> <?php echo $lesson['room']; ?>
                                                                                </span>
                                                                                <span class="lesson-type-badge bg-light text-dark">
                                                                                    <?php echo $lesson['lesson_type']; ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                        <?php if ($current_role === 'teacher' || $current_role === 'admin'): ?>
                                                                        <div class="btn-group btn-group-sm">
                                                                            <button class="btn btn-outline-primary" 
                                                                                    data-bs-toggle="modal" 
                                                                                    data-bs-target="#editLessonModal"
                                                                                    onclick="editLesson(<?php echo htmlspecialchars(json_encode($lesson)); ?>)">
                                                                                <i class="bi bi-pencil"></i>
                                                                            </button>
                                                                            <a href="schedule.php?<?php echo http_build_query(array_merge($_GET, ['delete_lesson' => $lesson['id']])); ?>" 
                                                                               class="btn btn-outline-danger" 
                                                                               onclick="return confirm('Удалить этот урок из расписания?')">
                                                                                <i class="bi bi-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($view_mode == 'teacher' && ($current_role === 'teacher' || $current_role === 'admin')): ?>
                    <!-- Вид по учителям -->
                    <div class="timetable-view animate-fade-in">
                        <div class="timetable-header">
                            <h5 class="mb-0"><i class="bi bi-person"></i> Нагрузка учителей</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Учитель</th>
                                        <?php foreach ($week_days as $day_num => $day_name): ?>
                                            <th class="text-center"><?php echo $day_name; ?></th>
                                        <?php endforeach; ?>
                                        <th class="text-center">Всего</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Группируем по учителям
                                    $teacher_stats = [];
                                    foreach ($schedule_data as $lesson) {
                                        $teacher = $lesson['teacher'];
                                        if (!isset($teacher_stats[$teacher])) {
                                            $teacher_stats[$teacher] = [
                                                'total' => 0,
                                                'days' => array_fill(1, 6, 0)
                                            ];
                                        }
                                        $teacher_stats[$teacher]['total']++;
                                        $teacher_stats[$teacher]['days'][$lesson['day_of_week']]++;
                                    }
                                    
                                    // Сортируем по количеству уроков
                                    uasort($teacher_stats, function($a, $b) {
                                        return $b['total'] - $a['total'];
                                    });
                                    
                                    foreach ($teacher_stats as $teacher => $stats):
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $teacher; ?></strong>
                                            <div class="small text-muted">
                                                <?php 
                                                $teacher_subjects = array_unique(array_column(
                                                    array_filter($schedule_data, function($lesson) use ($teacher) {
                                                        return $lesson['teacher'] == $teacher;
                                                    }), 
                                                    'subject'
                                                ));
                                                echo implode(', ', $teacher_subjects);
                                                ?>
                                            </div>
                                        </td>
                                        <?php foreach ($week_days as $day_num => $day_name): ?>
                                            <td class="text-center">
                                                <?php if ($stats['days'][$day_num] > 0): ?>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo $stats['days'][$day_num]; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill">
                                                <?php echo $stats['total']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Статистика занятости -->
        <div class="row mt-4 no-print">
            <div class="col-md-6">
                <div class="schedule-card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-door-closed"></i> Самые загруженные кабинеты</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['busy_rooms'])): ?>
                            <?php foreach ($stats['busy_rooms'] as $room): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><?php echo $room['room']; ?></span>
                                        <span class="badge bg-info"><?php echo $room['count']; ?> уроков</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo min(($room['count'] / max(array_column($stats['busy_rooms'], 'count')) * 100), 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">Нет данных</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="schedule-card">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bi bi-graph-up"></i> Уроки по дням недели</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['lessons_per_day'])): ?>
                            <?php foreach ($stats['lessons_per_day'] as $day): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><?php echo $week_days[$day['day_of_week']] ?? 'Неизвестно'; ?></span>
                                        <span class="badge bg-warning"><?php echo $day['count']; ?> уроков</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo min(($day['count'] / max(array_column($stats['lessons_per_day'], 'count')) * 100), 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">Нет данных</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно добавления урока -->
    <?php if ($current_role === 'teacher' || $current_role === 'admin'): ?>
    <div class="modal fade" id="addLessonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Добавить урок в расписание</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Класс *</label>
                                    <select name="class" class="form-select" required>
                                        <option value="">Выберите класс</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">День недели *</label>
                                    <select name="day_of_week" class="form-select" required>
                                        <option value="">Выберите день</option>
                                        <?php foreach ($week_days as $day_num => $day_name): ?>
                                            <option value="<?php echo $day_num; ?>"><?php echo $day_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время начала *</label>
                                    <select name="time_start" class="form-select" required>
                                        <option value="">Выберите время</option>
                                        <?php 
                                        $start_times = ['08:30', '09:25', '10:25', '11:25', '12:25', '13:20', '14:15'];
                                        foreach ($start_times as $time): ?>
                                            <option value="<?php echo $time; ?>:00"><?php echo $time; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время окончания *</label>
                                    <select name="time_end" class="form-select" required>
                                        <option value="">Выберите время</option>
                                        <?php 
                                        $end_times = ['09:15', '10:10', '11:10', '12:10', '13:10', '14:05', '15:00'];
                                        foreach ($end_times as $time): ?>
                                            <option value="<?php echo $time; ?>:00"><?php echo $time; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Предмет *</label>
                                    <select name="subject" class="form-select" required>
                                        <option value="">Выберите предмет</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject; ?>"><?php echo $subject; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Тип урока</label>
                                    <select name="lesson_type" class="form-select">
                                        <option value="урок">Урок</option>
                                        <option value="практика">Практика</option>
                                        <option value="лабораторная">Лабораторная</option>
                                        <option value="консультация">Консультация</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Учитель *</label>
                                    <select name="teacher" class="form-select" required>
                                        <option value="">Выберите учителя</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher; ?>"><?php echo $teacher; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Кабинет *</label>
                                    <select name="room" class="form-select" required>
                                        <option value="">Выберите кабинет</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room; ?>"><?php echo $room; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Система автоматически проверит наличие конфликтов по времени в кабинете и у учителя.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_lesson" class="btn btn-primary">Добавить урок</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно редактирования урока -->
    <div class="modal fade" id="editLessonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editLessonForm">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Редактировать урок</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="lesson_id" id="editLessonId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Класс *</label>
                                    <select name="class" id="editClass" class="form-select" required>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">День недели *</label>
                                    <select name="day_of_week" id="editDayOfWeek" class="form-select" required>
                                        <?php foreach ($week_days as $day_num => $day_name): ?>
                                            <option value="<?php echo $day_num; ?>"><?php echo $day_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время начала *</label>
                                    <input type="time" name="time_start" id="editTimeStart" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Время окончания *</label>
                                    <input type="time" name="time_end" id="editTimeEnd" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Предмет *</label>
                                    <select name="subject" id="editSubject" class="form-select" required>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject; ?>"><?php echo $subject; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Тип урока</label>
                                    <select name="lesson_type" id="editLessonType" class="form-select">
                                        <option value="урок">Урок</option>
                                        <option value="практика">Практика</option>
                                        <option value="лабораторная">Лабораторная</option>
                                        <option value="консультация">Консультация</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Учитель *</label>
                                    <select name="teacher" id="editTeacher" class="form-select" required>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher; ?>"><?php echo $teacher; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Кабинет *</label>
                                    <select name="room" id="editRoom" class="form-select" required>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room; ?>"><?php echo $room; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="edit_lesson" class="btn btn-warning">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Обновление фильтров
        function updateFilters() {
            const classSelect = document.getElementById('classSelect');
            const daySelect = document.getElementById('daySelect');
            const teacherSelect = document.getElementById('teacherSelect');
            const roomSelect = document.getElementById('roomSelect');
            
            const params = new URLSearchParams(window.location.search);
            
            if (classSelect.value !== 'all') params.set('class', classSelect.value);
            else params.delete('class');
            
            if (daySelect.value !== '0') params.set('day', daySelect.value);
            else params.delete('day');
            
            if (teacherSelect.value !== 'all') params.set('teacher', teacherSelect.value);
            else params.delete('teacher');
            
            if (roomSelect.value !== 'all') params.set('room', roomSelect.value);
            else params.delete('room');
            
            window.location.href = 'schedule.php?' + params.toString();
        }
        
        // Изменение режима просмотра
        function changeViewMode(mode) {
            const params = new URLSearchParams(window.location.search);
            params.set('view', mode);
            window.location.href = 'schedule.php?' + params.toString();
        }
        
        // Навигация по неделям
        function changeWeek(direction) {
            alert('Навигация по неделям будет реализована в следующей версии');
            // Здесь можно добавить логику изменения недели
        }
        
        // Редактирование урока
        function editLesson(lesson) {
            document.getElementById('editLessonId').value = lesson.id;
            document.getElementById('editClass').value = lesson.class;
            document.getElementById('editDayOfWeek').value = lesson.day_of_week;
            document.getElementById('editTimeStart').value = lesson.time_start.substring(0, 5);
            document.getElementById('editTimeEnd').value = lesson.time_end.substring(0, 5);
            document.getElementById('editSubject').value = lesson.subject;
            document.getElementById('editTeacher').value = lesson.teacher;
            document.getElementById('editRoom').value = lesson.room;
            document.getElementById('editLessonType').value = lesson.lesson_type;
            
            // Настраиваем форму для редактирования
            const form = document.getElementById('editLessonForm');
            form.action = 'update_schedule.php?id=' + lesson.id;
        }
        
        // Подсказки при наведении
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Автоматическая проверка конфликтов при выборе времени
            const timeStart = document.querySelector('select[name="time_start"]');
            const timeEnd = document.querySelector('select[name="time_end"]');
            
            if (timeStart && timeEnd) {
                timeStart.addEventListener('change', function() {
                    const startIndex = timeStart.selectedIndex;
                    if (timeEnd.options[startIndex]) {
                        timeEnd.selectedIndex = startIndex;
                    }
                });
            }
        });
        
        // Экспорт расписания
        function exportSchedule(format) {
            const date = new Date().toISOString().split('T')[0];
            let filename = `Расписание_${date}`;
            
            if (format === 'csv') {
                // Простой экспорт в CSV
                let csv = [];
                const rows = document.querySelectorAll('table tr');
                
                rows.forEach(row => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td, th');
                    cells.forEach(cell => {
                        rowData.push(`"${cell.textContent.trim()}"`);
                    });
                    csv.push(rowData.join(','));
                });
                
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename + '.csv';
                link.click();
            } else {
                alert('Экспорт в PDF будет доступен в следующей версии');
            }
        }
        
        // Поиск уроков
        function searchLessons() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            
            document.querySelectorAll('.lesson-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                    item.classList.add('animate-fade-in');
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Автозаполнение формы при выборе предмета
        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.querySelector('select[name="subject"]');
            const teacherSelect = document.querySelector('select[name="teacher"]');
            
            // Маппинг предметов к учителям
            const subjectTeachers = {
                'Математика': 'Иванова М.П.',
                'Русский язык': 'Петрова С.И.',
                'Физика': 'Сидоров А.В.',
                'История': 'Кузнецова О.В.',
                'Химия': 'Михайлов Д.С.',
                'Биология': 'Фёдорова Е.М.',
                'Английский язык': 'Смирнова Е.А.',
                'Литература': 'Петрова С.И.',
                'География': 'Васильев И.С.',
                'Информатика': 'Алексеев П.А.',
                'Обществознание': 'Николаева Т.В.',
                'Физкультура': 'Сергеева Л.Н.',
                'ИЗО': 'Павлов В.Г.',
                'Музыка': 'Павлов В.Г.',
                'Технология': 'Павлов В.Г.'
            };
            
            if (subjectSelect && teacherSelect) {
                subjectSelect.addEventListener('change', function() {
                    const subject = this.value;
                    if (subjectTeachers[subject]) {
                        teacherSelect.value = subjectTeachers[subject];
                    }
                });
            }
        });
    </script>
</body>
</html>