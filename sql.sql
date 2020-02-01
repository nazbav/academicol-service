CREATE TABLE IF NOT EXISTS `peers` (
  `peer_id` int(11) NOT NULL COMMENT 'Код получателя',
  `tag` varchar(255) NOT NULL COMMENT 'Тег для поиска',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -- VK, 0 -- Никуда'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces` (
  `id` int(11) NOT NULL,
  `replace_file` int(11) NOT NULL COMMENT 'Файл замен',
  `tag` varchar(255) NOT NULL COMMENT 'Тег для поиска'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL COMMENT 'Код задачи',
  `peer_id` int(11) NOT NULL COMMENT 'Код получателя',
  `replace_id` int(11) NOT NULL COMMENT 'Код файла замен'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `replaces_files` (
  `id` int(11) NOT NULL COMMENT 'Код файла замен',
  `name` varchar(255) NOT NULL COMMENT 'Имя файла замен',
  `date` int(11) NOT NULL COMMENT 'Дата вступления замены',
  `url` varchar(255) NOT NULL COMMENT 'Адрес файла замен'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `peers` (`peer_id`, `tag`, `type`) VALUES
(153143942, '2ПКС-11,3ПКС-9', 1),
(157410260, '2ПКС-11,3ПКС-9', 1),
(211984675, '2ЗИО-11,3ЗИО-9', 1),
(452444965, '2ПКС-11,3ПКС-9', 1);

INSERT INTO `replaces` (`id`, `replace_file`, `tag`) VALUES
(1, 5, 'Пучкин А.В.'),
(2, 5, '1ЗИО-9'),
(3, 5, '1ПСО-9'),
(4, 5, '1ПД-9'),
(5, 5, '1ТОП-9'),
(6, 5, '1К-9'),
(7, 5, '1Б-9,1БД-9'),
(8, 5, '2ЗИО-11,3ЗИО-9'),
(9, 5, '3ТОП-11,4ТОП-9'),
(10, 6, 'Зданович Г.В.'),
(11, 6, '1ПД-11,2ПД-9'),
(12, 6, '3ТОП-11,4ТОП-9');

INSERT INTO `replaces_files` (`id`, `name`, `date`, `url`) VALUES
(1, '20 января 2020', 1579550340, 'https://vk.cc/ag7OCK'),
(2, '18 января 2020', 1579377540, 'https://vk.cc/ag7ODO');



--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `peers`
--
ALTER TABLE `peers`
  ADD PRIMARY KEY (`peer_id`);

--
-- Индексы таблицы `replaces`
--
ALTER TABLE `replaces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `replace_file` (`replace_file`);

--
-- Индексы таблицы `replaces_files`
--
ALTER TABLE `replaces_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_3` (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `id_2` (`id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peer_id` (`peer_id`),
  ADD KEY `replace_id` (`replace_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `replaces`
--
ALTER TABLE `replaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `replaces_files`
--
ALTER TABLE `replaces_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `replaces`
--
ALTER TABLE `replaces`
  ADD CONSTRAINT `replaces_ibfk_1` FOREIGN KEY (`replace_file`) REFERENCES `replaces_files` (`id`);

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`replace_id`) REFERENCES `replaces_files` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`peer_id`) REFERENCES `peers` (`peer_id`);