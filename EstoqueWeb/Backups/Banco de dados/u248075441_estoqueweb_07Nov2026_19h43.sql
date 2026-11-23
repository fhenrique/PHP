-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 07/11/2026 às 22:43
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
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `empresa_id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 1, 'Cat 1 Empresa Teste', 'Cat 1 Empresa Teste', 1, '2026-11-05 19:52:44'),
(2, 1, 'Cat 2 Empresa Teste', 'Cat 2 Empresa Teste', 1, '2026-11-05 19:53:00'),
(6, 2, 'Cat 1 Empresa Teste 2', 'Cat 1 Empresa Teste 2', 1, '2026-11-07 00:40:07'),
(7, 2, 'Cat 2 Empresa Teste 2', 'Cat 2 Empresa Teste 2', 1, '2026-11-07 00:40:57'),
(8, 3, 'Cat 1 Empresa 3', 'Cat 1 Empresa 3', 1, '2026-11-07 00:44:58'),
(9, 3, 'Cat 2 Empresa 3', 'Cat 2 Empresa 3', 1, '2026-11-07 00:45:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `nome`, `ativo`, `criado_em`) VALUES
(1, 'Empresa Teste', 1, '2026-11-06 22:13:54'),
(2, 'Empresa Teste 2', 1, '2026-11-07 00:13:56'),
(3, 'Empresa Teste 3', 1, '2026-11-07 00:42:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas_estoque`
--

CREATE TABLE `entradas_estoque` (
  `id` int(11) NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `entradas_estoque` (`id`, `empresa_id`, `fornecedor_id`, `usuario_id`, `data_entrada`, `tipo_documento`, `numero_documento`, `valor_total`, `observacoes`, `data_cadastro`) VALUES
(5, 1, 2, 3, '2026-11-07', 'Nota Fiscal', 'asfasdf56565', 100.00, 'asdfasdf', '2026-11-07 12:32:10'),
(6, 1, 2, 3, '2026-11-07', 'Cupom Fiscal', 'asdfasdf', 144.00, 'asdfasdf', '2026-11-07 12:32:39'),
(7, 2, 3, 7, '2026-11-07', 'Nota Fiscal', 'asdfasdfasdf', 400.00, 'asdfasdfsdf', '2026-11-07 14:39:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `fornecedores` (`id`, `empresa_id`, `razao_social`, `nome_fantasia`, `tipo_pessoa`, `cpf_cnpj`, `telefone`, `email`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `status`, `observacoes`, `data_cadastro`, `data_atualizacao`) VALUES
(1, 1, 'Forn 2 Empresa Teste', 'FORN2EMPTESTE', 'J', '66136218000186', '953395785', 'fabio.veiga@live.com', 'Rua Manoel Ouro Preto', '41', 'Casa', 'São José', 'Garanhuns', 'PE', '55295-260', 'A', 'observacoes', '2026-11-05 21:48:40', '2026-11-07 01:59:36'),
(2, 1, 'Forn 1 Empresa Teste', 'FORN1EMPTESTE', 'F', '02787291422', '11953395785', 'fabio@live.com', 'Rua Monsenhor Callou', '715', 'casa', 'São José', 'Garanhuns', 'PE', '55295-290', 'A', 'obs de fabio', '2026-11-05 21:51:02', '2026-11-07 01:58:59'),
(3, 2, 'Forn 1 Empresa Teste 2', 'FORN1EMPTESTE2', 'F', '54654216542', '119565456545', NULL, 'Rua Manoel Ouro Preto', '43', NULL, 'São José', 'Garanhuns', 'PE', '55295-260', 'A', NULL, '2026-11-07 02:08:04', NULL),
(4, 2, 'Forn 2 Empresa Teste 2', 'FORN2EMPTESTE2', 'F', '65461326543', '1265465454', NULL, 'Rua Sandra de Almeida Barros', '55', NULL, 'Francisco Simão dos Santos Figueira', 'Garanhuns', 'PE', '55291-060', 'A', NULL, '2026-11-07 02:09:08', NULL),
(5, 3, 'Forn 1 Empresa Teste 3', 'FORN1EMPTESTE3', 'F', '02787291422', '11953356544', NULL, 'Rua Francisco da Lira', '130', NULL, 'Jardim Leonor Mendes de Barros', 'São Paulo', 'SP', '02346-010', 'A', NULL, '2026-11-07 02:10:49', NULL),
(6, 3, 'Forn 2 Empresa Teste 3', 'FORN2EMPTESTE3', 'F', '02454654654', '1165884455', NULL, 'Avenida Otaviano Alves de Lima', '120', NULL, 'Limão', 'São Paulo', 'SP', '02546-010', 'A', NULL, '2026-11-07 02:11:31', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_entrada`
--

CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(10) UNSIGNED NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `valor_unitario` decimal(12,2) NOT NULL,
  `valor_total` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_entrada`
--

INSERT INTO `itens_entrada` (`id`, `empresa_id`, `entrada_id`, `produto_id`, `quantidade`, `valor_unitario`, `valor_total`) VALUES
(9, 1, 5, 2, 10.000, 10.00, 100.00),
(10, 1, 6, 1, 12.000, 12.00, 144.00),
(11, 2, 7, 4, 20.000, 20.00, 400.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `produto_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('ENTRADA','SAIDA') NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `valor_unitario` decimal(12,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
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

INSERT INTO `movimentacoes` (`id`, `empresa_id`, `produto_id`, `usuario_id`, `tipo`, `quantidade`, `valor_unitario`, `valor_total`, `fornecedor_id`, `documento`, `setor_destino`, `responsavel_recebimento`, `motivo`, `observacao`, `data_movimentacao`, `criado_em`) VALUES
(9, 1, 2, 3, 'ENTRADA', 10.000, 10.00, 100.00, 2, 'Nota Fiscal - asfasdf56565', '', 'Eduardo Martins', 'Entrada de estoque', 'asdfasdf', '2026-11-07 12:32:10', '2026-11-07 12:32:10'),
(10, 1, 1, 3, 'ENTRADA', 12.000, 12.00, 144.00, 2, 'Cupom Fiscal - asdfasdf', '', 'Eduardo Martins', 'Entrada de estoque', 'asdfasdf', '2026-11-07 12:32:39', '2026-11-07 12:32:39'),
(11, 1, 2, 3, 'SAIDA', 5.000, NULL, NULL, NULL, 'doc01', 'TI', 'Orlando Milfort', 'Venda', 'observacoes da saida 1', '2026-11-07 13:35:29', '2026-11-07 13:35:29'),
(12, 2, 4, 7, 'ENTRADA', 20.000, 20.00, 400.00, 3, 'Nota Fiscal - asdfasdfasdf', '', 'Admin 1 Empresa Teste 2', 'Entrada de estoque', 'asdfasdfsdf', '2026-11-07 14:39:18', '2026-11-07 14:39:18'),
(13, 2, 4, 7, 'SAIDA', 20.000, NULL, NULL, NULL, 'asdfasdf', 'CCVE', 'Rodrigo', 'Consumo', 'observacoes que Rodrigo colocar', '2026-11-07 14:40:33', '2026-11-07 14:40:33');

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
(2, 'Operador', 'Cadastro e movimentação de estoque', 1),
(3, 'Administrador Geral', 'Gerencia todas as empresas e acessos globais', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `produtos` (`id`, `empresa_id`, `categoria_id`, `codigo`, `nome`, `descricao`, `unidade`, `estoque_atual`, `estoque_minimo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 1, 'PROD2EMPTESTE', 'PROD2EMPTESTE', 'Resma de papel A4', 'PCT', 12.000, 10.000, 1, '2026-11-05 20:05:24', '2026-11-07 12:32:39'),
(2, 1, 1, 'PROD1EMPTESTE', 'PROD1EMPTESTE', 'Caixa de canetas BIC preta', 'CX', 5.000, 2.000, 1, '2026-11-05 20:06:28', '2026-11-07 13:35:29'),
(4, 2, 6, 'PROD1EMPTESTE2', 'PROD1EMPTESTE2', '', 'UN', 0.000, 0.000, 1, '2026-11-07 00:52:08', '2026-11-07 14:40:33'),
(5, 2, 6, 'PROD2EMPTESTE2', 'PROD2EMPTESTE2', 'PROD2EMPTESTE2', 'UN', 0.000, 0.000, 1, '2026-11-07 00:52:31', '2026-11-07 00:52:31'),
(6, 3, 8, 'PROD1EMPTESTE3', 'PROD1EMPTESTE3', 'PROD1EMPTESTE3', 'UN', 0.000, 0.000, 1, '2026-11-07 01:08:50', '2026-11-07 01:08:50'),
(7, 3, 8, 'PROD2EMPTESTE3', 'PROD2EMPTESTE3', 'PROD2EMPTESTE3', 'UN', 0.000, 0.000, 1, '2026-11-07 01:09:03', '2026-11-07 01:09:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `perfil_id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
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

INSERT INTO `usuarios` (`id`, `perfil_id`, `empresa_id`, `nome`, `email`, `senha`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 3, NULL, 'fabio', 'fabio.veiga@live.com', '$2y$10$xRTYxXvJeF6lD6.M82UZUO.tr/EyFcSrXVZKJW06v0nzI5L6R4Wsu', 1, '2026-11-05 19:15:37', '2026-11-06 22:15:19'),
(2, 2, 1, 'USR1EMPTESTE', 'usr1@empteste.com', '$2y$10$9B4sZjITcI.l3906BrRuheTBF6xSMbGzPgah8CEXxeAs76I3Xj/t6', 1, '2026-11-05 20:33:04', '2026-11-07 01:25:44'),
(3, 1, 1, 'Eduardo Martins', 'eduardo@empresateste.com', '$2y$10$xXV4gFwLPkVReXUGCD9bV.Q2saPx7pFGRFbMjtopkXzvMDYgyQE7u', 1, '2026-11-05 20:50:42', '2026-11-07 01:29:24'),
(4, 1, 3, 'Admin 1 Empresa Teste 3', 'admin1@empresateste3.com', '$2y$10$R7kkTM/I7T3EKV1brIewrOy99fuGzo46bnPwwHSdgbgJnwfKoNOLe', 1, '2026-11-07 00:43:48', '2026-11-07 01:46:16'),
(5, 2, 3, 'Operador 1 Empresa Teste 3', 'op1@empresateste3.com', '$2y$10$xBlSQpLkNw8Gl065lDpFg.0vz1cvPVzMwRu5dImuvLNzBzXOVsGwO', 1, '2026-11-07 00:46:25', '2026-11-07 01:47:43'),
(6, 2, 2, 'Operador empresa teste 2', 'op1@empresateste2.com', '$2y$10$qnQ01ySIIBt82.SSBt6JyOPkBw8NdBetEV6363hujbNvPsZVIikje', 1, '2026-11-07 01:12:00', '2026-11-07 01:49:22'),
(7, 1, 2, 'Admin 1 Empresa Teste 2', 'admin1@empresateste2.com', '$2y$10$NvBNeEKbPYy82JNCShh7w.62g32a.jvlfWp2xE722WSZyJ/7qD42e', 1, '2026-11-07 01:13:55', '2026-11-07 01:44:40'),
(8, 2, 2, 'Operador 2 Empresa Teste', 'op2@empresateste2.com', '$2y$10$HB79YPq7P7TyUAoLsCEbu.aDd9iBqiiI3Lsf73wBhEW9SYKwXc/Ca', 1, '2026-11-07 01:15:31', '2026-11-07 01:50:02'),
(9, 2, 1, 'USR2EMPTESTE', 'usr2@empteste.com', '$2y$10$IVHFBKV2c2X5WwQuXWaln.mBQNfOoUJ/1siAgcRIC2/vI8ZQhgTX6', 1, '2026-11-07 01:26:28', '2026-11-07 01:26:28'),
(10, 1, 1, 'Admin 2', 'admin2@empresateste.com', '$2y$10$hV7WTDvgb9KQDmmOQTEQme6XtMkbi23ywKKgDn67V.jVaZJhBR/3a', 1, '2026-11-07 01:37:38', '2026-11-07 01:37:38'),
(11, 1, 2, 'Admin 2 Empresa Teste 2', 'admin2@empresateste2.com', '$2y$10$OKZ1f2VULgz3AlyGRnbYzO/ApXvd8cXXU4GmtpKas6XynvVV6sy6i', 1, '2026-11-07 01:45:00', '2026-11-07 01:45:00'),
(12, 1, 3, 'Admin 2 Empresa Teste 3', 'admin2@empresateste3.com', '$2y$10$p337VKVZCPUtU8TasGAo1eiSKP3XO8xb4sjwNhpzbUbC.rNtg4o5a', 1, '2026-11-07 01:47:01', '2026-11-07 01:47:01'),
(13, 2, 3, 'Operador 2 Empresa Teste 3', 'op2@empresateste3.com', '$2y$10$HwMQbjmrwu8ukrDV21Yd/.e.5hKxADmixBZrc0gmbpgk6pFshrKq2', 1, '2026-11-07 01:48:10', '2026-11-07 01:48:10');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categorias_empresa_nome` (`empresa_id`,`nome`),
  ADD UNIQUE KEY `uk_categorias_id_empresa` (`id`,`empresa_id`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_empresas_nome` (`nome`);

--
-- Índices de tabela `entradas_estoque`
--
ALTER TABLE `entradas_estoque`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_entradas_id_empresa` (`id`,`empresa_id`),
  ADD KEY `idx_entrada_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_entrada_usuario` (`usuario_id`),
  ADD KEY `idx_entrada_data` (`data_entrada`),
  ADD KEY `idx_entradas_empresa_fornecedor` (`empresa_id`,`fornecedor_id`),
  ADD KEY `fk_entradas_fornecedor_empresa` (`fornecedor_id`,`empresa_id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fornecedores_empresa_documento` (`empresa_id`,`cpf_cnpj`),
  ADD UNIQUE KEY `uk_fornecedores_id_empresa` (`id`,`empresa_id`),
  ADD KEY `idx_fornecedores_razao_social` (`razao_social`),
  ADD KEY `idx_fornecedores_status` (`status`),
  ADD KEY `idx_fornecedores_empresa` (`empresa_id`);

--
-- Índices de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_entrada` (`entrada_id`),
  ADD KEY `idx_item_produto` (`produto_id`),
  ADD KEY `idx_itens_empresa_entrada` (`empresa_id`,`entrada_id`),
  ADD KEY `idx_itens_empresa_produto` (`empresa_id`,`produto_id`),
  ADD KEY `fk_itens_entrada_empresa` (`entrada_id`,`empresa_id`),
  ADD KEY `fk_itens_produto_empresa` (`produto_id`,`empresa_id`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimentacoes_produto` (`produto_id`),
  ADD KEY `idx_movimentacoes_usuario` (`usuario_id`),
  ADD KEY `idx_movimentacoes_tipo` (`tipo`),
  ADD KEY `idx_movimentacoes_data` (`data_movimentacao`),
  ADD KEY `idx_movimentacoes_produto_data` (`produto_id`,`data_movimentacao`),
  ADD KEY `idx_movimentacoes_empresa_produto` (`empresa_id`,`produto_id`),
  ADD KEY `idx_movimentacoes_empresa_fornecedor` (`empresa_id`,`fornecedor_id`),
  ADD KEY `fk_movimentacoes_produto_empresa` (`produto_id`,`empresa_id`),
  ADD KEY `fk_movimentacoes_fornecedor_empresa` (`fornecedor_id`,`empresa_id`);

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
  ADD UNIQUE KEY `uk_produtos_empresa_codigo` (`empresa_id`,`codigo`),
  ADD UNIQUE KEY `uk_produtos_id_empresa` (`id`,`empresa_id`),
  ADD KEY `idx_produtos_nome` (`nome`),
  ADD KEY `idx_produtos_categoria` (`categoria_id`),
  ADD KEY `idx_produtos_ativo` (`ativo`),
  ADD KEY `idx_produtos_empresa_categoria` (`empresa_id`,`categoria_id`),
  ADD KEY `fk_produtos_categoria_empresa` (`categoria_id`,`empresa_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_perfis` (`perfil_id`),
  ADD KEY `idx_usuarios_empresa` (`empresa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `entradas_estoque`
--
ALTER TABLE `entradas_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `itens_entrada`
--
ALTER TABLE `itens_entrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `entradas_estoque`
--
ALTER TABLE `entradas_estoque`
  ADD CONSTRAINT `fk_entradas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entradas_fornecedor_empresa` FOREIGN KEY (`fornecedor_id`,`empresa_id`) REFERENCES `fornecedores` (`id`, `empresa_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD CONSTRAINT `fk_fornecedores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `itens_entrada`
--
ALTER TABLE `itens_entrada`
  ADD CONSTRAINT `fk_itens_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itens_entrada_empresa` FOREIGN KEY (`entrada_id`,`empresa_id`) REFERENCES `entradas_estoque` (`id`, `empresa_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itens_produto_empresa` FOREIGN KEY (`produto_id`,`empresa_id`) REFERENCES `produtos` (`id`, `empresa_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `fk_movimentacoes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_fornecedor_empresa` FOREIGN KEY (`fornecedor_id`,`empresa_id`) REFERENCES `fornecedores` (`id`, `empresa_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_produto_empresa` FOREIGN KEY (`produto_id`,`empresa_id`) REFERENCES `produtos` (`id`, `empresa_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produtos_categoria_empresa` FOREIGN KEY (`categoria_id`,`empresa_id`) REFERENCES `categorias` (`id`, `empresa_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produtos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_perfis` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
