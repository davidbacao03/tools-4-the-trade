CREATE DATABASE IF NOT EXISTS tools4thetrade;
USE tools4thetrade;

CREATE TABLE utilizador (
	utl_id		INT PRIMARY KEY AUTO_INCREMENT,
	utl_nome	VARCHAR(128),
	utl_email	VARCHAR(128) UNIQUE,
	utl_passe	VARCHAR(256),
	utl_admin	TINYINT(1) DEFAULT 0,
	utl_criado	DATETIME DEFAULT CURRENT_TIMESTAMP
)ENGINE=INNODB;

CREATE TABLE categoria (
	cat_id		INT PRIMARY KEY AUTO_INCREMENT,
	cat_nome	VARCHAR(64)
)ENGINE=INNODB;

CREATE TABLE ferramenta (
	fer_id		INT PRIMARY KEY AUTO_INCREMENT,
	fer_utl_id	INT,
	fer_cat_id	INT,
	fer_nome	VARCHAR(128),
	fer_descricao	VARCHAR(512),
	fer_preco_base	DECIMAL(8,2),
	fer_preco	DECIMAL(8,2),
	fer_lat		DECIMAL(10,7),
	fer_lng		DECIMAL(10,7),
	fer_ativa	TINYINT(1) DEFAULT 1,
	fer_criada	DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (fer_utl_id) REFERENCES utilizador(utl_id),
	FOREIGN KEY (fer_cat_id) REFERENCES categoria(cat_id)
)ENGINE=INNODB;

CREATE TABLE aluguer (
	alu_id		INT PRIMARY KEY AUTO_INCREMENT,
	alu_fer_id	INT,
	alu_utl_id	INT,
	alu_inicio	DATE,
	alu_fim		DATE,
	alu_estado	ENUM('reservado', 'alugado', 'devolvido') DEFAULT 'reservado',
	alu_criado	DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (alu_fer_id) REFERENCES ferramenta(fer_id),
	FOREIGN KEY (alu_utl_id) REFERENCES utilizador(utl_id)
)ENGINE=INNODB;

INSERT INTO categoria (cat_nome) VALUES
('Ferramentas Manuais'),
('Ferramentas Elétricas'),
('Ferramentas a Bateria'),
('Medição e Traçagem'),
('Construção'),
('Jardim'),
('Mecânica'),
('Outros');