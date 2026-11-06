-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/11/2026 às 21:44
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `unirota`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `caronas`
--

CREATE TABLE `caronas` (
  `id_carona` int(11) NOT NULL,
  `id_motorista` int(11) NOT NULL,
  `id_veiculo` int(11) DEFAULT NULL,
  `origem` varchar(150) NOT NULL,
  `bairro_origem` varchar(100) NOT NULL,
  `destino` varchar(150) NOT NULL,
  `bairro_destino` varchar(100) NOT NULL,
  `cidade_origem` varchar(100) NOT NULL,
  `cidade_destino` varchar(100) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  `vagas` int(11) NOT NULL,
  `preco` decimal(8,2) NOT NULL,
  `distancia` decimal(8,2) NOT NULL,
  `somente_mulheres` tinyint(1) DEFAULT 0,
  `status` enum('disponivel','lotada','cancelada','finalizada') DEFAULT 'disponivel',
  `data_publicacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `carteiras`
--

CREATE TABLE `carteiras` (
  `id_carteira` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `saldo` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas`
--

CREATE TABLE `reservas` (
  `id_reserva` int(11) NOT NULL,
  `id_carona` int(11) NOT NULL,
  `id_passageiro` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor_total` decimal(8,2) NOT NULL,
  `status` enum('pendente','confirmada','cancelada','concluida') DEFAULT 'pendente',
  `data_reserva` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id_transacao` int(11) NOT NULL,
  `id_carteira` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `tipo` enum('pagamento','recebimento','estorno') NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('pendente','concluida','cancelada') DEFAULT 'pendente',
  `data_transacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `curso` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) DEFAULT 'Araçatuba',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `tipo` enum('passageiro','motorista','ambos') DEFAULT 'ambos',
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id_veiculo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `placa` varchar(10) NOT NULL,
  `capacidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `caronas`
--
ALTER TABLE `caronas`
  ADD PRIMARY KEY (`id_carona`),
  ADD KEY `id_motorista` (`id_motorista`),
  ADD KEY `id_veiculo` (`id_veiculo`);

--
-- Índices de tabela `carteiras`
--
ALTER TABLE `carteiras`
  ADD PRIMARY KEY (`id_carteira`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `id_carona` (`id_carona`,`id_passageiro`),
  ADD KEY `id_passageiro` (`id_passageiro`);

--
-- Índices de tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id_transacao`),
  ADD KEY `id_carteira` (`id_carteira`),
  ADD KEY `id_reserva` (`id_reserva`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id_veiculo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caronas`
--
ALTER TABLE `caronas`
  MODIFY `id_carona` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `carteiras`
--
ALTER TABLE `carteiras`
  MODIFY `id_carteira` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id_transacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id_veiculo` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `caronas`
--
ALTER TABLE `caronas`
  ADD CONSTRAINT `caronas_ibfk_1` FOREIGN KEY (`id_motorista`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `caronas_ibfk_2` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id_veiculo`);

--
-- Restrições para tabelas `carteiras`
--
ALTER TABLE `carteiras`
  ADD CONSTRAINT `carteiras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`id_carona`) REFERENCES `caronas` (`id_carona`),
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`id_passageiro`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `transacoes`
--
ALTER TABLE `transacoes`
  ADD CONSTRAINT `transacoes_ibfk_1` FOREIGN KEY (`id_carteira`) REFERENCES `carteiras` (`id_carteira`),
  ADD CONSTRAINT `transacoes_ibfk_2` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`);

--
-- Restrições para tabelas `veiculos`
--
ALTER TABLE `veiculos`
  ADD CONSTRAINT `veiculos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
