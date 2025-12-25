<?php

require_once 'auth.php';

// Проверяем доступ
if (!isTeacher() && !isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Подключаем базу данных
require_once 'database.php';
$pdo = Database::getConnection();

// Функция для получения классов
function getClasses($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return ['10-А', '10-Б', '11-А', '11-Б'];
    }
}

// Получаем список классов
$classes = getClasses($pdo);

// Обработка GET параметров
$selected_class = $_GET['class'] ?? '';
$search = $_GET['search'] ?? '';

// Построение SQL запроса
$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($selected_class)) {
    $sql .= " AND class = ?";
    $params[] = $selected_class;
}

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY class, full_name";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $students = [];
}

// Обработка добавления ученика
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $full_name = trim($_POST['full_name']);
    $class = $_POST['class'];
    $birth_date = $_POST['birth_date'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO students 
            (full_name, class, birth_date, phone, email, parent_name, parent_phone, address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $full_name, $class, $birth_date, $phone, $email, 
            $parent_name, $parent_phone, $address
        ]);
        
        $_SESSION['success'] = "Ученик успешно добавлен";
        header('Location: students.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при добавлении ученика: " . $e->getMessage();
    }
}

// Обработка удаления ученика
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Ученик успешно удален";
        header('Location: students.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Ошибка при удалении ученика: " . $e->getMessage();
    }
}

// Получаем статистику
$total_students = count($students);
$class_stats = [];

foreach ($students as $student) {
    $class = $student['class'];
    if (!isset($class_stats[$class])) {
        $class_stats[$class] = 0;
    }
    $class_stats[$class]++;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление учениками</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4a6fa5, #2c3e50);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .badge-class {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        
        .student-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        
        .action-buttons .btn {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
        }
        
        .stat-card {
            border-left: 4px solid #4a6fa5;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .filter-card {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(74, 111, 165, 0.05);
        }
        
        .avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a6fa5, #2c3e50);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
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
                <i class="bi bi-people"></i> Управление учениками
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
        
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Всего учеников</h6>
                                <h3 class="mb-0"><?php echo $total_students; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Классов</h6>
                                <h3 class="mb-0"><?php echo count($class_stats); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-building text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Распределение по классам</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($class_stats as $class => $count): ?>
                                <span class="badge bg-primary badge-class">
                                    <?php echo $class; ?>: <?php echo $count; ?> чел.
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Панель фильтров и поиска -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filter-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Класс</label>
                                        <select name="class" class="form-select" onchange="this.form.submit()">
                                            <option value="">Все классы</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class; ?>" 
                                                    <?php echo $selected_class == $class ? 'selected' : ''; ?>>
                                                    <?php echo $class; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Поиск ученика</label>
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" 
                                                   placeholder="ФИО, телефон или email" value="<?php echo htmlspecialchars($search); ?>">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <a href="students.php" class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-arrow-clockwise"></i> Сбросить
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Список учеников -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-person-lines-fill"></i> Список учеников
                            <?php if ($selected_class): ?>
                                <span class="badge bg-primary ms-2">Класс: <?php echo $selected_class; ?></span>
                            <?php endif; ?>
                        </h5>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus"></i> Добавить ученика
                        </button>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                                <h5 class="mt-3">Ученики не найдены</h5>
                                <p class="text-muted">Добавьте первого ученика или измените параметры поиска</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Фото</th>
                                            <th>ФИО</th>
                                            <th>Класс</th>
                                            <th>Телефон</th>
                                            <th>Email</th>
                                            <th>Дата рождения</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $index => $student): 
                                            $initials = '';
                                            $name_parts = explode(' ', $student['full_name']);
                                            if (count($name_parts) >= 2) {
                                                $initials = mb_substr($name_parts[0], 0, 1) . mb_substr($name_parts[1], 0, 1);
                                            } else {
                                                $initials = mb_substr($student['full_name'], 0, 2);
                                            }
                                            $initials = mb_strtoupper($initials);
                                        ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <div class="avatar-placeholder">
                                                    <?php echo $initials; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                <?php if ($student['parent_name']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Родитель: <?php echo htmlspecialchars($student['parent_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $student['class']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($student['phone']): ?>
                                                    <a href="tel:<?php echo $student['phone']; ?>" class="text-decoration-none">
                                                        <i class="bi bi-telephone"></i> <?php echo $student['phone']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['email']): ?>
                                                    <a href="mailto:<?php echo $student['email']; ?>" class="text-decoration-none">
                                                        <i class="bi bi-envelope"></i> <?php echo $student['email']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['birth_date']): ?>
                                                    <?php echo date('d.m.Y', strtotime($student['birth_date'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php 
                                                            $birth_date = new DateTime($student['birth_date']);
                                                            $today = new DateTime();
                                                            $age = $today->diff($birth_date)->y;
                                                            echo $age . ' лет';
                                                        ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewStudentModal"
                                                            onclick="viewStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editStudentModal"
                                                            onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="students.php?delete=<?php echo $student['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Удалить ученика <?php echo addslashes($student['full_name']); ?>?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">
                                    Показано: <strong><?php echo count($students); ?></strong> из <?php echo $total_students; ?> учеников
                                </span>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Экспорт в Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно добавления ученика -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus"></i> Добавить нового ученика
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО ученика *</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Класс *</label>
                                    <select name="class" class="form-select" required>
                                        <option value="">Выберите класс</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Дата рождения</label>
                                    <input type="date" name="birth_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Телефон ученика</label>
                                    <input type="tel" name="phone" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email ученика</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО родителя</label>
                                    <input type="text" name="parent_name" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Телефон родителя</label>
                                    <input type="tel" name="parent_phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Адрес проживания</label>
                                    <textarea name="address" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_student" class="btn btn-primary">Добавить ученика</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно просмотра ученика -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewStudentName"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-placeholder mx-auto mb-3" id="viewStudentAvatar"></div>
                        <h4 id="viewStudentFullName"></h4>
                        <span class="badge bg-primary" id="viewStudentClass"></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-telephone text-primary"></i> Контакты</h6>
                            <p>
                                Телефон: <span id="viewStudentPhone" class="fw-bold"></span><br>
                                Email: <span id="viewStudentEmail"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-calendar-heart text-success"></i> Личные данные</h6>
                            <p>
                                Дата рождения: <span id="viewStudentBirthDate"></span><br>
                                Возраст: <span id="viewStudentAge"></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="bi bi-house text-warning"></i> Информация о родителях</h6>
                            <p>
                                Родитель: <span id="viewStudentParent"></span><br>
                                Телефон родителя: <span id="viewStudentParentPhone"></span><br>
                                Адрес: <span id="viewStudentAddress"></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно редактирования ученика -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editStudentForm">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square"></i> Редактировать ученика
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="editStudentId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО ученика *</label>
                                    <input type="text" name="full_name" id="editFullName" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Класс *</label>
                                    <select name="class" id="editClass" class="form-select" required>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class; ?>"><?php echo $class; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Дата рождения</label>
                                    <input type="date" name="birth_date" id="editBirthDate" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Телефон ученика</label>
                                    <input type="tel" name="phone" id="editPhone" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email ученика</label>
                                    <input type="email" name="email" id="editEmail" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО родителя</label>
                                    <input type="text" name="parent_name" id="editParentName" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Телефон родителя</label>
                                    <input type="tel" name="parent_phone" id="editParentPhone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Адрес проживания</label>
                                    <textarea name="address" id="editAddress" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="update_student" class="btn btn-warning">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Функция для просмотра ученика
        function viewStudent(student) {
            document.getElementById('viewStudentName').textContent = 'Информация об ученике';
            document.getElementById('viewStudentFullName').textContent = student.full_name;
            document.getElementById('viewStudentClass').textContent = student.class;
            document.getElementById('viewStudentPhone').textContent = student.phone || '—';
            document.getElementById('viewStudentEmail').textContent = student.email || '—';
            document.getElementById('viewStudentParent').textContent = student.parent_name || '—';
            document.getElementById('viewStudentParentPhone').textContent = student.parent_phone || '—';
            document.getElementById('viewStudentAddress').textContent = student.address || '—';
            
            // Аватар
            let initials = '';
            let nameParts = student.full_name.split(' ');
            if (nameParts.length >= 2) {
                initials = nameParts[0][0] + nameParts[1][0];
            } else {
                initials = student.full_name.substring(0, 2);
            }
            document.getElementById('viewStudentAvatar').textContent = initials.toUpperCase();
            
            // Дата рождения и возраст
            if (student.birth_date) {
                let birthDate = new Date(student.birth_date);
                let options = { day: 'numeric', month: 'long', year: 'numeric' };
                document.getElementById('viewStudentBirthDate').textContent = 
                    birthDate.toLocaleDateString('ru-RU', options);
                
                let today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                let m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('viewStudentAge').textContent = age + ' лет';
            } else {
                document.getElementById('viewStudentBirthDate').textContent = '—';
                document.getElementById('viewStudentAge').textContent = '—';
            }
        }
        
        // Функция для редактирования ученика
        function editStudent(student) {
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editFullName').value = student.full_name;
            document.getElementById('editClass').value = student.class;
            document.getElementById('editBirthDate').value = student.birth_date || '';
            document.getElementById('editPhone').value = student.phone || '';
            document.getElementById('editEmail').value = student.email || '';
            document.getElementById('editParentName').value = student.parent_name || '';
            document.getElementById('editParentPhone').value = student.parent_phone || '';
            document.getElementById('editAddress').value = student.address || '';
            
            // Обновляем action формы для редактирования
            document.getElementById('editStudentForm').action = 'update_student.php?id=' + student.id;
        }
        
        // Экспорт в Excel
        function exportToExcel() {
            const table = document.querySelector('table');
            const workbook = XLSX.utils.table_to_book(table, {sheet: "Ученики"});
            XLSX.writeFile(workbook, 'Ученики_Школа_123.xlsx');
        }
        
        // Инициализация DataTables
        document.addEventListener('DOMContentLoaded', function() {
            $('table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ru.json'
                },
                pageLength: 25,
                responsive: true
            });
        });
    </script>
</body>
</html>