<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'database.php';
    $pdo = Database::getConnection();
    
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    // Ищем пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ПРОСТАЯ ПРОВЕРКА - сравниваем пароли напрямую
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 400px;
        }
        .school-logo {
            font-size: 60px;
            color: #4a6fa5;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="school-logo">🏫</div>
        <h3 class="text-center mb-4">СОШ №123</h3>
        <p class="text-center text-muted mb-4">Панель управления учебным процессом</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Логин</label>
                <input type="text" name="login" class="form-control" placeholder="Введите логин" required 
                       value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" placeholder="Введите пароль" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Войти</button>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <strong>Тестовые доступы:</strong><br>
                    Админ: <code>admin</code> / <code>admin123</code><br>
                    Учитель: <code>teacher</code> / <code>teacher123</code><br>
                    Ученик: <code>student</code> / <code>student123</code>
                </small>
            </div>
        </form>
    </div>
</body>
</html>