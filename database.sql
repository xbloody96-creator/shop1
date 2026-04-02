-- ============================================
-- МАГАЗИН — Database Schema
-- Import via phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS `shop_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `shop_db`;

-- --------------------------------------------
-- Пользователи
-- --------------------------------------------
CREATE TABLE `users` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `email`        VARCHAR(255) NOT NULL UNIQUE,
  `login`        VARCHAR(100) NOT NULL UNIQUE,
  `full_name`    VARCHAR(255) NOT NULL,
  `nickname`     VARCHAR(100) NOT NULL UNIQUE,
  `birthdate`    DATE NOT NULL,
  `gender`       ENUM('male','female','other') NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `avatar`       VARCHAR(255) DEFAULT 'default_avatar.png',
  `role`         ENUM('user','admin') DEFAULT 'user',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- Сессии пользователей
-- --------------------------------------------
CREATE TABLE `user_sessions` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `session_token` VARCHAR(255) NOT NULL,
  `ip_address`   VARCHAR(45),
  `user_agent`   TEXT,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_active`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Категории товаров
-- --------------------------------------------
CREATE TABLE `categories` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(255) NOT NULL,
  `slug`         VARCHAR(255) NOT NULL UNIQUE,
  `parent_id`    INT DEFAULT NULL,
  `image`        VARCHAR(255) DEFAULT NULL,
  `sort_order`   INT DEFAULT 0,
  FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------
-- Товары
-- --------------------------------------------
CREATE TABLE `products` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(255) NOT NULL,
  `slug`         VARCHAR(255) NOT NULL UNIQUE,
  `description`  TEXT,
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `old_price`    DECIMAL(10,2) DEFAULT NULL,
  `stock`        INT NOT NULL DEFAULT 0,
  `category_id`  INT DEFAULT NULL,
  `main_image`   VARCHAR(255) DEFAULT NULL,
  `is_popular`   TINYINT(1) DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------
-- Изображения товаров (галерея)
-- --------------------------------------------
CREATE TABLE `product_images` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `product_id`   INT NOT NULL,
  `image`        VARCHAR(255) NOT NULL,
  `sort_order`   INT DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Характеристики товаров
-- --------------------------------------------
CREATE TABLE `product_specs` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `product_id`   INT NOT NULL,
  `spec_key`     VARCHAR(255) NOT NULL,
  `spec_value`   VARCHAR(255) NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Акции / Промо-блоки
-- --------------------------------------------
CREATE TABLE `promotions` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `title`        VARCHAR(255) NOT NULL,
  `description`  TEXT,
  `image`        VARCHAR(255) DEFAULT NULL,
  `discount_pct` INT DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- Новости
-- --------------------------------------------
CREATE TABLE `news` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `title`        VARCHAR(255) NOT NULL,
  `slug`         VARCHAR(255) NOT NULL UNIQUE,
  `content`      TEXT,
  `image`        VARCHAR(255) DEFAULT NULL,
  `rating`       INT DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- Отзывы
-- --------------------------------------------
CREATE TABLE `reviews` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `product_id`   INT NOT NULL,
  `user_id`      INT NOT NULL,
  `rating`       TINYINT NOT NULL DEFAULT 5,
  `text`         TEXT,
  `is_approved`  TINYINT(1) DEFAULT 0,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Корзина
-- --------------------------------------------
CREATE TABLE `cart` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `product_id`   INT NOT NULL,
  `quantity`     INT NOT NULL DEFAULT 1,
  `added_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Избранное
-- --------------------------------------------
CREATE TABLE `favorites` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `product_id`   INT NOT NULL,
  `added_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Заказы
-- --------------------------------------------
CREATE TABLE `orders` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `full_name`    VARCHAR(255) NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `phone`        VARCHAR(50),
  `address`      TEXT,
  `payment_method` ENUM('card','cash','online') DEFAULT 'card',
  `status`       ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Состав заказов
-- --------------------------------------------
CREATE TABLE `order_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`     INT NOT NULL,
  `product_id`   INT NOT NULL,
  `quantity`     INT NOT NULL DEFAULT 1,
  `price`        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- Настройки магазина
-- --------------------------------------------
CREATE TABLE `settings` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`  VARCHAR(255) NOT NULL UNIQUE,
  `setting_value` TEXT
) ENGINE=InnoDB;

-- --------------------------------------------
-- История просмотров
-- --------------------------------------------
CREATE TABLE `view_history` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `product_id`   INT NOT NULL,
  `viewed_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Начальные данные
-- ============================================

-- Настройки по умолчанию
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Магазин'),
('site_description', 'Лучший онлайн-магазин электроники'),
('contact_phone', '+7 (800) 555-35-35'),
('contact_email', 'info@magazin.ru'),
('contact_address', 'г. Москва, ул. Примерная, д. 1'),
('social_vk', 'https://vk.com/magazin'),
('social_tg', 'https://t.me/magazin'),
('map_lat', '55.7558'),
('map_lng', '37.6173');

-- Администратор (пароль: admin123)
INSERT INTO `users` (`email`, `login`, `full_name`, `nickname`, `birthdate`, `gender`, `password`, `role`) VALUES
('admin@magazin.ru', 'admin', 'Администратор Сайта', 'admin', '1990-01-01', 'male', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Категории
INSERT INTO `categories` (`name`, `slug`, `parent_id`, `sort_order`) VALUES
('Смартфоны', 'smartfony', NULL, 1),
('Ноутбуки', 'noutbuki', NULL, 2),
('Телевизоры', 'televizory', NULL, 3),
('Аудиотехника', 'audiotehnika', NULL, 4),
('Аксессуары', 'aksessuary', NULL, 5);

-- Тестовые товары
INSERT INTO `products` (`name`, `slug`, `description`, `price`, `old_price`, `stock`, `category_id`, `is_popular`, `is_active`) VALUES
('Samsung Galaxy S24', 'samsung-galaxy-s24', 'Флагманский смартфон Samsung с камерой 200 МП и процессором Snapdragon 8 Gen 3.', 79999.00, 89999.00, 15, 1, 1, 1),
('Apple iPhone 15', 'apple-iphone-15', 'Смартфон Apple с чипом A16 Bionic и динамическим островом.', 89999.00, NULL, 8, 1, 1, 1),
('ASUS ROG Zephyrus G14', 'asus-rog-zephyrus-g14', 'Игровой ноутбук с AMD Ryzen 9 и RTX 4060.', 129999.00, 149999.00, 5, 2, 1, 1),
('LG OLED 55"', 'lg-oled-55', 'OLED телевизор с разрешением 4K и частотой 120 Гц.', 89999.00, NULL, 3, 3, 1, 1),
('Sony WH-1000XM5', 'sony-wh-1000xm5', 'Беспроводные наушники с лучшим шумоподавлением в классе.', 29999.00, 34999.00, 20, 4, 1, 1);

-- Акция
INSERT INTO `promotions` (`title`, `description`, `discount_pct`, `is_active`) VALUES
('Летняя распродажа', 'Скидки до 30% на весь ассортимент электроники!', 30, 1),
('Новинки сезона', 'Первыми получи последние модели смартфонов.', 0, 1);

-- Новости
INSERT INTO `news` (`title`, `slug`, `content`, `is_active`) VALUES
('Магазин открылся!', 'magazin-otkrylsya', 'Рады приветствовать вас в нашем новом онлайн-магазине. У нас вы найдёте лучшую электронику по лучшим ценам.', 1),
('Новые поступления iPhone 15', 'novye-postupleniya-iphone-15', 'В продажу поступили новые смартфоны Apple iPhone 15 серии. Успейте заказать!', 1);
