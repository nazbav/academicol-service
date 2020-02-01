CREATE TABLE IF NOT EXISTS `peers` (
  `peer_id` int(11) NOT NULL COMMENT '��� ����������',
  `tag` varchar(255) NOT NULL COMMENT '��� ��� ������',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -- VK, 0 -- ������'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces` (
  `id` int(11) NOT NULL,
  `replace_file` int(11) NOT NULL COMMENT '���� �����',
  `tag` varchar(255) NOT NULL COMMENT '��� ��� ������'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL COMMENT '��� ������',
  `peer_id` int(11) NOT NULL COMMENT '��� ����������',
  `replace_id` int(11) NOT NULL COMMENT '��� ����� �����'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces_files` (
  `id` int(11) NOT NULL COMMENT '��� ����� �����',
  `name` varchar(255) NOT NULL COMMENT '��� ����� �����',
  `date` int(11) NOT NULL COMMENT '���� ���������� ������',
  `url` varchar(255) NOT NULL COMMENT '����� ����� �����'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `peers` (`peer_id`, `tag`, `type`) VALUES
(153143942, '2���-11,3���-9', 1),
(157410260, '2���-11,3���-9', 1),
(211984675, '2���-11,3���-9', 1),
(452444965, '2���-11,3���-9', 1);

INSERT INTO `replaces` (`id`, `replace_file`, `tag`) VALUES
(1, 5, '������ �.�.'),
(2, 5, '1���-9'),
(3, 5, '1���-9'),
(4, 5, '1��-9'),
(5, 5, '1���-9'),
(6, 5, '1�-9'),
(7, 5, '1�-9,1��-9'),
(8, 5, '2���-11,3���-9'),
(9, 5, '3���-11,4���-9'),
(10, 6, '�������� �.�.'),
(11, 6, '1��-11,2��-9'),
(12, 6, '3���-11,4���-9');

INSERT INTO `replaces_files` (`id`, `name`, `date`, `url`) VALUES
(1, '20 ������ 2020', 1579550340, 'https://vk.cc/ag7OCK'),
(2, '18 ������ 2020', 1579377540, 'https://vk.cc/ag7ODO');



--
-- ������� ���������� ������
--

--
-- ������� ������� `peers`
--
ALTER TABLE `peers`
  ADD PRIMARY KEY (`peer_id`);

--
-- ������� ������� `replaces`
--
ALTER TABLE `replaces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `replace_file` (`replace_file`);

--
-- ������� ������� `replaces_files`
--
ALTER TABLE `replaces_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_3` (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `id_2` (`id`);

--
-- ������� ������� `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peer_id` (`peer_id`),
  ADD KEY `replace_id` (`replace_id`);

--
-- AUTO_INCREMENT ��� ���������� ������
--

--
-- AUTO_INCREMENT ��� ������� `replaces`
--
ALTER TABLE `replaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT ��� ������� `replaces_files`
--
ALTER TABLE `replaces_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT ��� ������� `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- ����������� �������� ����� ����������� ������
--

--
-- ����������� �������� ����� ������� `replaces`
--
ALTER TABLE `replaces`
  ADD CONSTRAINT `replaces_ibfk_1` FOREIGN KEY (`replace_file`) REFERENCES `replaces_files` (`id`);

--
-- ����������� �������� ����� ������� `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`replace_id`) REFERENCES `replaces_files` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`peer_id`) REFERENCES `peers` (`peer_id`);