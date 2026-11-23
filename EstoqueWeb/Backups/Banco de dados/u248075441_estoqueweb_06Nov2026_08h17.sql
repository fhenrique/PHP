-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 06/11/2026 às 11:17
-- Versão do servidor: 11.8.9-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u248075441_estoqueweb`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'Material de Escritório', 'Material de Escritório', 1, '2026-11-05 19:52:44'),
(2, 'Material de Limpeza', 'Material de Limpeza', 0, '2026-11-05 19:53:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas_estoque`
--

CREATE TABLE `entradas_estoque` (
  `id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_entrada` date NOT NULL,
  `tipo_documento` varchar(30) NOT NULL,
  `numero_documento` varchar(50) NOT NULL,
  `valor_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `entradas_estoque`
--

INSERT INTO `entradas_estoque` (`id`, `fornecedor_id`, `usuario_id`, `data_entrada`, `tipo_documento`, `numero_documento`, `valor_total`, `observacoes`, `data_cadastro`) VALUES
(1, 1, 1, '2026-11-05', 'Nota Fiscal', '123654564', 520.00, 'observacoes da NF', '2026-11-05 22:54:26'),
(2, 1, 1, '2026-11-05', 'Cupom Fiscal', '465464654', 520.00, 'obs do cupom', '2026-11-05 22:56:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `razao_social` varchar(150) NOT NULL,
  `nome_fantasia` varchar(150) DEFAULT NULL,
  `tipo_pessoa` enum('F','J') NOT NULL DEFAULT 'J',
  `cpf_cnpj` varchar(18) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `status` enum('A','I') NOT NULL DEFAULT 'A',
  `observacoes` text DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `razao_social`, `nome_fantasia`, `tipo_pessoa`, `cpf_cnpj`, `telefone`, `email`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `status`, `observacoes`, `data_cadastro`, `data_atualizacao`) VALUES
(1, 'Razão Social 1', 'RZ1', 'J', '66136218000186', '953395785', 'fabio.veiga@live.com', 'rua manoel ouro preto', '41', 'Casa', 'são josé', 'Garanhuns', 'PE', '55295-260', 'A', 'observacoes', '2026-11-05 21:48:40', '2026-11-05 22:53:01'),
(2, 'Fábio Henrique Rodrigues Veiga', NULL, 'F', '02787291422', '11953395785', 'fabio@live.com', 'Rua rua', '46', 'casa', 'bairro', 'cidade', 'AP', '55295-290', 'A', 'obs de fabio', '2026-11-05 21:51:02', '2026-11-05 21:54:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_entrada`
--

CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `valor_unitario` decimal(12,2) NOT NULL,
  `valor_total` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_entrada`
--

INSERT INTO `itens_entrada` (`id`, `entrada_id`, `produto_id`, `quantidade`, `valor_unitario`, `valor_total`) VALUES
(1, 1, 2, 20.000, 12.00, 240.00),
(2, 1, 1, 10.000, 28.00, 280.00),
(3, 2, 2, 20.000, 12.00, 240.00),
(4, 2, 1, 10.000, 28.00, 280.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produto_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('ENTRADA','SAIDA') NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `valor_unitario` decimal(12,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `setor_destino` varchar(150) DEFAULT NULL,
  `responsavel_recebimento` varchar(150) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `data_movimentacao` datetime NOT NULL DEFAULT current_timestamp(),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `movimentacoes`
--

INSERT INTO `movimentacoes` (`id`, `produto_id`, `usuario_id`, `tipo`, `quantidade`, `valor_unitario`, `valor_total`, `fornecedor_id`, `documento`, `setor_destino`, `responsavel_recebimento`, `motivo`, `observacao`, `data_movimentacao`, `criado_em`) VALUES
(1, 2, 1, 'ENTRADA', 20.000, 12.00, 240.00, 1, 'Nota Fiscal - 123654564', '', 'fabio', 'Entrada de estoque', 'observacoes da NF', '2026-11-05 22:54:26', '2026-11-05 22:54:26'),
(2, 1, 1, 'ENTRADA', 10.000, 28.00, 280.00, 1, 'Nota Fiscal - 123654564', '', 'fabio', 'Entrada de estoque', 'observacoes da NF', '2026-11-05 22:54:26', '2026-11-05 22:54:26'),
(3, 2, 1, 'ENTRADA', 20.000, 12.00, 240.00, 1, 'Cupom Fiscal - 465464654', '', 'fabio', 'Entrada de estoque', 'obs do cupom', '2026-11-05 22:56:26', '2026-11-05 22:56:26'),
(4, 1, 1, 'ENTRADA', 10.000, 28.00, 280.00, 1, 'Cupom Fiscal - 465464654', '', 'fabio', 'Entrada de estoque', 'obs do cupom', '2026-11-05 22:56:26', '2026-11-05 22:56:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Administrador', 'Acesso completo ao sistema', 1),
(2, 'Operador', 'Cadastro e movimentação de estoque', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `unidade` varchar(20) NOT NULL DEFAULT 'UN',
  `estoque_atual` decimal(12,3) NOT NULL DEFAULT 0.000,
  `estoque_minimo` decimal(12,3) NOT NULL DEFAULT 0.000,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `categoria_id`, `codigo`, `nome`, `descricao`, `unidade`, `estoque_atual`, `estoque_minimo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'PROD001', 'Papel A4', 'Resma de papel A4', 'PCT', 10.000, 10.000, 1, '2026-11-05 20:05:24', '2026-11-06 00:27:51'),
(2, 1, 'PROD002', 'Caneta BIC preta', 'Caixa de canetas BIC preta', 'CX', 40.000, 2.000, 1, '2026-11-05 20:06:28', '2026-11-05 22:56:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `perfil_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `perfil_id`, `nome`, `email`, `senha`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'fabio', 'fabio.veiga@live.com', '$2y$10$xRTYxXvJeF6lD6.M82UZUO.tr/EyFcSrXVZKJW06v0nzI5L6R4Wsu', 1, '2026-11-05 19:15:37', '2026-11-05 19:15:37'),
(2, 2, 'operador 01', 'email@teste.com.br', '$2y$10$HpMM.fgXB.EqWTKIq.pdo.3RgFWLUHRO8hj5c8dv1y36D6MzEwEsq', 1, '2026-11-05 20:33:04', '2026-11-05 20:36:33'),
(3, 1, 'Eduardo Martins', 'eduardo@teste.com', '$2y$10$xXV4gFwLPkVReXUGCD9bV.Q2saPx7pFGRFbMjtopkXzvMDYgyQE7u', 1, '2026-11-05 20:50:42', '2026-11-05 20:50:42');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `entradas_estoque`
--
ALTER TABLE `entradas_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entrada_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_entrada_usuario` (`usuario_id`),
  ADD KEY `idx_entrada_data` (`data_entrada`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fornecedores_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_fornecedores_razao_social` (`razao_social`),
  ADD KEY `idx_fornecedores_status` (`status`);

--
-- Índices de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_entrada` (`entrada_id`),
  ADD KEY `idx_item_produto` (`produto_id`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimentacoes_produto` (`produto_id`),
  ADD KEY `idx_movimentacoes_usuario` (`usuario_id`),
  ADD KEY `idx_movimentacoes_tipo` (`tipo`),
  ADD KEY `idx_movimentacoes_data` (`data_movimentacao`),
  ADD KEY `idx_movimentacoes_produto_data` (`produto_id`,`data_movimentacao`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_produtos_nome` (`nome`),
  ADD KEY `idx_produtos_categoria` (`categoria_id`),
  ADD KEY `idx_produtos_ativo` (`ativo`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_perfis` (`perfil_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `entradas_estoque`
--
ALTER TABLE `entradas_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `fk_movimentacoes_produtos` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produtos_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_perfis` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
