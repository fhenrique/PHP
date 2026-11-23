
-- BACKUP DA ESTRUTURA DO ESTOQUEWEB
-- Banco: u248075441_estoqueweb
-- Data: 16/11/2026 10:46:01

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

-- TABELA: empresas
DROP TABLE IF EXISTS `empresas`;
CREATE TABLE `empresas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresas_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: perfis
DROP TABLE IF EXISTS `perfis`;
CREATE TABLE `perfis` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: categorias
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categorias_empresa_nome` (`empresa_id`,`nome`),
  UNIQUE KEY `uk_categorias_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_categorias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: fornecedores
DROP TABLE IF EXISTS `fornecedores`;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
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
  `data_atualizacao` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fornecedores_empresa_documento` (`empresa_id`,`cpf_cnpj`),
  UNIQUE KEY `uk_fornecedores_id_empresa` (`id`,`empresa_id`),
  KEY `idx_fornecedores_razao_social` (`razao_social`),
  KEY `idx_fornecedores_status` (`status`),
  KEY `idx_fornecedores_empresa` (`empresa_id`),
  CONSTRAINT `fk_fornecedores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `perfil_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuarios_perfis` (`perfil_id`),
  KEY `idx_usuarios_empresa` (`empresa_id`),
  CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_perfis` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: produtos
DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `categoria_id` int(10) unsigned NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `ncm` varchar(8) DEFAULT NULL COMMENT 'NCM - 8 dígitos',
  `cst_ibs_cbs` char(3) DEFAULT NULL COMMENT 'CST IBS/CBS - 3 dígitos',
  `cclasstrib` char(6) DEFAULT NULL COMMENT 'cClassTrib - 6 dígitos',
  `aliquota_cbs` decimal(5,2) DEFAULT NULL,
  `aliquota_ibs` decimal(5,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `unidade` varchar(20) NOT NULL DEFAULT 'UN',
  `estoque_atual` decimal(12,3) NOT NULL DEFAULT 0.000,
  `estoque_minimo` decimal(12,3) NOT NULL DEFAULT 0.000,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_produtos_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uk_produtos_id_empresa` (`id`,`empresa_id`),
  KEY `idx_produtos_nome` (`nome`),
  KEY `idx_produtos_categoria` (`categoria_id`),
  KEY `idx_produtos_ativo` (`ativo`),
  KEY `idx_produtos_empresa_categoria` (`empresa_id`,`categoria_id`),
  KEY `fk_produtos_categoria_empresa` (`categoria_id`,`empresa_id`),
  CONSTRAINT `fk_produtos_categoria_empresa` FOREIGN KEY (`categoria_id`, `empresa_id`) REFERENCES `categorias` (`id`, `empresa_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_produtos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: entradas_estoque
DROP TABLE IF EXISTS `entradas_estoque`;
CREATE TABLE `entradas_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_entrada` date NOT NULL,
  `tipo_documento` varchar(30) NOT NULL,
  `numero_documento` varchar(50) NOT NULL,
  `valor_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `cfop` char(4) DEFAULT NULL COMMENT 'Código Fiscal de Operações e Prestações',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entradas_id_empresa` (`id`,`empresa_id`),
  KEY `idx_entrada_fornecedor` (`fornecedor_id`),
  KEY `idx_entrada_usuario` (`usuario_id`),
  KEY `idx_entrada_data` (`data_entrada`),
  KEY `idx_entradas_empresa_fornecedor` (`empresa_id`,`fornecedor_id`),
  KEY `fk_entradas_fornecedor_empresa` (`fornecedor_id`,`empresa_id`),
  CONSTRAINT `fk_entradas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_entradas_fornecedor_empresa` FOREIGN KEY (`fornecedor_id`, `empresa_id`) REFERENCES `fornecedores` (`id`, `empresa_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: itens_entrada
DROP TABLE IF EXISTS `itens_entrada`;
CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(10) unsigned NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `valor_unitario` decimal(12,2) NOT NULL,
  `valor_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_entrada` (`entrada_id`),
  KEY `idx_item_produto` (`produto_id`),
  KEY `idx_itens_empresa_entrada` (`empresa_id`,`entrada_id`),
  KEY `idx_itens_empresa_produto` (`empresa_id`,`produto_id`),
  KEY `fk_itens_entrada_empresa` (`entrada_id`,`empresa_id`),
  KEY `fk_itens_produto_empresa` (`produto_id`,`empresa_id`),
  CONSTRAINT `fk_itens_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_entrada_empresa` FOREIGN KEY (`entrada_id`, `empresa_id`) REFERENCES `entradas_estoque` (`id`, `empresa_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_produto_empresa` FOREIGN KEY (`produto_id`, `empresa_id`) REFERENCES `produtos` (`id`, `empresa_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: movimentacoes
DROP TABLE IF EXISTS `movimentacoes`;
CREATE TABLE `movimentacoes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `produto_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_movimentacoes_produto` (`produto_id`),
  KEY `idx_movimentacoes_usuario` (`usuario_id`),
  KEY `idx_movimentacoes_tipo` (`tipo`),
  KEY `idx_movimentacoes_data` (`data_movimentacao`),
  KEY `idx_movimentacoes_produto_data` (`produto_id`,`data_movimentacao`),
  KEY `idx_movimentacoes_empresa_produto` (`empresa_id`,`produto_id`),
  KEY `idx_movimentacoes_empresa_fornecedor` (`empresa_id`,`fornecedor_id`),
  KEY `fk_movimentacoes_produto_empresa` (`produto_id`,`empresa_id`),
  KEY `fk_movimentacoes_fornecedor_empresa` (`fornecedor_id`,`empresa_id`),
  CONSTRAINT `fk_movimentacoes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimentacoes_fornecedor_empresa` FOREIGN KEY (`fornecedor_id`, `empresa_id`) REFERENCES `fornecedores` (`id`, `empresa_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimentacoes_produto_empresa` FOREIGN KEY (`produto_id`, `empresa_id`) REFERENCES `produtos` (`id`, `empresa_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimentacoes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABELA: config_fiscal
DROP TABLE IF EXISTS `config_fiscal`;
CREATE TABLE `config_fiscal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `ano_vigencia` int(11) NOT NULL DEFAULT 2027,
  `aliquota_cbs_padrao` decimal(5,2) DEFAULT 11.30,
  `aliquota_ibs_padrao` decimal(5,2) DEFAULT 0.10,
  `cfop_padrao` char(4) DEFAULT '5102',
  `cst_ibs_cbs_padrao` char(3) DEFAULT '000',
  `cclasstrib_padrao` char(6) DEFAULT '000001',
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresa` (`empresa_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- FIM DO BACKUP DA ESTRUTURA
