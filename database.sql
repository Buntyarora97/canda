-- ============================================================
-- GIO MOBILITY CANADA — database schema + demo seed data
-- Target: MySQL 8+ / MariaDB 10.4+   (utf8mb4)
--
-- Install:
--   1) Create a database + user in cPanel (MySQL Databases).
--   2) Import this file via phpMyAdmin.
--   3) Edit includes/config.php (or better: /home/USER/gio-config.php).
--
-- Demo admin login:  admin@giomobility.ca  /  GioDemo!2026
-- !!! CHANGE THIS PASSWORD IMMEDIATELY after first login (Admin → Settings).
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-08:00';

CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(80) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','staff') NOT NULL DEFAULT 'admin',
  last_login_at   DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(120) NOT NULL,
  slug        VARCHAR(140) NOT NULL UNIQUE,
  description TEXT,
  menu_label  VARCHAR(140) NULL,
  image       VARCHAR(190) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku                VARCHAR(60) NOT NULL,
  slug               VARCHAR(160) NOT NULL UNIQUE,
  name               VARCHAR(190) NOT NULL,
  tagline            VARCHAR(255) NULL,
  short_description  TEXT,
  long_description   MEDIUMTEXT,
  price              DECIMAL(10,2) NULL,
  compare_price      DECIMAL(10,2) NULL,
  show_price         TINYINT(1) NOT NULL DEFAULT 1,
  wheel_config       ENUM('3-wheel','4-wheel','enclosed','walker','part') NOT NULL DEFAULT '4-wheel',
  stock_status       ENUM('in_stock','pre_order','limited','coming_soon','out_of_stock') NOT NULL DEFAULT 'in_stock',
  availability_text  VARCHAR(190) NULL,
  badge_override     VARCHAR(30) NULL,
  is_featured        TINYINT(1) NOT NULL DEFAULT 0,
  is_best_seller     TINYINT(1) NOT NULL DEFAULT 0,
  is_new_arrival     TINYINT(1) NOT NULL DEFAULT 0,
  is_published       TINYINT(1) NOT NULL DEFAULT 1,
  sort_order         INT NOT NULL DEFAULT 0,
  range_km           INT NULL,
  top_speed_kmh      INT NULL,
  capacity_kg        INT NULL,
  keywords           VARCHAR(255) NULL,
  seo_title          VARCHAR(190) NULL,
  seo_description    VARCHAR(320) NULL,
  views              INT UNSIGNED NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_published (is_published, sort_order),
  INDEX idx_flags (is_best_seller, is_new_arrival, is_featured),
  INDEX idx_stock (stock_status),
  INDEX idx_wheel (wheel_config),
  FULLTEXT KEY ft_search (name, sku, tagline, short_description, keywords)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  file        VARCHAR(190) NOT NULL,
  alt         VARCHAR(190) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_product (product_id, sort_order),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_variants (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  type        ENUM('colour','option') NOT NULL DEFAULT 'colour',
  name        VARCHAR(90) NOT NULL,
  hex         VARCHAR(9) NULL,
  sku_suffix  VARCHAR(30) NULL,
  price       DECIMAL(10,2) NULL,
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  INDEX idx_product (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_specs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  spec_group  VARCHAR(60) NOT NULL DEFAULT 'Specifications',
  spec_name   VARCHAR(120) NOT NULL,
  spec_value  VARCHAR(255) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  INDEX idx_product (product_id, spec_group, sort_order),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_features (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  feature     VARCHAR(255) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  INDEX idx_product (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_videos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  provider    ENUM('youtube','vimeo') NOT NULL DEFAULT 'youtube',
  video_url   VARCHAR(255) NOT NULL,
  video_id    VARCHAR(40) NOT NULL,
  title       VARCHAR(190) NULL,
  poster      VARCHAR(190) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_categories (
  product_id  INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, category_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_related (
  product_id          INT UNSIGNED NOT NULL,
  related_product_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, related_product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_faqs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  question    VARCHAR(255) NOT NULL,
  answer      TEXT NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enquiries (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference             VARCHAR(20) NOT NULL UNIQUE,
  product_id            INT UNSIGNED NULL,
  product_name          VARCHAR(190) NULL,
  product_sku           VARCHAR(60) NULL,
  variant               VARCHAR(90) NULL,
  colour                VARCHAR(90) NULL,
  price_shown           DECIMAL(10,2) NULL,
  page_url              VARCHAR(255) NULL,
  first_name            VARCHAR(60) NOT NULL,
  last_name             VARCHAR(60) NULL,
  email                 VARCHAR(120) NOT NULL,
  phone                 VARCHAR(30) NOT NULL,
  province              VARCHAR(2) NOT NULL,
  city                  VARCHAR(80) NOT NULL,
  postal_code           VARCHAR(7) NULL,
  preferred_contact     ENUM('Phone','Email') NOT NULL DEFAULT 'Email',
  message               TEXT NULL,
  consent               TINYINT(1) NOT NULL DEFAULT 0,
  utm_source            VARCHAR(120) NULL,
  utm_medium            VARCHAR(120) NULL,
  utm_campaign          VARCHAR(120) NULL,
  referrer              VARCHAR(255) NULL,
  ip_hash               CHAR(64) NULL,
  status                ENUM('New','Contact Attempted','Contacted','Quote Sent','Follow-Up','Converted','Closed','Spam') NOT NULL DEFAULT 'New',
  email_delivery_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  ack_delivery_status   ENUM('pending','sent','failed','disabled') NOT NULL DEFAULT 'pending',
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status, created_at),
  INDEX idx_product (product_id),
  INDEX idx_email (email),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enquiry_notes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enquiry_id  INT UNSIGNED NOT NULL,
  admin_id    INT UNSIGNED NULL,
  note        TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banners (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  eyebrow         VARCHAR(120) NULL,
  headline        VARCHAR(190) NOT NULL,
  subheading      VARCHAR(255) NULL,
  cta1_text       VARCHAR(60) NULL,
  cta1_url        VARCHAR(255) NULL,
  cta2_text       VARCHAR(60) NULL,
  cta2_url        VARCHAR(255) NULL,
  desktop_image   VARCHAR(190) NOT NULL,
  mobile_image    VARCHAR(190) NULL,
  text_alignment  ENUM('left','center') NOT NULL DEFAULT 'left',
  overlay_opacity TINYINT NOT NULL DEFAULT 60,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  schedule_start  DATETIME NULL,
  schedule_end    DATETIME NULL,
  sort_order      INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(90) NOT NULL,
  rating        TINYINT NOT NULL DEFAULT 5,
  review        TEXT NOT NULL,
  product_id    INT UNSIGNED NULL,
  photo         VARCHAR(190) NULL,
  video_url     VARCHAR(255) NULL,
  source        VARCHAR(90) NULL,
  source_url    VARCHAR(255) NULL,
  is_published  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(190) NOT NULL,
  slug            VARCHAR(190) NOT NULL UNIQUE,
  excerpt         VARCHAR(320) NULL,
  content         MEDIUMTEXT,
  featured_image  VARCHAR(190) NULL,
  seo_title       VARCHAR(190) NULL,
  seo_description VARCHAR(320) NULL,
  is_published    TINYINT(1) NOT NULL DEFAULT 1,
  published_at    DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pub (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_categories (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(90) NOT NULL,
  slug  VARCHAR(90) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_category_map (
  post_id     INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, category_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES post_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS faqs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category     VARCHAR(60) NOT NULL DEFAULT 'General',
  question     VARCHAR(255) NOT NULL,
  answer       TEXT NOT NULL,
  product_id   INT UNSIGNED NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_cat (category, sort_order),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS manuals (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id   INT UNSIGNED NULL,
  title        VARCHAR(190) NOT NULL,
  language     VARCHAR(30) NOT NULL DEFAULT 'English',
  version      VARCHAR(30) NULL,
  file         VARCHAR(190) NOT NULL,
  published_at DATE NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS seo_meta (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key    VARCHAR(90) NOT NULL UNIQUE,
  title       VARCHAR(190) NULL,
  description VARCHAR(320) NULL,
  og_image    VARCHAR(255) NULL,
  canonical   VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NULL,
  action      VARCHAR(80) NOT NULL,
  details     VARCHAR(500) NULL,
  ip_hash     CHAR(64) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action      VARCHAR(40) NOT NULL,
  ip_hash     CHAR(64) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lookup (action, ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(190) NOT NULL UNIQUE,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'GIO Mobility Canada'),
('announcement_text', 'Designed for Canadian Mobility • Product Support Available'),
('store_phone', '1-855-907-4211'),
('store_email', 'support@gioelectric.zendesk.com'),
('store_address', 'Unit 1 - 11400 Twigg Place, Richmond, BC'),
('store_hours', 'Monday – Friday, 10am – 4pm Pacific'),
('mail_from_name', 'GIO Mobility Canada'),
('mail_from_email', ''),
('mail_notify_email', ''),
('mail_cc_email', ''),
('mail_send_ack', '1'),
('mail_footer', 'GIO Mobility Canada • Unit 1 - 11400 Twigg Place, Richmond, BC'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_encryption', 'tls'),
('social_facebook', '#'),
('social_instagram', '#'),
('social_youtube', '#'),
('data_retention_months', '24');

-- Demo admin — CHANGE PASSWORD IMMEDIATELY (see README.md)
INSERT INTO admins (name, email, password_hash, role) VALUES
('GIO Administrator', 'admin@giomobility.ca', '$2y$12$PlD7Z6WqK24QtRFMTK8aPuE/FXS1cICZnsEb6ufLwrLipTIY3X9Ba', 'admin');

INSERT INTO categories (name, slug, description, menu_label, image, sort_order) VALUES
('3-Wheel Mobility', '3-wheel-mobility', 'Agile three-wheel scooters with tight turning for everyday outings.', 'Nimble & easy to manoeuvre', 'cat-3wheel.jpg', 1),
('4-Wheel Mobility', '4-wheel-mobility', 'Stable four-wheel scooters built for comfort and confidence.', 'Maximum stability', 'cat-4wheel.jpg', 2),
('All-Season Enclosed', 'enclosed-mobility', 'Fully enclosed cabin scooters with heat and fan for Canadian weather.', 'Comfort beyond the forecast', 'cat-enclosed.jpg', 3),
('Accessible Operation', 'accessible-operation', 'Scooters with simplified controls and easy-operation throttles.', 'Simple, confidence-first controls', 'cat-accessible.jpg', 4),
('Mobility Walkers', 'mobility-walkers', 'Lightweight folding walkers that go wherever you do.', 'Lift & go folding', 'cat-walkers.jpg', 5),
('Parts & Accessories', 'parts-accessories', 'Genuine GIO batteries, tires, covers and more.', 'Keep your GIO rolling', 'cat-parts.jpg', 6);

INSERT INTO products
(sku, slug, name, tagline, short_description, long_description, price, compare_price, show_price, wheel_config, stock_status, availability_text, is_featured, is_best_seller, is_new_arrival, sort_order, range_km, top_speed_kmh, capacity_kg, keywords, seo_title, seo_description) VALUES

('GIO-TITAN-PREM-BLU','gio-titan-premium-blue','GIO Titan Premium — Blue','Motorcycle-inspired design. Our most popular scooter, upgraded.',
 'A Canadian-designed 3-wheel scooter with 50 km range, hydraulic disc brakes and premium finishes.',
 '<p>Inspired by motorcycle styling and designed in Canada, the Titan Premium brings upgraded parts and features to our most popular model to date. Minimal final assembly, generous storage and a high-back adjustable seat make it a confident everyday companion.</p>',
 2595.00, 2995.00, 1, '3-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 1, 1, 0, 1, 50, 25, 200,
 'titan premium 3 wheel scooter long range outdoor',
 'GIO Titan Premium 3-Wheel Mobility Scooter — Blue | GIO Mobility Canada',
 'Canadian-designed 3-wheel mobility scooter. 50 km range, 25 km/h top speed, 200 kg capacity, hydraulic disc brakes. $2,595 CAD.'),

('GIO-TITAN-PREM-RED','gio-titan-premium-red','GIO Titan Premium — Red','Motorcycle-inspired design. Our most popular scooter, upgraded.',
 'A Canadian-designed 3-wheel scooter with 50 km range, hydraulic disc brakes and premium finishes.',
 '<p>Inspired by motorcycle styling and designed in Canada, the Titan Premium brings upgraded parts and features to our most popular model to date. Minimal final assembly, generous storage and a high-back adjustable seat make it a confident everyday companion.</p>',
 2595.00, 2995.00, 1, '3-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 0, 1, 0, 2, 50, 25, 200,
 'titan premium 3 wheel scooter red long range',
 'GIO Titan Premium 3-Wheel Mobility Scooter — Red | GIO Mobility Canada',
 'Canadian-designed 3-wheel mobility scooter in red. 50 km range, 25 km/h top speed, 200 kg capacity. $2,595 CAD.'),

('GIO-TRON-PRO-BLU','gio-tron-pro-blue','GIO Tron PRO — Blue','The smart seat scooter with a custom riding position.',
 'A unique 4-wheel scooter with rotating Smart Seat, adjustable steering column and up to 75 km range.',
 '<p>This unique four-wheeled scooter features an adjustable steering column and adaptive Smart Seat design for a highly custom riding position. The PRO model rides further with the GIO PRO graphene battery.</p>',
 3695.00, 3995.00, 1, '4-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 1, 0, 1, 3, 75, 24, 200,
 'tron pro smart seat 4 wheel scooter',
 'GIO Tron PRO 4-Wheel Smart Seat Scooter — Blue | GIO Mobility Canada',
 '4-wheel mobility scooter with rotating Smart Seat and up to 75 km range. $3,695 CAD.'),

('GIO-TRON-PRO-RED','gio-tron-pro-red','GIO Tron PRO — Red','The smart seat scooter with a custom riding position.',
 'A unique 4-wheel scooter with rotating Smart Seat, adjustable steering column and up to 75 km range.',
 '<p>This unique four-wheeled scooter features an adjustable steering column and adaptive Smart Seat design for a highly custom riding position. The PRO model rides further with the GIO PRO graphene battery.</p>',
 3695.00, 3995.00, 1, '4-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 0, 1, 0, 4, 75, 24, 200,
 'tron pro smart seat 4 wheel scooter red',
 'GIO Tron PRO 4-Wheel Smart Seat Scooter — Red | GIO Mobility Canada',
 '4-wheel mobility scooter with rotating Smart Seat and up to 75 km range, in red. $3,695 CAD.'),

('GIO-TETRIS-PRO-BLU','gio-tetris-pro-blue','GIO Tetris PRO — Blue','Easy operation, designed around you.',
 'A 4-wheel scooter with simplified delta tiller controls, electric brakes and a back-up camera.',
 '<p>Designed with less complex controls and an easily operated throttle, the Tetris PRO is a great choice for those who want straightforward, confidence-first operation — complete with stereo speakers and a back-up camera.</p>',
 3695.00, 3995.00, 1, '4-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 0, 0, 1, 5, 50, 15, 140,
 'tetris pro easy operation scooter accessible',
 'GIO Tetris PRO Easy-Operation Mobility Scooter — Blue | GIO Mobility Canada',
 'Easy-operation 4-wheel scooter with delta tiller, electric brakes and back-up camera. $3,695 CAD.'),

('GIO-TETRIS-PRO-RED','gio-tetris-pro-red','GIO Tetris PRO — Red','Easy operation, designed around you.',
 'A 4-wheel scooter with simplified delta tiller controls, electric brakes and a back-up camera.',
 '<p>Designed with less complex controls and an easily operated throttle, the Tetris PRO is a great choice for those who want straightforward, confidence-first operation — complete with stereo speakers and a back-up camera.</p>',
 3695.00, 3995.00, 1, '4-wheel', 'in_stock', 'In stock — estimated 4–10 business days delivery', 0, 1, 0, 6, 50, 15, 140,
 'tetris pro easy operation scooter red',
 'GIO Tetris PRO Easy-Operation Mobility Scooter — Red | GIO Mobility Canada',
 'Easy-operation 4-wheel scooter with delta tiller and back-up camera, in red. $3,695 CAD.'),

('GIO-GOLF-BLU','gio-all-season-enclosed-blue','GIO All-Season Enclosed — Blue','The look and comfort of a car. The freedom of a scooter.',
 'A fully enclosed, all-season cabin scooter with heater, fan, media centre and room for a passenger.',
 '<p>Stay dry and comfortable in any forecast. The All-Season Enclosed offers the look and comfort of a car with the freedom and flexibility of a mobility scooter — with heat for winter, a fan for summer, and ample room for cargo or a passenger on the rear bench.</p>',
 7699.00, NULL, 1, 'enclosed', 'in_stock', 'In stock — estimated 5–20 business days delivery', 1, 1, 0, 7, 70, 24, 227,
 'enclosed cabin scooter all season winter canada',
 'GIO All-Season Enclosed Mobility Scooter — Blue | GIO Mobility Canada',
 'Fully enclosed all-season mobility scooter with heater, fan, media centre and backup camera. 70 km range. $7,699 CAD.'),

('GIO-GOLF-SLV','gio-all-season-enclosed-silver','GIO All-Season Enclosed — Silver','The look and comfort of a car. The freedom of a scooter.',
 'A fully enclosed, all-season cabin scooter with heater, fan, media centre and room for a passenger.',
 '<p>Stay dry and comfortable in any forecast. The All-Season Enclosed offers the look and comfort of a car with the freedom and flexibility of a mobility scooter — with heat for winter, a fan for summer, and ample room for cargo or a passenger on the rear bench.</p>',
 7699.00, NULL, 1, 'enclosed', 'in_stock', 'In stock — estimated 5–20 business days delivery', 0, 0, 0, 8, 70, 24, 227,
 'enclosed cabin scooter silver all season',
 'GIO All-Season Enclosed Mobility Scooter — Silver | GIO Mobility Canada',
 'Fully enclosed all-season mobility scooter in silver with heater, fan and media centre. $7,699 CAD.'),

('GIO-GOLF-GRN-LE','gio-all-season-enclosed-green','GIO All-Season Enclosed — Limited Edition Green','Limited edition. All-season comfort, unmistakable style.',
 'A limited-edition fully enclosed cabin scooter with heater, fan, media centre and backup camera.',
 '<p>Stay dry and comfortable in any forecast — and stand out doing it. This limited-edition All-Season Enclosed pairs the same heated, fan-cooled cabin and media centre with an exclusive deep green finish.</p>',
 7699.00, NULL, 1, 'enclosed', 'limited', 'Limited edition — estimated 5–20 business days delivery', 0, 0, 1, 9, 70, 24, 227,
 'enclosed scooter limited edition green',
 'GIO All-Season Enclosed Limited Edition Green | GIO Mobility Canada',
 'Limited-edition enclosed all-season mobility scooter in deep green. Heater, fan, media centre. $7,699 CAD.'),

('GIO-NIMBUS','gio-nimbus-folding-walker','GIO Nimbus Folding Mobility Walker','Lightweight. Heavy duty. Lift-and-go folding.',
 'A carbon-fibre and aluminium walker with wide 8-inch wheels, comfort sling seat and zippered storage.',
 '<p>The GIO Nimbus offers a heavy-duty walker experience at the weight of a much smaller walker. Rugged 3-inch-thick 8-inch wheels keep a stable footing, and the frame folds with a simple lift of the seat. Pricing is confirmed on enquiry.</p>',
 NULL, NULL, 0, 'walker', 'in_stock', 'In stock — estimated 4–10 business days delivery', 0, 0, 1, 10, NULL, NULL, 100,
 'nimbus folding walker lightweight carbon fibre rollator',
 'GIO Nimbus Folding Mobility Walker | GIO Mobility Canada',
 'Lightweight heavy-duty folding walker with carbon fibre frame, 8-inch wheels and sling seat. Enquire for CAD pricing.');

-- Product images (demo photography — replaceable from Admin → Products)
INSERT INTO product_images (product_id, file, alt, sort_order, is_featured) VALUES
(1, 'titan-blue-1.jpg', 'GIO Titan Premium 3-wheel mobility scooter in blue, front three-quarter view', 1, 1),
(1, 'titan-blue-2.jpg', 'GIO Titan Premium in blue, front view', 2, 0),
(1, 'titan-blue-3.jpg', 'GIO Titan Premium in blue, side profile with captain seat', 3, 0),
(1, 'titan-blue-4.jpg', 'GIO Titan Premium in blue, rear three-quarter view with basket', 4, 0),
(2, 'titan-red-1.jpg', 'GIO Titan Premium 3-wheel mobility scooter in red, front three-quarter view', 1, 1),
(2, 'titan-red-2.jpg', 'GIO Titan Premium in red, front view', 2, 0),
(2, 'titan-red-3.jpg', 'GIO Titan Premium in red, rear three-quarter view', 3, 0),
(2, 'titan-red-4.jpg', 'GIO Titan Premium in red, side profile', 4, 0),
(3, 'tron-blue-1.jpg', 'GIO Tron PRO 4-wheel scooter in blue with smart seat', 1, 1),
(3, 'tron-blue-2.jpg', 'GIO Tron PRO in blue, front view', 2, 0),
(3, 'tron-blue-3.jpg', 'GIO Tron PRO in blue, rear three-quarter view', 3, 0),
(3, 'tron-blue-4.jpg', 'GIO Tron PRO in blue, side profile', 4, 0),
(4, 'tron-red-1.jpg', 'GIO Tron PRO 4-wheel scooter in red with smart seat', 1, 1),
(4, 'tron-red-2.jpg', 'Rider enjoying the GIO Tron PRO in red outdoors', 2, 0),
(4, 'tron-red-3.jpg', 'GIO Tron PRO in red parked on a pedestrian bridge', 3, 0),
(4, 'tron-red-4.jpg', 'GIO Tron PRO in red, front view', 4, 0),
(5, 'tetris-blue-1.jpg', 'GIO Tetris PRO easy-operation scooter in blue', 1, 1),
(5, 'tetris-blue-2.jpg', 'GIO Tetris PRO in blue, side profile', 2, 0),
(5, 'tetris-blue-3.jpg', 'GIO Tetris PRO in blue, folding frame detail', 3, 0),
(5, 'tetris-blue-4.jpg', 'GIO Tetris PRO in blue, rear view', 4, 0),
(6, 'tetris-red-1.jpg', 'GIO Tetris PRO easy-operation scooter in red', 1, 1),
(6, 'tetris-red-2.jpg', 'GIO Tetris PRO in red, side profile', 2, 0),
(6, 'tetris-red-3.jpg', 'GIO Tetris PRO in red, frame detail', 3, 0),
(6, 'tetris-red-4.jpg', 'GIO Tetris PRO in red, rear view', 4, 0),
(7, 'enclosed-blue-1.jpg', 'GIO All-Season Enclosed cabin mobility scooter in blue', 1, 1),
(7, 'enclosed-blue-2.jpg', 'GIO All-Season Enclosed interior and dash', 2, 0),
(7, 'enclosed-blue-3.jpg', 'GIO All-Season Enclosed cabin seating', 3, 0),
(7, 'enclosed-blue-4.jpg', 'GIO All-Season Enclosed sunroof and windshield view', 4, 0),
(8, 'enclosed-silver-1.jpg', 'GIO All-Season Enclosed cabin mobility scooter in silver', 1, 1),
(8, 'enclosed-silver-2.jpg', 'GIO All-Season Enclosed in silver, interior and dash', 2, 0),
(8, 'enclosed-silver-3.jpg', 'GIO All-Season Enclosed cabin seating', 3, 0),
(8, 'enclosed-silver-4.jpg', 'GIO All-Season Enclosed sunroof and windshield view', 4, 0),
(9, 'enclosed-green-1.jpg', 'GIO All-Season Enclosed limited edition cabin scooter in green', 1, 1),
(9, 'enclosed-green-2.jpg', 'GIO All-Season Enclosed limited edition, interior and dash', 2, 0),
(9, 'enclosed-green-3.jpg', 'GIO All-Season Enclosed cabin seating', 3, 0),
(9, 'enclosed-green-4.jpg', 'GIO All-Season Enclosed sunroof and windshield view', 4, 0),
(10, 'nimbus-1.jpg', 'GIO Nimbus folding mobility walker in blue', 1, 1),
(10, 'nimbus-2.jpg', 'GIO Nimbus walker, folded for transport', 2, 0),
(10, 'nimbus-3.jpg', 'GIO Nimbus walker seat and backrest detail', 3, 0),
(10, 'nimbus-4.jpg', 'GIO Nimbus walker, side profile', 4, 0);

-- Colour variants
INSERT INTO product_variants (product_id, type, name, hex, sku_suffix, is_default, sort_order) VALUES
(1,'colour','Deep Blue','#1B4F9C','BLU',1,1),
(2,'colour','GIO Red','#C8102E','RED',1,1),
(3,'colour','Deep Blue','#1B4F9C','BLU',1,1),
(4,'colour','GIO Red','#C8102E','RED',1,1),
(5,'colour','Deep Blue','#1B4F9C','BLU',1,1),
(6,'colour','GIO Red','#C8102E','RED',1,1),
(7,'colour','Ocean Blue','#24476B','BLU',1,1),
(8,'colour','Silver','#C9CBCE','SLV',1,1),
(9,'colour','Limited Edition Green','#2E5B3F','GRN-LE',1,1),
(10,'colour','Nimbus Blue','#24476B','BLU',1,1);

-- Category mapping
INSERT INTO product_categories (product_id, category_id) VALUES
(1,1),(2,1),
(3,2),(4,2),
(5,2),(5,4),(6,2),(6,4),
(7,3),(8,3),(9,3),
(10,5);

-- Related products
INSERT INTO product_related (product_id, related_product_id) VALUES
(1,2),(1,3),(1,5),(1,7),
(2,1),(2,4),(2,6),(2,7),
(3,4),(3,5),(3,1),
(4,3),(4,6),(4,2),
(5,6),(5,3),(5,1),
(6,5),(6,4),(6,2),
(7,8),(7,9),(7,3),
(8,7),(8,9),(8,4),
(9,7),(9,8),(9,5),
(10,5),(10,6),(10,1);

-- Features (from verified official product data)
INSERT INTO product_features (product_id, feature, sort_order) VALUES
(1,'Minimal final assembly',1),(1,'Rear basket, under-seat storage, bag hook',2),(1,'Alarm system with remote fob',3),(1,'Horn & backup klaxon',4),(1,'Display in miles or kilometres',5),(1,'Headlight and signal lights',6),(1,'Adjustable high-back seat with armrests',7),(1,'USB port for device charging',8),
(2,'Minimal final assembly',1),(2,'Rear basket, under-seat storage, bag hook',2),(2,'Alarm system with remote fob',3),(2,'Horn & backup klaxon',4),(2,'Display in miles or kilometres',5),(2,'Headlight and signal lights',6),(2,'Adjustable high-back seat with armrests',7),(2,'USB port for device charging',8),
(3,'Smart Seat system — rotating sliding seat',1),(3,'Adjustable reclining seatback and headrest',2),(3,'Adjustable steering column',3),(3,'Minimal final assembly',4),(3,'Locking rear cargo bin and under-seat storage',5),(3,'Removable front shopping basket',6),(3,'LED headlights and signal lights',7),(3,'Horn and backup klaxon',8),
(4,'Smart Seat system — rotating sliding seat',1),(4,'Adjustable reclining seatback and headrest',2),(4,'Adjustable steering column',3),(4,'Minimal final assembly',4),(4,'Locking rear cargo bin and under-seat storage',5),(4,'Removable front shopping basket',6),(4,'LED headlights and signal lights',7),(4,'Horn and backup klaxon',8),
(5,'4-wheeled design',1),(5,'Delta tiller with easy-to-operate throttle',2),(5,'Electric brakes',3),(5,'Stereo speakers with MP3 player and Bluetooth',4),(5,'Adjustable seat with swivel and belt',5),(5,'Front bin, tiller bin, rear pocket & locking rear storage',6),(5,'Headlights and signal lights',7),(5,'Back-up camera',8),
(6,'4-wheeled design',1),(6,'Delta tiller with easy-to-operate throttle',2),(6,'Electric brakes',3),(6,'Stereo speakers with MP3 player and Bluetooth',4),(6,'Adjustable seat with swivel and belt',5),(6,'Front bin, tiller bin, rear pocket & locking rear storage',6),(6,'Headlights and signal lights',7),(6,'Back-up camera',8),
(7,'Heater & fan',1),(7,'Alarm system with remote fob',2),(7,'Backup camera',3),(7,'LCD display & media centre (radio, MP3/MP4, Bluetooth)',4),(7,'Headlight, signal lights, horn',5),(7,'Adjustable driver seat with seatbelt',6),(7,'Rear bench for storage or a passenger',7),(7,'Opening side, rear and roof windows',8),(7,'Locking doors',9),(7,'Windshield wiper',10),
(8,'Heater & fan',1),(8,'Alarm system with remote fob',2),(8,'Backup camera',3),(8,'LCD display & media centre (radio, MP3/MP4, Bluetooth)',4),(8,'Headlight, signal lights, horn',5),(8,'Adjustable driver seat with seatbelt',6),(8,'Rear bench for storage or a passenger',7),(8,'Opening side, rear and roof windows',8),(8,'Locking doors',9),(8,'Windshield wiper',10),
(9,'Heater & fan',1),(9,'Alarm system with remote fob',2),(9,'Backup camera',3),(9,'LCD display & media centre (radio, MP3/MP4, Bluetooth)',4),(9,'Headlight, signal lights, horn',5),(9,'Adjustable driver seat with seatbelt',6),(9,'Rear bench for storage or a passenger',7),(9,'Opening side, rear and roof windows',8),(9,'Locking doors',9),(9,'Windshield wiper',10),
(10,'Carbon fibre frame with aluminium alloy',1),(10,'Wide 3" × 8" solid rubber wheels, front casters',2),(10,'Adjustable-height ergonomic comfort-grip handles',3),(10,'Rear braking system for speed control and parking',4),(10,'Curb-assist foot levers',5),(10,'Comfortable sling seat',6),(10,'Simple lift-and-go folding with locking clip',7),(10,'Zippered front storage bag with reflective piping',8);

-- Specifications (verified against official GIO product data; confirm before go-live)
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES
-- Titan Premium (1 & 2)
(1,'Performance','Top speed (High setting)','Up to 25 km/h (15 mph)',1),
(1,'Performance','Speeds by setting','Low 8 km/h • Medium 16 km/h • High 24 km/h • Reverse 5 km/h',2),
(1,'Performance','Range','50 km (30 miles)',3),
(1,'Performance','Full recharge time','6–8 hours',4),
(1,'Performance','Recommended max weight','200 kg (440 lbs) rider + cargo',5),
(1,'Performance','Recommended max incline','25°',6),
(1,'Performance','Turning radius','68 cm (27")',7),
(1,'Battery','Battery','48V20AH sealed lead acid',1),
(1,'Battery','Charger','Portable charger for standard 110V outlet',2),
(1,'Specifications','Motor','500W with differential',1),
(1,'Specifications','Brakes','Front & rear hydraulic disc brakes',2),
(1,'Specifications','Tires','Pneumatic tubeless 10 × 3.00',3),
(1,'Specifications','Display','Backlit LCD',4),
(1,'Specifications','Lights','LED',5),
(1,'Dimensions','Length','155 cm (62")',1),
(1,'Dimensions','Width','70 cm (28")',2),
(1,'Dimensions','Height','120 cm (48")',3),
(1,'Dimensions','Weight','125 kg (276 lbs)',4),
(1,'Dimensions','Seat width','45 cm (18")',5),
(1,'Dimensions','Seat to ground','73 cm (29")',6),
(1,'Dimensions','Ground clearance','Average 12 cm (5")',7),
(1,'Dimensions','Tire diameter','40 cm (16")',8);
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order)
SELECT 2, spec_group, spec_name, spec_value, sort_order FROM product_specs WHERE product_id = 1;

-- Tron PRO (3 & 4)
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES
(3,'Performance','Speeds by setting','Low 8 km/h • Medium 16 km/h • High 24 km/h • Reverse 5 km/h',1),
(3,'Performance','Range','Up to 75 km (45 miles)',2),
(3,'Performance','Full recharge time','6–8 hours',3),
(3,'Performance','Recommended max weight','200 kg (440 lbs) rider + cargo',4),
(3,'Performance','Recommended max incline','15°',5),
(3,'Performance','Turning radius','178 cm (71")',6),
(3,'Battery','Battery','60V23AH GIO PRO Graphene battery (5 × 12V VRLA cells)',1),
(3,'Battery','Charger','Portable charger for standard 110V outlet',2),
(3,'Specifications','Motor','600W with differential',1),
(3,'Specifications','Brakes','Front drum & rear hydraulic disc brakes',2),
(3,'Specifications','Tires','Pneumatic tubeless 90/70-10',3),
(3,'Specifications','Throttle','Twist-grip throttle (right handlebar)',4),
(3,'Specifications','Suspension','Front & rear shocks',5),
(3,'Specifications','Lights','LED',6),
(3,'Dimensions','Length','155 cm (62")',1),
(3,'Dimensions','Width','70 cm (28")',2),
(3,'Dimensions','Height','128 cm (51")',3),
(3,'Dimensions','Weight','152 kg (335 lbs)',4),
(3,'Dimensions','Seat width','45 cm (18")',5),
(3,'Dimensions','Seat to ground','63 cm (25")',6),
(3,'Dimensions','Ground clearance','12 cm (4.75")',7),
(3,'Dimensions','Tire diameter','40 cm (16")',8);
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order)
SELECT 4, spec_group, spec_name, spec_value, sort_order FROM product_specs WHERE product_id = 3;

-- Tetris PRO (5 & 6)
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES
(5,'Performance','Top speed','Up to 15 km/h (9 mph)',1),
(5,'Performance','Range','50 km (30 miles)',2),
(5,'Performance','Braking distance','3 m (10 ft) at top speed',3),
(5,'Performance','Turning radius','177 cm (71")',4),
(5,'Performance','Full recharge time','6–8 hours',5),
(5,'Performance','Recommended max weight','140 kg (308 lbs)',6),
(5,'Performance','Recommended max incline','15°',7),
(5,'Battery','Battery','48V23AH GIO PRO Graphene battery (4 × 12V VRLA cells)',1),
(5,'Battery','Charger','Portable charger for standard 110V outlet',2),
(5,'Specifications','Motor','500W',1),
(5,'Specifications','Throttle','Centre pivot lever with variable speed dial',2),
(5,'Specifications','Brakes','Electromagnetic',3),
(5,'Specifications','Tires','Pneumatic tubeless 13×5-6',4),
(5,'Specifications','Lights','LED',5),
(5,'Specifications','Media player','Stereo speaker, MP3 (USB/SD), Bluetooth',6),
(5,'Dimensions','Length','155 cm (62")',1),
(5,'Dimensions','Width','65 cm (26")',2),
(5,'Dimensions','Height','120 cm (48")',3),
(5,'Dimensions','Weight','108 kg (238 lbs)',4),
(5,'Dimensions','Wheelbase','90 cm (36")',5),
(5,'Dimensions','Ground clearance','7 cm (2.75")',6),
(5,'Dimensions','Seat width','45 cm (18")',7),
(5,'Dimensions','Leg room (adjustable)','32–40 cm (13"–16")',8);
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order)
SELECT 6, spec_group, spec_name, spec_value, sort_order FROM product_specs WHERE product_id = 5;

-- All-Season Enclosed (7, 8, 9)
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES
(7,'Performance','Top speed','Up to 24 km/h (15 mph)',1),
(7,'Performance','Range','Up to 70 km (45 miles)',2),
(7,'Performance','Full recharge time','8–10 hours',3),
(7,'Performance','Recommended max capacity','227 kg (500 lbs) total',4),
(7,'Performance','Recommended max incline','17°',5),
(7,'Battery','Battery','60V58AH sealed lead acid (5 × 12V cells)',1),
(7,'Battery','Charger','Portable charger for standard 110V outlet',2),
(7,'Specifications','Motor','1200W with differential',1),
(7,'Specifications','Brakes','Front & rear disc brakes',2),
(7,'Specifications','Tires','Pneumatic tubeless 4.00-10',3),
(7,'Specifications','Throttle','Twist-grip throttle (right handlebar)',4),
(7,'Specifications','Suspension','Front & rear shocks',5),
(7,'Specifications','Lights','LED',6),
(7,'Dimensions','Exterior (L×W×H)','201 × 135 × 157 cm (79" × 53" × 62")',1),
(7,'Dimensions','Interior (L×W×H)','135 × 79 × 122 cm (53" × 31" × 48")',2),
(7,'Dimensions','Weight','329 kg (725 lbs)',3),
(7,'Dimensions','Step-in height','36 cm (14")',4),
(7,'Dimensions','Bench width','84 cm (33")',5),
(7,'Dimensions','Ground clearance','15 cm (6")',6),
(7,'Dimensions','Tire diameter','46 cm (18")',7);
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order)
SELECT 8, spec_group, spec_name, spec_value, sort_order FROM product_specs WHERE product_id = 7;
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order)
SELECT 9, spec_group, spec_name, spec_value, sort_order FROM product_specs WHERE product_id = 7;

-- Nimbus walker (10)
INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES
(10,'Dimensions','Dimensions open (L×W×H)','74 × 69 × 84–94 cm (29" × 27" × 33–37")',1),
(10,'Dimensions','Dimensions folded (L×W×H)','74 × 32 × 84–94 cm (29" × 12.5" × 33–37")',2),
(10,'Dimensions','Weight','6.8 kg (15 lbs)',3),
(10,'Dimensions','Seat height','61 cm (24")',4),
(10,'Dimensions','Seat width','42 cm (16.5")',5),
(10,'Specifications','Wheels','8 cm (3") thick, 20 cm (8") diameter',1),
(10,'Specifications','Frame','Carbon fibre with aluminium alloy',2),
(10,'Specifications','Storage dimensions (W×H×D)','37 × 23 × 20 cm (14.5" × 9" × 8")',3),
(10,'Performance','Recommended max height','193 cm (6''4")',1),
(10,'Performance','Recommended max weight','100 kg (220 lbs)',2);

-- Demo homepage banners (replaceable from Admin → Banners)
INSERT INTO banners (eyebrow, headline, subheading, cta1_text, cta1_url, cta2_text, cta2_url, desktop_image, mobile_image, text_alignment, overlay_opacity, is_active, sort_order) VALUES
('Mobility, Reimagined.','Go Further. Live Freely.','Discover stylish, thoughtfully designed mobility solutions made for comfort, confidence and everyday independence.','Explore Mobility','/shop','Find Your GIO','/shop','banner-1-desktop.jpg','banner-1-mobile.jpg','left',62,1,1),
('All-Season Enclosed','Comfort Beyond the Forecast.','Rain, snow or shine — the enclosed cabin keeps you moving all year with heat, fan and a media centre.','Shop Enclosed','/category/enclosed-mobility','Why GIO','/why-gio','banner-2-desktop.jpg','banner-2-mobile.jpg','left',58,1,2),
('Thoughtful By Design','Mobility Designed Around You.','Canadian-designed scooters refined with real customer feedback — technology that fits your life, not the other way around.','Explore Models','/shop','Our Story','/about','banner-3-desktop.jpg','banner-3-mobile.jpg','left',58,1,3);

-- FAQs (from verified official GIO support content)
INSERT INTO faqs (category, question, answer, sort_order) VALUES
('Ordering','How do I get a scooter?','Browse the models, then send an enquiry from any product page or call us at 1-855-907-4211. Our team will confirm availability, pricing and delivery, and complete your order with you directly. Full steps are in our Ordering Guide.',1),
('Ordering','Do you have financing?','We do not offer direct financing at this time. If you plan to use a third-party lender, we recommend arranging it before placing your order.',2),
('Ordering','Can I see the scooters in person?','We ship directly to customers across Canada. All product information, pricing and media are available on this website, and our team is happy to answer detailed questions by phone or email.',3),
('Shipping','How long will it take to get my scooter?','In-stock products normally ship within 1–3 business days. Most scooters take an estimated 4–10 business days in transit. Due to its size, the All-Season Enclosed Scooter has more complex routing and takes an estimated 5–20 business days. A local agent will call to set up a delivery appointment.',1),
('Shipping','Will the driver help me unbox or assemble it?','Delivery drivers deliver your packaged scooter only — they do not unbox, assemble or demonstrate. Scooters arrive 90–95% assembled; final steps (like mirror installation) are simple and covered in the product manual.',2),
('Shipping','My scooter was delivered damaged. What do I do?','Please accept the shipment and contact us right away — refusing a shipment delays resolution and may add charges. Photograph any crate damage, inspect the scooter, and document issues. Shipping damage is resolved with warranty replacement parts.',3),
('Warranty','What warranty comes with my scooter?','All scooters include a 12-month parts warranty from the day your scooter arrives — no registration required. It covers part defects, failures or manufacturing errors, with replacement parts shipped free of charge. Wear-and-tear items (tires, brake cables, brake pads) and damage from accidents, incorrect use or modification are excluded. Labour for installation is not included.',1),
('Warranty','Are the batteries covered under warranty?','Yes — batteries are covered against defects. Batteries not properly maintained, stored or cared for per the scooter''s instructions are not considered defective.',2),
('Warranty','My scooter has an issue under warranty. What should I do?','Contact technical support at support@gioelectric.zendesk.com or call 1-855-907-4211 (ext. 2) with your order number and details. Our technician will troubleshoot with you and ship required parts under warranty.',3),
('Warranty','Can I return my product?','You have 30 days to request a return. The product must be in original packaging, in new condition with no signs of use, and return shipping is the customer''s responsibility (we can arrange it and deduct the cost from your refund).',4),
('Product','Do I need a licence, insurance or registration?','In most places, mobility scooters like ours do not require a licence, insurance or registration. Regulations vary by province and municipality, so please verify your local requirements before riding.',1),
('Charging','How do I charge my scooter?','Every GIO scooter includes a portable charger that works with a standard 110V household outlet. A full recharge typically takes 6–8 hours (8–10 hours for the All-Season Enclosed).',1),
('Maintenance','Are tires covered if they go flat?','Tires are a wear item and are not covered under warranty, except for shipping damage reported at delivery. All current GIO Mobility scooters use tubeless tires — an inexpensive tubeless repair kit handles most punctures.',1),
('Assembly','How much assembly is required?','Each GIO scooter arrives 90–95% assembled. Final steps vary by model and are covered in the digital owner''s manual — typically mirrors, seat positioning and a first charge.',1),
('General','Where are you located?','GIO Mobility Canada is based at Unit 1 - 11400 Twigg Place, Richmond, BC. Customer service and technical support are available Monday to Friday, 10am–4pm Pacific.',1);

-- Demo blog posts
INSERT INTO post_categories (name, slug) VALUES
('Buying Guides','buying-guides'),('Mobility Education','mobility-education'),('Product Updates','product-updates'),('Maintenance','maintenance');

INSERT INTO posts (title, slug, excerpt, content, featured_image, is_published, published_at) VALUES
('Choosing Between 3-Wheel and 4-Wheel Mobility','choosing-between-3-wheel-and-4-wheel-mobility','Turning radius or maximum stability? Here''s how to pick the wheel configuration that fits your routes and routines.',
 '<p>Both configurations can deliver an excellent ride — the right choice comes down to where and how you''ll use your scooter most.</p><h3>Choose 3-wheel if…</h3><ul><li>You value a tight turning radius for shops, pathways and tighter spaces.</li><li>You want motorcycle-inspired styling like the GIO Titan Premium.</li></ul><h3>Choose 4-wheel if…</h3><ul><li>Maximum stability and a planted feel matter most to you.</li><li>You''ll ride on varied surfaces and want four points of contact, like the GIO Tron PRO or Tetris PRO.</li></ul><p>Still deciding? Compare any two models side by side on our comparison page, or send an enquiry and our team will help you choose.</p>',
 'post-3vs4.jpg', 1, NOW() - INTERVAL 12 DAY),
('Understanding Mobility Scooter Range','understanding-mobility-scooter-range','What ''50 km range'' really means day to day — and the factors that change it.',
 '<p>Range figures are measured under favourable conditions. Real-world range depends on rider and cargo weight, terrain, incline, temperature, tire condition and speed setting.</p><h3>Getting the most from every charge</h3><ul><li>Charge fully before first use and after rides (6–8 hours for most models).</li><li>Use lower speed settings for longer trips.</li><li>Store and charge batteries at room temperature.</li></ul><p>All GIO scooters include a portable charger for a standard 110V outlet, so topping up at home is simple.</p>',
 'post-range.jpg', 1, NOW() - INTERVAL 26 DAY),
('What to Consider Before Choosing an Enclosed Scooter','what-to-consider-before-choosing-an-enclosed-scooter','Space, delivery and everyday practicality — a quick checklist before you go all-season.',
 '<p>An enclosed cabin scooter is a wonderful four-season companion. Before choosing one, run through this checklist:</p><ul><li><strong>Parking & charging:</strong> you''ll want a covered spot near a standard outlet.</li><li><strong>Measurements:</strong> check gates, doors and garage openings against the scooter''s exterior dimensions.</li><li><strong>Delivery:</strong> enclosed models travel on more complex routes — allow 5–20 business days.</li><li><strong>Local rules:</strong> verify your municipality''s requirements for enclosed mobility vehicles.</li></ul>',
 'post-enclosed.jpg', 1, NOW() - INTERVAL 41 DAY),
('Mobility Scooter Storage Guide','mobility-scooter-storage-guide','Keep your scooter happy through every Canadian season with these storage basics.',
 '<p><h3>Short-term storage</h3><ul><li>Park on a level surface with the brake engaged.</li><li>Keep the charger accessible — a full charge takes 6–8 hours.</li></ul><h3>Winter & long-term storage</h3><ul><li>Store indoors where possible; batteries dislike deep cold.</li><li>Charge the battery fully, then top up monthly.</li><li>A fitted cover keeps dust off between rides.</li></ul><p>Questions about a specific model? Check its digital owner''s manual or contact our team.</p>',
 'post-storage.jpg', 1, NOW() - INTERVAL 58 DAY);

INSERT INTO post_category_map (post_id, category_id) VALUES (1,1),(2,2),(3,1),(4,4);

-- SEO meta overrides for key static pages
INSERT INTO seo_meta (page_key, title, description) VALUES
('home','GIO Mobility Canada — Premium Electric Mobility Scooters','Shop Canadian-designed electric mobility scooters: 3-wheel, 4-wheel, all-season enclosed models and folding walkers. Stylish, dependable mobility for everyday independence.'),
('shop','Shop All Mobility Scooters & Walkers | GIO Mobility Canada','Browse the full GIO lineup: 3-wheel and 4-wheel mobility scooters, the All-Season Enclosed cabin scooter, and the Nimbus folding walker. CAD pricing, Canada-wide delivery.'),
('about','About GIO Mobility Canada','Proudly providing stylish, dependable electric scooters that help Canadians regain mobility and independence for over a decade.'),
('why-gio','Why Choose GIO | Canadian-Designed Mobility Scooters','Canadian-designed scooters, direct pricing, an experienced Vancouver-based support team, and a 12-month parts warranty.'),
('support','Support Centre | GIO Mobility Canada','FAQs, ordering guide, warranty, shipping info, product manuals and contact support for your GIO mobility scooter.'),
('blog','Blog & Buying Guides | GIO Mobility Canada','Guides on choosing, charging, storing and enjoying your mobility scooter — written for Canadian riders.'),
('contact','Contact Us | GIO Mobility Canada','Questions about a model, an order or parts? Call 1-855-907-4211 (Mon–Fri, 10am–4pm Pacific) or send us a message.'),
('stories','Customer Stories | GIO Mobility Canada','Real experiences from GIO riders across Canada.');
