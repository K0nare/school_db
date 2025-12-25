-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Дек 26 2025 г., 00:06
-- Версия сервера: 5.5.23
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `school_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `reason` text,
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `room` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher_id`, `year`, `room`) VALUES
(1, '10-А', NULL, 2023, '201'),
(2, '10-Б', NULL, 2023, '202'),
(3, '11-А', NULL, 2023, '301');

-- --------------------------------------------------------

--
-- Структура таблицы `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `grade` int(11) NOT NULL,
  `date` date NOT NULL,
  `comment` text,
  `teacher` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject`, `grade`, `date`, `comment`, `teacher`, `created_at`) VALUES
(1, 1, 'Математика', 5, '2024-12-01', 'Отлично ответил у доски', 'Иванова М.П.', '2025-12-25 21:57:31'),
(2, 1, 'Математика', 4, '2024-12-05', 'Контрольная работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(3, 1, 'Русский язык', 4, '2024-12-02', 'Диктант', 'Петрова С.И.', '2025-12-25 21:57:31'),
(4, 1, 'Физика', 5, '2024-12-03', 'Лабораторная работа', 'Сидоров А.В.', '2025-12-25 21:57:31'),
(5, 2, 'Математика', 3, '2024-12-01', 'Домашняя работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(6, 2, 'Математика', 5, '2024-12-08', 'Самостоятельная работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(7, 2, 'История', 4, '2024-12-04', 'Тест', 'Кузнецова О.В.', '2025-12-25 21:57:31'),
(8, 3, 'Математика', 4, '2024-12-01', 'Ответ на уроке', 'Иванова М.П.', '2025-12-25 21:57:31'),
(9, 3, 'Математика', 4, '2024-12-06', 'Тестирование', 'Иванова М.П.', '2025-12-25 21:57:31'),
(10, 3, 'Химия', 5, '2024-12-05', 'Эксперимент', 'Михайлов Д.С.', '2025-12-25 21:57:31'),
(11, 4, 'Математика', 5, '2024-12-02', 'Проектная работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(12, 4, 'Биология', 4, '2024-12-03', 'Практикум', 'Фёдорова Е.М.', '2025-12-25 21:57:31'),
(13, 5, 'Математика', 3, '2024-12-03', 'Практическая работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(14, 5, 'Английский язык', 4, '2024-12-04', 'Диалог', 'Смирнова Е.А.', '2025-12-25 21:57:31'),
(15, 6, 'Математика', 5, '2024-12-01', 'Контрольная', 'Иванова М.П.', '2025-12-25 21:57:31'),
(16, 7, 'Математика', 4, '2024-12-02', 'Работа у доски', 'Иванова М.П.', '2025-12-25 21:57:31'),
(17, 8, 'Математика', 5, '2024-12-03', 'Домашняя работа', 'Иванова М.П.', '2025-12-25 21:57:31'),
(18, 9, 'Математика', 3, '2024-12-04', 'Тестирование', 'Иванова М.П.', '2025-12-25 21:57:31'),
(19, 10, 'Математика', 5, '2024-12-05', 'Самостоятельная', 'Иванова М.П.', '2025-12-25 21:57:31');

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `time` time NOT NULL,
  `room` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `class` varchar(20) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `students`
--

INSERT INTO `students` (`id`, `full_name`, `class`, `birth_date`, `phone`, `email`, `parent_name`, `parent_phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Иванов Александр Сергеевич', '10-А', '2007-05-15', '+7 (999) 123-45-67', 'ivanov@school.ru', 'Иванова Мария Петровна', '+7 (999) 987-65-43', 'ул. Ленина, д. 10, кв. 5', '2025-12-25 21:46:49', NULL),
(2, 'Петрова Мария Дмитриевна', '10-А', '2007-08-22', '+7 (999) 234-56-78', 'petrova@school.ru', 'Петров Дмитрий Иванович', '+7 (999) 876-54-32', 'ул. Центральная, д. 25, кв. 12', '2025-12-25 21:46:49', NULL),
(3, 'Сидоров Дмитрий Владимирович', '10-Б', '2007-03-10', '+7 (999) 345-67-89', 'sidorov@school.ru', 'Сидорова Ольга Николаевна', '+7 (999) 765-43-21', 'ул. Школьная, д. 3, кв. 7', '2025-12-25 21:46:49', NULL),
(4, 'Козлова Анна Андреевна', '10-А', '2007-11-05', '+7 (999) 456-78-90', 'kozlova@school.ru', 'Козлов Андрей Сергеевич', '+7 (999) 654-32-10', 'ул. Пушкина, д. 15, кв. 9', '2025-12-25 21:46:49', NULL),
(5, 'Николаев Владимир Петрович', '10-Б', '2007-02-18', '+7 (999) 567-89-01', 'nikolaev@school.ru', 'Николаева Елена Викторовна', '+7 (999) 543-21-09', 'ул. Гагарина, д. 8, кв. 3', '2025-12-25 21:46:49', NULL),
(6, 'Смирнова Екатерина Олеговна', '11-А', '2006-07-30', '+7 (999) 678-90-12', 'smirnova@school.ru', 'Смирнов Олег Васильевич', '+7 (999) 432-10-98', 'ул. Мира, д. 12, кв. 6', '2025-12-25 21:46:49', NULL),
(7, 'Фёдоров Артём Игоревич', '11-Б', '2006-04-12', '+7 (999) 789-01-23', 'fedorov@school.ru', 'Фёдорова Светлана Александровна', '+7 (999) 321-09-87', 'ул. Садовая, д. 7, кв. 11', '2025-12-25 21:46:49', NULL),
(8, 'Алексеева София Максимовна', '10-А', '2007-09-25', '+7 (999) 890-12-34', 'alekseeva@school.ru', 'Алексеев Максим Викторович', '+7 (999) 210-98-76', 'ул. Лесная, д. 5, кв. 8', '2025-12-25 21:46:49', NULL),
(9, 'Григорьев Михаил Алексеевич', '10-Б', '2007-01-14', '+7 (999) 901-23-45', 'grigoriev@school.ru', 'Григорьева Татьяна Сергеевна', '+7 (999) 109-87-65', 'ул. Весенняя, д. 9, кв. 4', '2025-12-25 21:46:49', NULL),
(10, 'Дмитриева Виктория Романовна', '11-А', '2006-12-03', '+7 (999) 012-34-56', 'dmitrieva@school.ru', 'Дмитриев Роман Андреевич', '+7 (999) 098-76-54', 'ул. Осенняя, д. 11, кв. 2', '2025-12-25 21:46:49', NULL);

--
-- Триггеры `students`
--
DELIMITER $$
CREATE TRIGGER `update_students_timestamp` BEFORE UPDATE ON `students` FOR EACH ROW SET NEW.updated_at = NOW()
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `description`) VALUES
(1, 'Математика', NULL),
(2, 'Русский язык', NULL),
(3, 'Физика', NULL),
(4, 'История', NULL),
(5, 'Химия', NULL),
(6, 'Биология', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`, `email`, `phone`, `created_at`, `last_login`, `is_active`) VALUES
(7, 'admin', 'admin123', 'Администратор Системы', 'admin', 'admin@school.ru', NULL, '2025-12-25 21:07:08', NULL, 1),
(8, 'teacher', 'teacher123', 'Иванова Мария Петровна', 'teacher', 'teacher@school.ru', NULL, '2025-12-25 21:07:08', NULL, 1),
(9, 'student', 'student123', 'Петров Иван Сергеевич', 'student', 'student@school.ru', NULL, '2025-12-25 21:07:08', NULL, 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Индексы таблицы `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_date` (`date`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Индексы таблицы `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
