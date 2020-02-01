CREATE TABLE IF NOT EXISTS `peers` (
  `peer_id` int(11) NOT NULL COMMENT 'Êîä ïîëó÷àòåëÿ',
  `tag` varchar(255) NOT NULL COMMENT 'Òåã äëÿ ïîèñêà',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -- VK, 0 -- Íèêóäà'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces` (
  `id` int(11) NOT NULL,
  `replace_file` int(11) NOT NULL COMMENT 'Ôàéë çàìåí',
  `tag` varchar(255) NOT NULL COMMENT 'Òåã äëÿ ïîèñêà'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL COMMENT 'Êîä çàäà÷è',
  `peer_id` int(11) NOT NULL COMMENT 'Êîä ïîëó÷àòåëÿ',
  `replace_id` int(11) NOT NULL COMMENT 'Êîä ôàéëà çàìåí'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces_files` (
  `id` int(11) NOT NULL COMMENT 'Êîä ôàéëà çàìåí',
  `name` varchar(255) NOT NULL COMMENT 'Èìÿ ôàéëà çàìåí',
  `date` int(11) NOT NULL COMMENT 'Äàòà âñòóïëåíèÿ çàìåíû',
  `url` varchar(255) NOT NULL COMMENT 'Àäðåñ ôàéëà çàìåí'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Èíäåêñû ñîõðàí¸ííûõ òàáëèö
--

--
-- Èíäåêñû òàáëèöû `peers`
--
ALTER TABLE `peers`
  ADD PRIMARY KEY (`peer_id`);

--
-- Èíäåêñû òàáëèöû `replaces`
--
ALTER TABLE `replaces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `replace_file` (`replace_file`);

--
-- Èíäåêñû òàáëèöû `replaces_files`
--
ALTER TABLE `replaces_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_3` (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `id_2` (`id`);

--
-- Èíäåêñû òàáëèöû `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peer_id` (`peer_id`),
  ADD KEY `replace_id` (`replace_id`);

--
-- AUTO_INCREMENT äëÿ ñîõðàí¸ííûõ òàáëèö
--

--
-- AUTO_INCREMENT äëÿ òàáëèöû `replaces`
--
ALTER TABLE `replaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT äëÿ òàáëèöû `replaces_files`
--
ALTER TABLE `replaces_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT äëÿ òàáëèöû `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- Îãðàíè÷åíèÿ âíåøíåãî êëþ÷à ñîõðàíåííûõ òàáëèö
--

--
-- Îãðàíè÷åíèÿ âíåøíåãî êëþ÷à òàáëèöû `replaces`
--
ALTER TABLE `replaces`
  ADD CONSTRAINT `replaces_ibfk_1` FOREIGN KEY (`replace_file`) REFERENCES `replaces_files` (`id`);

--
-- Îãðàíè÷åíèÿ âíåøíåãî êëþ÷à òàáëèöû `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`replace_id`) REFERENCES `replaces_files` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`peer_id`) REFERENCES `peers` (`peer_id`);

