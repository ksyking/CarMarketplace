-- Reset schema cleanly
USE autotrade;

-- USERS
DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role            ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
  email           VARCHAR(255) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  display_name    VARCHAR(100) NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- VEHICLES (canonical specs)
DROP TABLE IF EXISTS vehicles;
CREATE TABLE IF NOT EXISTS vehicles (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  make          VARCHAR(80)  NOT NULL,
  model         VARCHAR(80)  NOT NULL,
  trim          VARCHAR(80)  NULL,
  year          SMALLINT UNSIGNED NOT NULL,
  body_type     VARCHAR(40)  NOT NULL,   -- e.g., SUV, Truck, Coupe
  drivetrain    VARCHAR(20)  NOT NULL,   -- e.g., FWD, RWD, AWD, 4WD
  fuel_type     VARCHAR(20)  NULL,       -- e.g., Gas, Diesel, Hybrid, EV
  transmission  VARCHAR(20)  NULL,       -- e.g., Auto, Manual
  UNIQUE KEY u_vehicle (make, model, trim, year, body_type, drivetrain, fuel_type, transmission)
) ENGINE=InnoDB;

-- LISTINGS
DROP TABLE IF EXISTS listings;
CREATE TABLE IF NOT EXISTS listings (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id       INT UNSIGNED NOT NULL,
  vehicle_id      INT UNSIGNED NOT NULL,
  title           VARCHAR(150) NOT NULL,
  description     TEXT NULL,
  color_ext       VARCHAR(40)  NULL,
  color_int       VARCHAR(40)  NULL,
  mileage         INT UNSIGNED NOT NULL DEFAULT 0,
  price           DECIMAL(10,2) NOT NULL,
  city            VARCHAR(100) NULL,
  state           VARCHAR(100) NULL,
  condition_grade TINYINT UNSIGNED NULL,        
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_listings_seller  FOREIGN KEY (seller_id)  REFERENCES users(id),
  CONSTRAINT fk_listings_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  INDEX ix_listings_search (price, mileage, is_active),
  INDEX ix_listings_filters (color_ext, color_int, condition_grade),
  INDEX ix_listings_geo (state, city)
) ENGINE=InnoDB;

-- PHOTOS
DROP TABLE IF EXISTS photos;
CREATE TABLE IF NOT EXISTS photos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id  INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photos_listing FOREIGN KEY (listing_id) REFERENCES listings(id),
  INDEX ix_photos_listing (listing_id, sort_order)
) ENGINE=InnoDB;

-- FEATURES
DROP TABLE IF EXISTS features;
CREATE TABLE IF NOT EXISTS features (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- LISTING_FEATURES (M:N)
DROP TABLE IF EXISTS listing_features;
CREATE TABLE IF NOT EXISTS listing_features (
  listing_id  INT UNSIGNED NOT NULL,
  feature_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (listing_id, feature_id),
  CONSTRAINT fk_lf_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  CONSTRAINT fk_lf_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- SAVED LISTS
DROP TABLE IF EXISTS saved_lists;
CREATE TABLE IF NOT EXISTS saved_lists (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(80) NOT NULL,              -- e.g., "Compare", "Favorites"
  is_compare TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_saved_lists_user FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY u_user_listname (user_id, name)
) ENGINE=InnoDB;

-- SAVED LIST ITEMS
DROP TABLE IF EXISTS saved_list_items;
CREATE TABLE IF NOT EXISTS saved_list_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  saved_list_id INT UNSIGNED NOT NULL,
  listing_id    INT UNSIGNED NOT NULL,
  added_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sli_list   FOREIGN KEY (saved_list_id) REFERENCES saved_lists(id) ON DELETE CASCADE,
  CONSTRAINT fk_sli_listing FOREIGN KEY (listing_id)    REFERENCES listings(id) ON DELETE CASCADE,
  UNIQUE KEY u_list_listing (saved_list_id, listing_id)
) ENGINE=InnoDB;

-- ORDERS (mock purchase flow)
DROP TABLE IF EXISTS orders;
CREATE TABLE IF NOT EXISTS orders (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id     INT UNSIGNED NOT NULL,
  listing_id   INT UNSIGNED NOT NULL,
  status       ENUM('initiated','cancelled','completed') NOT NULL DEFAULT 'initiated',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_buyer   FOREIGN KEY (buyer_id)   REFERENCES users(id),
  CONSTRAINT fk_orders_listing FOREIGN KEY (listing_id) REFERENCES listings(id),
  UNIQUE KEY u_order_listing (listing_id)
) ENGINE=InnoDB;

-- RECENTLY VIEWED LISTINGS (per-buyer, per-listing rollup)
DROP TABLE IF EXISTS recently_viewed_listings;
CREATE TABLE IF NOT EXISTS recently_viewed_listings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,     -- buyer who viewed
  listing_id   INT UNSIGNED NOT NULL,     -- listing that was viewed
  view_count   INT UNSIGNED NOT NULL DEFAULT 1,
  first_viewed TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_viewed  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rv_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_rv_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  UNIQUE KEY u_user_listing (user_id, listing_id),                 -- one row per buyer+listing
  INDEX ix_recent_user_time (user_id, last_viewed),                -- fetch “recently viewed” fast
  INDEX ix_recent_listing   (listing_id)                           -- simple analytics
) ENGINE=InnoDB;
