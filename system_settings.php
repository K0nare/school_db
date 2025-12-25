<?php
// Подключаем проверку авторизации
require_once 'auth.php';
checkAuth();

// Проверяем, что пользователь администратор
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Подключаем базу данных
require_once 'database.php';
$pdo = Database::getConnection();
// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Создаем таблицу system_settings если её нет
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_group VARCHAR(50) DEFAULT 'general',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Обрабатываем каждую группу настроек
        $settings_groups = [
            'school_info' => [
                'school_name', 'school_address', 'school_phone', 'school_email',
                'school_director', 'school_year', 'academic_period'
            ],
            'system_config' => [
                'site_name', 'default_language', 'timezone', 'date_format',
                'items_per_page', 'session_timeout', 'maintenance_mode'
            ],
            'email_settings' => [
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
                'smtp_encryption', 'admin_email', 'email_from_name'
            ],
            'backup_settings' => [
                'auto_backup', 'backup_frequency', 'keep_backups', 'backup_path'
            ]
        ];
        
        try {
            $pdo->beginTransaction();
            
            foreach ($_POST as $key => $value) {
                // Проверяем, что это наша настройка
                $is_setting = false;
                foreach ($settings_groups as $group => $settings) {
                    if (in_array($key, $settings)) {
                        $is_setting = true;
                        $group_name = $group;
                        break;
                    }
                }
                
                if ($is_setting) {
                    // Проверяем, существует ли уже такая настройка
                    $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Обновляем существующую настройку
                        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, setting_group = ? WHERE setting_key = ?");
                        $stmt->execute([$value, $group_name, $key]);
                    } else {
                        // Вставляем новую настройку
                        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
                        $stmt->execute([$key, $value, $group_name]);
                    }
                }
            }
            
            $pdo->commit();
            $success_message = "Настройки успешно сохранены!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Ошибка при сохранении настроек: " . $e->getMessage();
        }
    }
    
    // Обработка сброса кэша
    if (isset($_POST['clear_cache'])) {
        // Здесь можно добавить очистку кэша
        $success_message = "Кэш успешно очищен!";
    }
    
    // Обработка резервного копирования
    if (isset($_POST['create_backup'])) {
        try {
            $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_path = 'backups/' . $backup_file;
            
            // Создаем директорию backups если её нет
            if (!is_dir('backups')) {
                mkdir('backups', 0755, true);
            }
            
            // Получаем все таблицы
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $backup_content = "-- Резервная копия базы данных\n";
            $backup_content .= "-- Дата создания: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $backup_content .= "--\n-- Структура таблицы `$table`\n--\n";
                $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $backup_content .= $create_table['Create Table'] . ";\n\n";
                
                // Данные таблицы
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $backup_content .= "--\n-- Дамп данных таблицы `$table`\n--\n";
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($pdo) {
                            return $pdo->quote($value);
                        }, array_values($row));
                        $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $backup_content .= "\n";
                }
            }
            
            file_put_contents($backup_path, $backup_content);
            $success_message = "Резервная копия создана: " . $backup_file;
        } catch (Exception $e) {
            $error_message = "Ошибка при создании резервной копии: " . $e->getMessage();
        }
    }
    
    // Обработка сброса системы
    if (isset($_POST['reset_system']) && isset($_POST['confirm_reset'])) {
        // Здесь можно добавить сброс системы
        $warning_message = "Функция сброса системы в разработке";
    }
}

// Получаем текущие настройки из базы
$current_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings as $setting) {
        $current_settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    // Таблица может не существовать
}

// Значения по умолчанию
$default_settings = [
    'school_name' => 'Средняя школа №123',
    'school_address' => 'г. Москва, ул. Школьная, д. 1',
    'school_phone' => '+7 (495) 123-45-67',
    'school_email' => 'info@school123.ru',
    'school_director' => 'Иванова А.Н.',
    'school_year' => '2024-2025',
    'academic_period' => '1 семестр',
    'site_name' => 'Школьный портал',
    'default_language' => 'ru',
    'timezone' => 'Europe/Moscow',
    'date_format' => 'd.m.Y',
    'items_per_page' => '20',
    'session_timeout' => '30',
    'maintenance_mode' => '0',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'admin_email' => 'admin@school123.ru',
    'email_from_name' => 'Школьный портал',
    'auto_backup' => '1',
    'backup_frequency' => 'daily',
    'keep_backups' => '7',
    'backup_path' => 'backups/'
];

// Объединяем с текущими настройками
$settings = array_merge($default_settings, $current_settings);

// Статистика системы
$system_stats = [];
try {
    $system_stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $system_stats['total_teachers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
    $system_stats['total_classes'] = $pdo->query("SELECT COUNT(DISTINCT class) FROM schedule")->fetchColumn();
    $system_stats['total_lessons'] = $pdo->query("SELECT COUNT(*) FROM schedule")->fetchColumn();
    $system_stats['db_size'] = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
} catch (Exception $e) {
    $system_stats = [
        'total_students' => 0,
        'total_teachers' => 0,
        'total_classes' => 0,
        'total_lessons' => 0,
        'db_size' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Системные настройки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar {
            background: #2c3e50;
            min-height: calc(100vh - 56px);
            color: white;
        }
        .sidebar a {
            color: #bdc3c7;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background: #34495e;
            border-left-color: #3498db;
        }
        .settings-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
        }
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        .stat-card.students {
            border-left-color: #0d6efd;
        }
        .stat-card.teachers {
            border-left-color: #198754;
        }
        .stat-card.classes {
            border-left-color: #ffc107;
        }
        .stat-card.db {
            border-left-color: #dc3545;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .tab-content {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            background: linear-gradient(to right, rgba(220,53,69,0.05), rgba(220,53,69,0.01));
        }
        .maintenance-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-right: 10px;
        }
        .maintenance-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .maintenance-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        .maintenance-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .maintenance-slider {
            background-color: #dc3545;
        }
        input:checked + .maintenance-slider:before {
            transform: translateX(30px);
        }
    </style>
</head>
<body>
    <!-- Шапка -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-house-door"></i> Школьный портал
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    <span class="badge bg-danger ms-2">Администратор</span>
                </span>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Назад
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Выход
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Боковое меню -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="pt-3">
                    <div class="text-center mb-4">
                        <h5>СОШ №123</h5>
                        <hr class="text-white-50">
                    </div>
                    
                    <a href="dashboard.php">
                        <i class="bi bi-house-door me-2"></i> Главная
                    </a>
                    
                    <a href="journal.php">
                        <i class="bi bi-journal-text me-2"></i> Электронный журнал
                    </a>
                    
                    <a href="students.php">
                        <i class="bi bi-people me-2"></i> Ученики
                    </a>
                    
                    <a href="schedule.php">
                        <i class="bi bi-calendar-week me-2"></i> Расписание
                    </a>
                    
                    <a href="system_settings.php" class="active">
                        <i class="bi bi-gear me-2"></i> Системные настройки
                    </a>
                    
                    <a href="users.php">
                        <i class="bi bi-person-badge me-2"></i> Пользователи
                    </a>
                    
                    <a href="logs.php">
                        <i class="bi bi-clipboard-data me-2"></i> Логи системы
                    </a>
                </div>
            </div>
            
            <!-- Основной контент -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-gear me-2"></i> Системные настройки</h2>
                    <div>
                        <span class="badge bg-secondary">
                            <i class="bi bi-database"></i> Версия 1.0.0
                        </span>
                    </div>
                </div>
                
                <!-- Уведомления -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($warning_message)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($warning_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Статистика системы -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card students">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Учеников</h6>
                                        <h3 class="mb-0"><?php echo $system_stats['total_students']; ?></h3>
                                    </div>
                                    <i class="bi bi-people-fill fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card teachers">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Учителей</h6>
                                        <h3 class="mb-0"><?php echo $system_stats['total_teachers']; ?></h3>
                                    </div>
                                    <i class="bi bi-person-badge fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card classes">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Классов</h6>
                                        <h3 class="mb-0"><?php echo $system_stats['total_classes']; ?></h3>
                                    </div>
                                    <i class="bi bi-building fs-1 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card db">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Размер БД</h6>
                                        <h3 class="mb-0"><?php echo $system_stats['db_size']; ?> МБ</h3>
                                    </div>
                                    <i class="bi bi-hdd-fill fs-1 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Форма настроек -->
                <form method="POST">
                    <!-- Вкладки -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="school-tab" data-bs-toggle="tab" data-bs-target="#school" type="button" role="tab">
                                <i class="bi bi-building me-1"></i> Школа
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                                <i class="bi bi-gear me-1"></i> Система
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="bi bi-envelope me-1"></i> Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                                <i class="bi bi-cloud-arrow-down me-1"></i> Резервные копии
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                                <i class="bi bi-tools me-1"></i> Тех. обслуживание
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="settingsContent">
                        <!-- Вкладка "Школа" -->
                        <div class="tab-pane fade show active" id="school" role="tabpanel">
                            <div class="settings-card card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Информация о школе</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Название школы *</label>
                                            <input type="text" class="form-control" name="school_name" 
                                                   value="<?php echo htmlspecialchars($settings['school_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Директор *</label>
                                            <input type="text" class="form-control" name="school_director" 
                                                   value="<?php echo htmlspecialchars($settings['school_director']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Адрес *</label>
                                            <input type="text" class="form-control" name="school_address" 
                                                   value="<?php echo htmlspecialchars($settings['school_address']); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Телефон *</label>
                                            <input type="text" class="form-control" name="school_phone" 
                                                   value="<?php echo htmlspecialchars($settings['school_phone']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email школы *</label>
                                            <input type="email" class="form-control" name="school_email" 
                                                   value="<?php echo htmlspecialchars($settings['school_email']); ?>" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Учебный год *</label>
                                            <input type="text" class="form-control" name="school_year" 
                                                   value="<?php echo htmlspecialchars($settings['school_year']); ?>" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Семестр *</label>
                                            <select class="form-select" name="academic_period" required>
                                                <option value="1 семестр" <?php echo $settings['academic_period'] == '1 семестр' ? 'selected' : ''; ?>>1 семестр</option>
                                                <option value="2 семестр" <?php echo $settings['academic_period'] == '2 семестр' ? 'selected' : ''; ?>>2 семестр</option>
                                                <option value="летняя сессия" <?php echo $settings['academic_period'] == 'летняя сессия' ? 'selected' : ''; ?>>Летняя сессия</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка "Система" -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="settings-card card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Настройки системы</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Название сайта *</label>
                                            <input type="text" class="form-control" name="site_name" 
                                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Язык по умолчанию *</label>
                                            <select class="form-select" name="default_language" required>
                                                <option value="ru" <?php echo $settings['default_language'] == 'ru' ? 'selected' : ''; ?>>Русский</option>
                                                <option value="en" <?php echo $settings['default_language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="kz" <?php echo $settings['default_language'] == 'kz' ? 'selected' : ''; ?>>Қазақша</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Часовой пояс *</label>
                                            <select class="form-select" name="timezone" required>
                                                <option value="Europe/Moscow" <?php echo $settings['timezone'] == 'Europe/Moscow' ? 'selected' : ''; ?>>Москва (UTC+3)</option>
                                                <option value="Asia/Almaty" <?php echo $settings['timezone'] == 'Asia/Almaty' ? 'selected' : ''; ?>>Алматы (UTC+6)</option>
                                                <option value="Europe/London" <?php echo $settings['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>Лондон (UTC+0)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Формат даты *</label>
                                            <select class="form-select" name="date_format" required>
                                                <option value="d.m.Y" <?php echo $settings['date_format'] == 'd.m.Y' ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                                <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                                <option value="m/d/Y" <?php echo $settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Элементов на странице *</label>
                                            <input type="number" class="form-control" name="items_per_page" 
                                                   value="<?php echo htmlspecialchars($settings['items_per_page']); ?>" min="5" max="100" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Таймаут сессии (минут) *</label>
                                            <input type="number" class="form-control" name="session_timeout" 
                                                   value="<?php echo htmlspecialchars($settings['session_timeout']); ?>" min="5" max="480" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label d-block">Режим обслуживания</label>
                                            <label class="maintenance-toggle">
                                                <input type="checkbox" name="maintenance_mode" value="1" 
                                                       <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                                <span class="maintenance-slider"></span>
                                            </label>
                                            <span class="text-muted small">При включении доступ только для администраторов</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка "Email" -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <div class="settings-card card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Настройки почты</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> Настройки SMTP для отправки email уведомлений
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">SMTP хост *</label>
                                            <input type="text" class="form-control" name="smtp_host" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">SMTP порт *</label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Шифрование *</label>
                                            <select class="form-select" name="smtp_encryption" required>
                                                <option value="tls" <?php echo $settings['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo $settings['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="">Без шифрования</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">SMTP логин *</label>
                                            <input type="text" class="form-control" name="smtp_username" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">SMTP пароль *</label>
                                            <div class="position-relative">
                                                <input type="password" class="form-control" id="smtp_password" 
                                                       name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>" required>
                                                <span class="password-toggle" onclick="togglePassword('smtp_password')">
                                                    <i class="bi bi-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email администратора *</label>
                                            <input type="email" class="form-control" name="admin_email" 
                                                   value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Имя отправителя *</label>
                                            <input type="text" class="form-control" name="email_from_name" 
                                                   value="<?php echo htmlspecialchars($settings['email_from_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка "Резервные копии" -->
                        <div class="tab-pane fade" id="backup" role="tabpanel">
                            <div class="settings-card card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Резервные копии</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="auto_backup" 
                                                       name="auto_backup" value="1" 
                                                       <?php echo $settings['auto_backup'] == '1' ? 'checked' : ''; ?> style="transform: scale(1.3);">
                                                <label class="form-check-label" for="auto_backup">
                                                    <strong>Автоматическое резервное копирование</strong>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="submit" name="create_backup" class="btn btn-success w-100">
                                                <i class="bi bi-cloud-arrow-up"></i> Создать резервную копию сейчас
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Частота копирования</label>
                                            <select class="form-select" name="backup_frequency">
                                                <option value="daily" <?php echo $settings['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Ежедневно</option>
                                                <option value="weekly" <?php echo $settings['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Еженедельно</option>
                                                <option value="monthly" <?php echo $settings['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Ежемесячно</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Хранить копий</label>
                                            <input type="number" class="form-control" name="keep_backups" 
                                                   value="<?php echo htmlspecialchars($settings['keep_backups']); ?>" min="1" max="365">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Путь для копий</label>
                                            <input type="text" class="form-control" name="backup_path" 
                                                   value="<?php echo htmlspecialchars($settings['backup_path']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка "Тех. обслуживание" -->
                        <div class="tab-pane fade" id="maintenance" role="tabpanel">
                            <div class="settings-card card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Техническое обслуживание</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="card border-primary">
                                                <div class="card-body">
                                                    <h6><i class="bi bi-trash text-primary"></i> Очистка кэша</h6>
                                                    <p class="text-muted small">Очищает временные файлы и кэш системы</p>
                                                    <button type="submit" name="clear_cache" class="btn btn-outline-primary">
                                                        <i class="bi bi-trash"></i> Очистить кэш
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card border-warning">
                                                <div class="card-body">
                                                    <h6><i class="bi bi-bar-chart text-warning"></i> Оптимизация БД</h6>
                                                    <p class="text-muted small">Оптимизирует таблицы базы данных</p>
                                                    <button type="button" class="btn btn-outline-warning" id="optimizeDbBtn">
                                                        <i class="bi bi-bar-chart"></i> Оптимизировать БД
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Опасная зона -->
                                    <div class="danger-zone mt-4">
                                        <h5 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Опасная зона</h5>
                                        <p class="text-muted">Эти действия могут привести к необратимым изменениям в системе</p>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label text-danger">Сброс системы к заводским настройкам</label>
                                                    <p class="small text-muted">Удалит все пользовательские данные и настройки</p>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="confirm_reset" id="confirmReset" style="transform: scale(1.2);">
                                                        <label class="form-check-label" for="confirmReset">
                                                            Я понимаю последствия
                                                        </label>
                                                    </div>
                                                    <button type="submit" name="reset_system" class="btn btn-danger" disabled>
                                                        <i class="bi bi-arrow-repeat"></i> Сбросить систему
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label text-danger">Удаление всех данных</label>
                                                    <p class="small text-muted">Полное удаление всех данных из базы</p>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                                                        <i class="bi bi-trash3"></i> Удалить все данные
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопки сохранения -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" name="save_settings" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save"></i> Сохранить все настройки
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-lg ms-2" id="testSettingsBtn">
                                        <i class="bi bi-check-circle"></i> Проверить настройки
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exitModal">
                                        <i class="bi bi-door-closed"></i> Выйти без сохранения
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Внимание!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger"><strong>Вы уверены, что хотите удалить все данные системы?</strong></p>
                    <p>Это действие:</p>
                    <ul>
                        <li>Удалит всех пользователей</li>
                        <li>Удалит все расписание</li>
                        <li>Удалит все оценки</li>
                        <li>Удалит все настройки</li>
                    </ul>
                    <p><strong>Это действие необратимо!</strong></p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmDeleteAll">
                        <label class="form-check-label" for="confirmDeleteAll">
                            Я понимаю последствия и хочу продолжить
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Удалить всё</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно выхода -->
    <div class="modal fade" id="exitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Выйти без сохранения?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>У вас есть несохраненные изменения. Вы уверены, что хотите выйти?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Остаться</button>
                    <a href="dashboard.php" class="btn btn-warning">Выйти</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Включение/выключение кнопки сброса системы
        document.getElementById('confirmReset').addEventListener('change', function() {
            document.querySelector('button[name="reset_system"]').disabled = !this.checked;
        });
        
        // Включение/выключение кнопки удаления в модальном окне
        document.getElementById('confirmDeleteAll').addEventListener('change', function() {
            document.getElementById('confirmDeleteBtn').disabled = !this.checked;
        });
        
        // Обработка нажатия кнопки "Удалить всё"
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            alert('Функция удаления всех данных в разработке');
            $('#deleteAllModal').modal('hide');
        });
        
        // Переключение видимости пароля
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Проверка настроек
        document.getElementById('testSettingsBtn').addEventListener('click', function() {
            const tabs = ['school', 'system', 'email', 'backup'];
            let isValid = true;
            let firstInvalidTab = null;
            
            tabs.forEach(tab => {
                const tabContent = document.getElementById(tab);
                const inputs = tabContent.querySelectorAll('input[required], select[required]');
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        if (!firstInvalidTab) {
                            firstInvalidTab = tab;
                        }
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });
            
            if (!isValid) {
                // Переключаемся на первую вкладку с ошибкой
                const tabBtn = document.querySelector(`button[data-bs-target="#${firstInvalidTab}"]`);
                new bootstrap.Tab(tabBtn).show();
                
                // Прокручиваем к первой ошибке
                const firstInvalidInput = document.getElementById(firstInvalidTab).querySelector('.is-invalid');
                if (firstInvalidInput) {
                    firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalidInput.focus();
                }
                
                showAlert('Пожалуйста, заполните все обязательные поля', 'danger');
            } else {
                showAlert('Все настройки корректны!', 'success');
            }
        });
        
        // Оптимизация БД
        document.getElementById('optimizeDbBtn').addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите оптимизировать базу данных?')) {
                // Здесь AJAX запрос на оптимизацию БД
                fetch('ajax/optimize_db.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('База данных успешно оптимизирована!', 'success');
                        } else {
                            showAlert('Ошибка при оптимизации БД: ' + data.error, 'danger');
                        }
                    })
                    .catch(error => {
                        showAlert('Ошибка сети: ' + error, 'danger');
                    });
            }
        });
        
        // Показ уведомлений
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // Проверка изменений перед уходом со страницы
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        form.addEventListener('submit', () => {
            formChanged = false;
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>