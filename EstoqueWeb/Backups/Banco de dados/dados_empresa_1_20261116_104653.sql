
-- BACKUP DOS DADOS DO ESTOQUEWEB
-- Empresa: Empresa A
-- ID da empresa: 1
-- Data: 16/11/2026 10:46:53

SET FOREIGN_KEY_CHECKS = 0;

-- TABELA: categorias
INSERT INTO `categorias` (`id`, `empresa_id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES (10, 1, 'MATESC', 'Material de Escritório', 1, '2026-11-16 10:43:56');
INSERT INTO `categorias` (`id`, `empresa_id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES (11, 1, 'MATLIMP', 'Material de Limpeza', 1, '2026-11-16 10:44:12');

-- TABELA: fornecedores
-- TABELA: fornecedores (sem registros)

-- TABELA: produtos
-- TABELA: produtos (sem registros)

-- TABELA: usuarios
INSERT INTO `usuarios` (`id`, `perfil_id`, `empresa_id`, `nome`, `email`, `senha`, `ativo`, `criado_em`, `atualizado_em`) VALUES (15, 1, 1, 'Eduardo Martins', 'eduardo@admin.com', '$2y$10$7IRFVhxqpOhZ8dE3y5C4AuvnSqR3rKDlZHokNNfpBvz5Co/qmfOp.', 1, '2026-11-08 02:11:25', '2026-11-08 02:11:25');

-- TABELA: entradas_estoque
-- TABELA: entradas_estoque (sem registros)

-- TABELA: itens_entrada
-- TABELA: itens_entrada (sem registros)

-- TABELA: movimentacoes
-- TABELA: movimentacoes (sem registros)

SET FOREIGN_KEY_CHECKS = 1;
-- FIM DO BACKUP DOS DADOS
