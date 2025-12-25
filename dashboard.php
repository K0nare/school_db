<?php
// Подключаем проверку авторизации
require_once 'auth.php';
checkAuth();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        .settings-sidebar {
            background: white;
            min-height: calc(100vh - 56px);
            border-left: 1px solid #dee2e6;
            box-shadow: -2px 0 10px rgba(0,0,0,0.05);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .quick-action-btn {
            transition: all 0.2s;
        }
        .quick-action-btn:hover {
            transform: translateX(5px);
        }
        .lesson-item {
            border-left: 4px solid #3498db;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .news-alert {
            border-left: 4px solid #17a2b8;
        }
        .settings-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .settings-section:last-child {
            border-bottom: none;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #28a745;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
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
                    <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </span>
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
                    
                    <a href="dashboard.php" class="active">
                        <i class="bi bi-house-door me-2"></i> Главная
                    </a>
                    
                    <?php if (isTeacher()): ?>
                    <a href="journal.php">
                        <i class="bi bi-journal-text me-2"></i> Электронный журнал
                    </a>
                    <a href="students.php">
                        <i class="bi bi-people me-2"></i> Ученики
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isStudent()): ?>
                    <a href="journal.php">
                        <i class="bi bi-journal-bookmark me-2"></i> Мои оценки
                    </a>
                    <?php endif; ?>
                    
                    <a href="schedule.php">
                        <i class="bi bi-calendar-week me-2"></i> Расписание
                    </a>
                    
                    <?php if (isAdmin()): ?>
                    <a href="system_settings.php">
                        <i class="bi bi-gear me-2"></i> Системные настройки
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Основной контент -->
            <main class="col-md-6 col-lg-7 px-md-4 pt-3">
                <h2>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-muted">
                    <i class="bi bi-calendar"></i> Сегодня: <?php echo date('d.m.Y'); ?>
                </p>
                
                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Всего учеников</h5>
                                <h2 class="mb-0">850</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Средний балл</h5>
                                <h2 class="mb-0">4.2</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Учителей</h5>
                                <h2 class="mb-0">45</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Классов</h5>
                                <h2 class="mb-0">28</h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Контент для разных ролей -->
                <?php if (isTeacher()): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Быстрые действия</h5>
                            </div>
                            <div class="card-body">
                                <a href="journal.php" class="btn btn-primary mb-2 w-100 quick-action-btn d-flex align-items-center">
                                    <i class="bi bi-journal-text me-2"></i> Открыть журнал
                                    <i class="bi bi-arrow-right ms-auto"></i>
                                </a>
                                <a href="students.php" class="btn btn-success mb-2 w-100 quick-action-btn d-flex align-items-center">
                                    <i class="bi bi-people me-2"></i> Список учеников
                                    <i class="bi bi-arrow-right ms-auto"></i>
                                </a>
                                <a href="schedule.php" class="btn btn-info w-100 quick-action-btn d-flex align-items-center">
                                    <i class="bi bi-calendar-week me-2"></i> Расписание
                                    <i class="bi bi-arrow-right ms-auto"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Ближайшие уроки</h5>
                            </div>
                            <div class="card-body">
                                <div class="lesson-item">
                                    <strong class="text-primary">08:30</strong> - Математика (10-А класс)
                                </div>
                                <div class="lesson-item">
                                    <strong class="text-primary">10:25</strong> - Алгебра (11-Б класс)
                                </div>
                                <div class="lesson-item">
                                    <strong class="text-primary">12:20</strong> - Геометрия (9-А класс)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (isStudent()): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Расписание на сегодня</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Время</th>
                                            <th>Предмет</th>
                                            <th>Кабинет</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>08:30-09:15</td>
                                            <td><strong>Математика</strong></td>
                                            <td>302</td>
                                        </tr>
                                        <tr>
                                            <td>09:25-10:10</td>
                                            <td><strong>Русский язык</strong></td>
                                            <td>205</td>
                                        </tr>
                                        <tr>
                                            <td>10:25-11:10</td>
                                            <td><strong>Физика</strong></td>
                                            <td>404</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Мои оценки</h5>
                            </div>
                            <div class="card-body text-center">
                                <h1 class="text-primary">4.5</h1>
                                <p class="text-muted">Средний балл</p>
                                <a href="journal.php" class="btn btn-primary">Подробнее</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Новости -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Школьные новости</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info news-alert">
                                    <h6><i class="bi bi-megaphone"></i> Родительское собрание</h6>
                                    <p class="mb-0">Родительское собрание для 10-х классов состоится 15 декабря.</p>
                                </div>
                                <div class="alert alert-success news-alert">
                                    <h6><i class="bi bi-trophy"></i> Победа в олимпиаде</h6>
                                    <p class="mb-0">Ученик 11-Б класса занял 1 место в городской олимпиаде.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Правая панель настроек -->
            <aside class="col-md-3 col-lg-3 settings-sidebar p-4">
                <h4 class="mb-4">Настройки</h4>
                
                <!-- Настройки темы -->
                <div class="settings-section">
                    <h6><i class="bi bi-palette me-2"></i>Внешний вид</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" style="transform: scale(1.2);">
                        <label class="form-check-label" for="darkModeSwitch">Темная тема</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Размер шрифта</label>
                        <select class="form-select form-select-sm" id="fontSizeSelect">
                            <option value="small">Мелкий</option>
                            <option value="medium" selected>Средний</option>
                            <option value="large">Крупный</option>
                        </select>
                    </div>
                </div>
                
                <!-- Настройки уведомлений -->
                <div class="settings-section">
                    <h6><i class="bi bi-bell me-2"></i>Уведомления</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="emailNotifications">Email уведомления</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="lessonReminders" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="lessonReminders">Напоминания об уроках</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="gradeAlerts" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="gradeAlerts">Оповещения об оценках</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsNotifications" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="newsNotifications">Новости школы</label>
                    </div>
                </div>
                
                <!-- Настройки языка -->
                <div class="settings-section">
                    <h6><i class="bi bi-translate me-2"></i>Язык</h6>
                    <div class="mb-3">
                        <select class="form-select form-select-sm" id="languageSelect">
                            <option value="ru" selected>Русский</option>
                            <option value="en">English</option>
                            <option value="kz">Қазақша</option>
                        </select>
                    </div>
                </div>
                
                <!-- Дополнительные настройки -->
                <div class="settings-section">
                    <h6><i class="bi bi-gear me-2"></i>Дополнительно</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="autoSave" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="autoSave">Автосохранение</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="twoFactorAuth" style="transform: scale(1.1);">
                        <label class="form-check-label small" for="twoFactorAuth">Двухфакторная аутентификация</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showOnlineStatus" checked style="transform: scale(1.1);">
                        <label class="form-check-label small" for="showOnlineStatus">Показывать статус онлайн</label>
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="settings-section">
                    <button class="btn btn-sm btn-primary w-100 mb-2" id="saveSettingsBtn">
                        <i class="bi bi-save me-1"></i> Сохранить настройки
                    </button>
                    <button class="btn btn-sm btn-outline-secondary w-100 mb-2" id="resetSettingsBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Сбросить к стандартным
                    </button>
                    <a href="profile.php" class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-person-circle me-1"></i> Настройки профиля
                    </a>
                </div>
                
                <!-- Статус системы -->
                <div class="settings-section">
                    <h6><i class="bi bi-server me-2"></i>Статус системы</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Память сервера:</span>
                        <span class="small text-success">65%</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 65%"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">База данных:</span>
                        <span class="small text-success">Работает</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">Последнее обновление:</span>
                        <span class="small text-muted">Сегодня 08:15</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Функционал для сохранения настроек
        document.getElementById('saveSettingsBtn').addEventListener('click', function() {
            const darkMode = document.getElementById('darkModeSwitch').checked;
            const fontSize = document.getElementById('fontSizeSelect').value;
            const language = document.getElementById('languageSelect').value;
            
            // Сохраняем в localStorage (в реальном приложении отправляли бы на сервер)
            localStorage.setItem('darkMode', darkMode);
            localStorage.setItem('fontSize', fontSize);
            localStorage.setItem('language', language);
            
            // Применяем настройки
            applySettings();
            
            // Показываем уведомление
            showNotification('Настройки успешно сохранены!', 'success');
        });
        
        // Функционал для сброса настроек
        document.getElementById('resetSettingsBtn').addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите сбросить все настройки к стандартным?')) {
                localStorage.clear();
                location.reload();
            }
        });
        
        // Применение сохраненных настроек при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            applySettings();
            
            // Восстанавливаем состояние переключателей из localStorage
            const darkMode = localStorage.getItem('darkMode') === 'true';
            const fontSize = localStorage.getItem('fontSize') || 'medium';
            const language = localStorage.getItem('language') || 'ru';
            
            document.getElementById('darkModeSwitch').checked = darkMode;
            document.getElementById('fontSizeSelect').value = fontSize;
            document.getElementById('languageSelect').value = language;
        });
        
        function applySettings() {
            // Применение темной темы
            if (document.getElementById('darkModeSwitch').checked) {
                document.body.classList.add('bg-dark', 'text-light');
                document.querySelector('.settings-sidebar').classList.add('bg-dark', 'text-light');
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.add('bg-dark', 'text-light');
                });
            } else {
                document.body.classList.remove('bg-dark', 'text-light');
                document.querySelector('.settings-sidebar').classList.remove('bg-dark', 'text-light');
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('bg-dark', 'text-light');
                });
            }
            
            // Применение размера шрифта
            const fontSize = document.getElementById('fontSizeSelect').value;
            document.body.style.fontSize = fontSize === 'small' ? '14px' : fontSize === 'large' ? '18px' : '16px';
        }
        
        function showNotification(message, type) {
            // Создаем уведомление
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Автоматически скрываем через 3 секунды
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 3000);
        }
        
        // Обработчики для переключателей уведомлений
        document.querySelectorAll('#emailNotifications, #lessonReminders, #gradeAlerts, #newsNotifications').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const settings = {
                    email: document.getElementById('emailNotifications').checked,
                    lessons: document.getElementById('lessonReminders').checked,
                    grades: document.getElementById('gradeAlerts').checked,
                    news: document.getElementById('newsNotifications').checked
                };
                localStorage.setItem('notificationSettings', JSON.stringify(settings));
                showNotification('Настройки уведомлений обновлены', 'info');
            });
        });
    </script>
</body>
</html>