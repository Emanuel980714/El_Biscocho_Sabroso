-- init.sql - Estructura de base de datos para 'El Biscocho Sabroso'
-- Ejecuta este script en HeidiSQL dentro de tu base de datos.

CREATE TABLE IF NOT EXISTS productos (
  id        VARCHAR(20) PRIMARY KEY,
  nombre    VARCHAR(120) NOT NULL,
  categoria VARCHAR(60)  NOT NULL,
  precio    DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock     INT NOT NULL DEFAULT 0,
  imagen    VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ventas (
  id     BIGINT AUTO_INCREMENT PRIMARY KEY,
  folio  VARCHAR(30) UNIQUE NOT NULL,
  fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total  DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS venta_items (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  venta_id     BIGINT NOT NULL,
  producto_id  VARCHAR(20) NOT NULL,
  qty          INT NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  sub          DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_items_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_prod  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Productos iniciales (coinciden con tu CATALOGO en index.html)
INSERT IGNORE INTO productos (id, nombre, categoria, precio, stock) VALUES
('pan001','Concha de Vainilla','Dulce',10,24),
('pan002','Concha de Chocolate','Dulce',11,20),
('pan003','Cuernito (Croissant)','Hojaldre',14,18),
('pan004','Oreja','Hojaldre',9,26),
('pan005','Dona Glaseada','Dulce',12,30),
('pan006','Pan de Elote','Especialidad',16,12),
('pan009','Bolillo','Salado',4,60),
('pan010','Telera','Salado',5,50);
