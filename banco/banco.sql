CREATE DATABASE IF NOT EXISTS projetoyudi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projetoyudi;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno','professor') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta TEXT NOT NULL,
    alternativa_a TEXT NOT NULL,
    alternativa_b TEXT NOT NULL,
    alternativa_c TEXT NOT NULL,
    alternativa_d TEXT NOT NULL,
    correta CHAR(1) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    assunto VARCHAR(150) NOT NULL DEFAULT '',
    professor_id INT NOT NULL,
    INDEX idx_questoes_professor (professor_id),
    INDEX idx_questoes_disciplina (disciplina)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    professor_id INT NOT NULL,
    INDEX idx_provas_professor (professor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prova_questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prova_id INT NOT NULL,
    questao_id INT NOT NULL,
    UNIQUE KEY uk_prova_questao (prova_id, questao_id),
    INDEX idx_pq_prova (prova_id),
    INDEX idx_pq_questao (questao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    prova_id INT NOT NULL,
    nota DECIMAL(5,2) NOT NULL DEFAULT 0,
    data_realizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resultados_aluno (aluno_id),
    INDEX idx_resultados_prova (prova_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respostas_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resultado_id INT NOT NULL,
    questao_id INT NOT NULL,
    resposta_aluno VARCHAR(1) NOT NULL DEFAULT '',
    resposta_correta VARCHAR(1) NOT NULL,
    correta TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_respostas_resultado (resultado_id),
    INDEX idx_respostas_questao (questao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
