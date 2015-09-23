

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `folders` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `path` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_files_to_folders` (`folder_id`);


ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_folders_to_parent_folders` (`parent_id`);


ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `files`
  ADD CONSTRAINT `FK_files_to_folders` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `folders`
  ADD CONSTRAINT `FK_folders_to_parent_folders` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

