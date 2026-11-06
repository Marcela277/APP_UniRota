-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/11/2026 às 12:53
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

--
-- Despejando dados para a tabela `caronas`
--

INSERT INTO `caronas` (`id_carona`, `id_motorista`, `id_veiculo`, `origem`, `bairro_origem`, `destino`, `bairro_destino`, `cidade_origem`, `cidade_destino`, `latitude`, `longitude`, `data_hora`, `vagas`, `preco`, `distancia`, `somente_mulheres`, `status`, `data_publicacao`) VALUES
(1, 1, 1, 'Av.Ibirapuera, 1036', 'Palmeiras', 'Av. Prestes Maia', 'FATEC', 'Araçatuba', 'Araçatuba', NULL, NULL, '2026-11-09 07:10:00', 3, 3.52, 20.00, 0, 'disponivel', '2026-11-06 08:30:26'),
(5, 8, 7, 'Rua principal do Jardim Nova Yorque', 'Jardim Nova Yorque', 'Entrada do Unisalesiano', 'Centro', 'Araçatuba', 'Araçatuba', -21.1932000, -50.4235000, '2026-11-07 18:30:00', 2, 5.00, 6.80, 1, 'disponivel', '2026-11-06 08:49:40'),
(6, 9, 8, 'Praça do bairro Concórdia', 'Concórdia', 'Entrada do UniToledo', 'Jardim Umuarama', 'Araçatuba', 'Araçatuba', -21.2231000, -50.4189000, '2026-11-09 07:15:00', 3, 4.50, 6.30, 0, 'disponivel', '2026-11-06 08:49:40'),
(7, 9, 8, 'Praça do bairro Concórdia', 'Concórdia', 'Portaria da FATEC Araçatuba', 'Centro', 'Araçatuba', 'Araçatuba', -21.2231000, -50.4189000, '2026-11-08 18:45:00', 3, 4.50, 4.10, 0, 'disponivel', '2026-11-06 08:49:40'),
(8, 10, 9, 'Terminal rodoviário de Birigui', 'Centro', 'Portaria principal da UNESP', 'Dona Amélia', 'Birigui', 'Araçatuba', -21.2889000, -50.3400000, '2026-11-08 06:30:00', 3, 12.00, 28.00, 0, 'disponivel', '2026-11-06 08:49:40'),
(9, 11, 10, 'Praça central de Penápolis', 'Centro', 'Entrada do UniToledo', 'Jardim Umuarama', 'Penápolis', 'Araçatuba', -21.4139000, -50.0772000, '2026-11-07 06:00:00', 4, 18.00, 45.00, 0, 'disponivel', '2026-11-06 08:49:40'),
(10, 7, 11, 'Portaria principal da UNESP', 'Dona Amélia', 'Praça da Vila Mendonça', 'Vila Mendonça', 'Araçatuba', 'Araçatuba', -21.1987000, -50.4496000, '2026-11-07 12:30:00', 2, 4.00, 5.20, 0, 'disponivel', '2026-11-06 08:49:40'),
(11, 7, 11, 'Praça da Vila Mendonça', 'Vila Mendonça', 'Portaria principal da UNESP', 'Dona Amélia', 'Araçatuba', 'Araçatuba', -21.2145000, -50.4401000, '2026-11-07 07:00:00', 3, 4.00, 5.20, 0, 'disponivel', '2026-11-06 08:49:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `carteiras`
--

CREATE TABLE `carteiras` (
  `id_carteira` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `saldo` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `carteiras`
--

INSERT INTO `carteiras` (`id_carteira`, `id_usuario`, `saldo`) VALUES
(1, 1, 0.00),
(9, 8, 0.00),
(10, 9, 0.00),
(11, 10, 0.00),
(12, 11, 0.00),
(13, 7, 0.00);

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

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nome`, `email`, `senha`, `telefone`, `curso`, `bairro`, `cidade`, `latitude`, `longitude`, `tipo`, `data_cadastro`) VALUES
(1, 'Marcela', 'marcela@gmail.com', '$2y$10$YPYdnpJdGeQSYShUzL65lul028S2DW2mPEITKST2HQ9mnMV1aPnha', '18999999999', 'ADS', 'Palmeiras', 'Araçatuba', NULL, NULL, 'ambos', '2026-11-06 08:06:50'),
(7, 'Rafael Almeida', 'rafael@exemplo.unirota', '$2b$12$Hp9QjC7Vwvvev6uIPdYzp.lSeAMNScrF0l4N7LUajjyr4hhbtIGuy', '(18) 99111-0001', 'Engenharia Civil', 'Vila Mendonça', 'Araçatuba', NULL, NULL, 'motorista', '2026-11-06 08:49:39'),
(8, 'Juliana Costa', 'juliana@exemplo.unirota', '$2b$12$Hp9QjC7Vwvvev6uIPdYzp.lSeAMNScrF0l4N7LUajjyr4hhbtIGuy', '(18) 99111-0002', 'Enfermagem', 'Jardim Nova Yorque', 'Araçatuba', NULL, NULL, 'motorista', '2026-11-06 08:49:39'),
(9, 'Lucas Martins', 'lucas@exemplo.unirota', '$2b$12$Hp9QjC7Vwvvev6uIPdYzp.lSeAMNScrF0l4N7LUajjyr4hhbtIGuy', '(18) 99111-0003', 'Ciência da Computação', 'Concórdia', 'Araçatuba', NULL, NULL, 'motorista', '2026-11-06 08:49:39'),
(10, 'Mariana Souza', 'mariana@exemplo.unirota', '$2b$12$Hp9QjC7Vwvvev6uIPdYzp.lSeAMNScrF0l4N7LUajjyr4hhbtIGuy', '(18) 99111-0004', 'Medicina Veterinária', 'Centro', 'Birigui', NULL, NULL, 'motorista', '2026-11-06 08:49:39'),
(11, 'Pedro Henrique', 'pedro@exemplo.unirota', '$2b$12$Hp9QjC7Vwvvev6uIPdYzp.lSeAMNScrF0l4N7LUajjyr4hhbtIGuy', '(18) 99111-0005', 'Administração', 'Centro', 'Penápolis', NULL, NULL, 'motorista', '2026-11-06 08:49:39');

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
-- Despejando dados para a tabela `veiculos`
--

INSERT INTO `veiculos` (`id_veiculo`, `id_usuario`, `modelo`, `cor`, `placa`, `capacidade`) VALUES
(1, 1, 'Ford Ka', 'Prata', 'HNC5J624', 4),
(7, 8, 'Chevrolet Onix', 'Branco', 'EXB2C34', 4),
(8, 9, 'VW Gol', 'Preto', 'EXC3D45', 4),
(9, 10, 'Hyundai HB20', 'Vermelho', 'EXD4E56', 4),
(10, 11, 'Toyota Corolla', 'Cinza', 'EXE5F67', 5),
(11, 7, 'Fiat Argo', 'Prata', 'EXA1B23', 4);

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
  MODIFY `id_carona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `carteiras`
--
ALTER TABLE `carteiras`
  MODIFY `id_carteira` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id_veiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
