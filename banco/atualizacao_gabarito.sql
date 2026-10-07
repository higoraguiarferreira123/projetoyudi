-- =========================================================
-- ATUALIZAÇÃO DO GABARITO DAS PROVAS
-- Banco: projetoyudi
-- =========================================================

USE projetoyudi;

-- Esta tabela guarda a resposta de cada aluno para cada questão.
-- Se ela já existir corretamente, não é necessário recriá-la.

CREATE TABLE IF NOT EXISTS respostas_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resultado_id INT NOT NULL,
    questao_id INT NOT NULL,
    resposta_aluno VARCHAR(1) NOT NULL DEFAULT '',
    resposta_correta VARCHAR(1) NOT NULL,
    correta TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_resultado_id (resultado_id),
    INDEX idx_questao_id (questao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depois de substituir os arquivos PHP, faça uma nova prova como aluno.
-- Os registros serão gravados automaticamente em respostas_alunos.
