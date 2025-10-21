-- MySQL schema for OpenClimate-DHIS (PHP/LAMP)
CREATE TABLE IF NOT EXISTS org_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  dhis2_uid VARCHAR(32) NOT NULL,
  lat DECIMAL(9,6) NOT NULL,
  lon DECIMAL(9,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS climate_values (
  org_unit_id INT NOT NULL,
  date_utc CHAR(8) NOT NULL,
  tmean_c DECIMAL(5,2) NULL,
  rain_mm DECIMAL(6,1) NULL,
  source VARCHAR(32) NOT NULL,
  PRIMARY KEY (org_unit_id, date_utc),
  CONSTRAINT fk_cv_ou FOREIGN KEY (org_unit_id) REFERENCES org_units(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO org_units (name, dhis2_uid, lat, lon) VALUES
('Addis Ababa - Health Zone A', 'Zw9G1aB3', 8.9806, 38.7578),
('Adama Hospital',               'AbC123xyZ', 8.5406, 39.2700),
('Bahir Dar - Cluster 1',        'Yt7LmN0pQ', 11.5740, 37.3614);

INSERT INTO climate_values (org_unit_id, date_utc, tmean_c, rain_mm, source) VALUES
(1, '20250101', 21.5, 5.4, 'DEMO'),
(2, '20250101', 22.1, 3.0, 'DEMO'),
(3, '20250101', 19.8, 0.0, 'DEMO'),
(1, '20250102', 22.3, 0.0, 'DEMO'),
(2, '20250102', 23.0, 1.2, 'DEMO'),
(3, '20250102', 20.0, 0.0, 'DEMO');
