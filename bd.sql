CREATE DATABASE IF NOT EXISTS tools4thetrade
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tools4thetrade;

CREATE TABLE utilizador (
    utl_id     INT(11)      NOT NULL AUTO_INCREMENT,
    utl_nome   VARCHAR(128),
    utl_email  VARCHAR(128) UNIQUE,
    utl_passe  VARCHAR(256),
    utl_admin  TINYINT(1)   DEFAULT 0,
    utl_criado DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utl_id)
);

CREATE TABLE categoria (
    cat_id   INT(11)     NOT NULL AUTO_INCREMENT,
    cat_nome VARCHAR(64),
    PRIMARY KEY (cat_id)
);

CREATE TABLE ferramenta (
    fer_id        INT(11)       NOT NULL AUTO_INCREMENT,
    fer_utl_id    INT(11),
    fer_cat_id    INT(11),
    fer_nome      VARCHAR(128),
    fer_descricao VARCHAR(512),
    fer_preco_base DECIMAL(8,2),
    fer_preco     DECIMAL(8,2),
    fer_lat       DECIMAL(10,7),
    fer_lng       DECIMAL(10,7),
    fer_ativa     TINYINT(1)    DEFAULT 1,
    fer_criada    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fer_id),
    FOREIGN KEY (fer_utl_id) REFERENCES utilizador(utl_id),
    FOREIGN KEY (fer_cat_id) REFERENCES categoria(cat_id)
);

CREATE TABLE aluguer (
    alu_id        INT(11)  NOT NULL AUTO_INCREMENT,
    alu_fer_id    INT(11),
    alu_utl_id    INT(11),
    alu_inicio    DATE,
    alu_fim       DATE,
    alu_estado    ENUM('Reservado','Alugado','Devolvido') NOT NULL DEFAULT 'Reservado',
    alu_devolvido DATETIME DEFAULT NULL,
    alu_criado    DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alu_id),
    FOREIGN KEY (alu_fer_id) REFERENCES ferramenta(fer_id),
    FOREIGN KEY (alu_utl_id) REFERENCES utilizador(utl_id)
);

-- Seed categories
INSERT INTO categoria (cat_nome) VALUES
    ('Elétrica'),
    ('Manual'),
    ('Construção'),
    ('Jardim'),
    ('Medição');
