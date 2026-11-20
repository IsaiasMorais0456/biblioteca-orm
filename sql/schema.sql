CREATE DATABASE IF NOT EXISTS biblioteca_orm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biblioteca_orm;

CREATE TABLE IF NOT EXISTS autores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nacionalidade VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS livros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    ano_publicacao INT NOT NULL,
    isbn VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS livros_autores (
    livro_id INT NOT NULL,
    autor_id INT NOT NULL,
    PRIMARY KEY (livro_id, autor_id),
    CONSTRAINT fk_la_livro
    FOREIGN KEY (livro_id) REFERENCES livros(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_la_autor
    FOREIGN KEY (autor_id) REFERENCES autores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;