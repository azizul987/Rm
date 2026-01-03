USE rm_properti;

-- USERS (admin)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SALES
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  title VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  whatsapp VARCHAR(50) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  areas VARCHAR(255) DEFAULT NULL,
  experience_years INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- PROPERTIES
CREATE TABLE properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(220) NOT NULL,
  type VARCHAR(80) NOT NULL,
  price BIGINT NOT NULL DEFAULT 0,
  location VARCHAR(120) NOT NULL,
  beds INT NOT NULL DEFAULT 0,
  baths INT NOT NULL DEFAULT 0,
  land INT NOT NULL DEFAULT 0,
  building INT NOT NULL DEFAULT 0,
  description TEXT DEFAULT NULL,
  features_json JSON DEFAULT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  sales_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_properties_sales
    FOREIGN KEY (sales_id) REFERENCES sales(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- PROPERTY IMAGES (gallery)
CREATE TABLE property_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_images_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed SALES
INSERT INTO sales (name, title, phone, whatsapp, email, bio, areas, experience_years)
VALUES
('Rizki Pratama', 'Property Consultant', '081234567890', '6281234567890', 'rizki@rmproperti.id',
 'Spesialis rumah dan ruko. Fokus proses cepat, transparan, dan aman sampai serah terima.',
 'Palembang,Ogan Ilir', 4),
('Dina Aulia', 'Senior Sales', '081298765432', '6281298765432', 'dina@rmproperti.id',
 'Spesialis apartemen dan tanah. Membantu negosiasi, legalitas, hingga closing.',
 'Jakarta,Tangerang', 7);

-- Seed PROPERTIES
INSERT INTO properties (title, type, price, location, beds, baths, land, building, description, features_json, status, sales_id)
VALUES
('Rumah Minimalis 3KT Dekat Kampus', 'Rumah', 850000000, 'Palembang', 3, 2, 120, 90,
 'Rumah siap huni dengan akses jalan lebar. Dekat minimarket dan kampus.',
 JSON_ARRAY('Carport','Dapur luas','Air PDAM','Listrik 2200W'),
 'active', 1),
('Ruko 2 Lantai Pinggir Jalan Utama', 'Ruko', 1350000000, 'Palembang', 2, 2, 90, 150,
 'Lokasi strategis dengan traffic tinggi. Cocok untuk usaha retail/office.',
 JSON_ARRAY('Balkon','Parkir luas','CCTV area','Dekat pusat kuliner'),
 'active', 1),
('Tanah Kavling SHM Siap Bangun', 'Tanah', 420000000, 'Tangerang', 0, 0, 100, 0,
 'Kavling matang, SHM, akses mudah ke tol. Lingkungan berkembang.',
 JSON_ARRAY('SHM','Akses tol','Jalan cor','Kawasan berkembang'),
 'active', 2);
