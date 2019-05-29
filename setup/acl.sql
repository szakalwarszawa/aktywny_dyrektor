
--
-- Baza danych: `lsi_aktywny`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `acl_action`
--

CREATE TABLE IF NOT EXISTS `acl_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deletedAt` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `skrot` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `opis` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=20 ;

--
-- Zrzut danych tabeli `acl_action`
--

INSERT INTO `acl_action` (`id`, `deletedAt`, `name`, `skrot`, `opis`) VALUES
(1, NULL, 'uruchamianie ad hoc importu informacji o zmianach kadrowych (RkD -> AkD -> MS-AD)', 'IMPORT_REKORD', 'uruchamianie ad hoc importu informacji o zmianach kadrowych (RkD -> AkD -> MS-AD)'),
(2, NULL, 'wprowadzanie informacji o zmianach kadrowych', 'INPUT_INFO_KADRY', 'wprowadzanie informacji o zmianach kadrowych'),
(3, NULL, 'uzupełnianie informacji o zmianach kadrowych', 'UPDATE_INFO_KADRY', 'uzupełnianie informacji o zmianach kadrowych'),
(4, NULL, 'modyfikacja danych w CZU (Centralna Zbiornica Uprawnień)', 'UPDATE_CZU', 'modyfikacja danych w CZU (Centralna Zbiornica Uprawnień)'),
(5, NULL, 'ustalanie czasu synchronizacji danych (RkD -> AkD -> MS-AD)', 'SYNCHRO_TIME', 'ustalanie czasu synchronizacji danych (RkD -> AkD -> MS-AD)'),
(6, NULL, 'odnotowanie (w AkD) nadania uprawnień', 'ADD_PRIVILEGE', 'odnotowanie (w AkD) nadania uprawnień'),
(7, NULL, 'odnotowanie odebrania uprawnień', 'REMOVE_PRIVILEGE', 'odnotowanie odebrania uprawnień'),
(8, NULL, 'import z ECM danych o nadaniu uprawnień', 'IMPORT_ECM_UPRAWNIENIA', 'import z ECM danych o nadaniu uprawnień'),
(9, NULL, 'odnotowanie przydzielenia sprzętu komputerowego', 'ADD_COMPUTER', 'odnotowanie przydzielenia sprzętu komputerowego'),
(10, NULL, 'uruchamianie raportów', 'REPORTS', 'uruchamianie raportów'),
(11, NULL, 'ograniczenie dostępu do danych dot. zasobu którego użytkownik nie obsługuje', 'DENY_RESOURCE', 'ograniczenie dostępu do danych dot. zasobu którego użytkownik nie obsługuje'),
(12, NULL, 'zgłaszanie zasobów do Rejestru Zasobów', 'ADD_RESOURCE', 'zgłaszanie zasobów do Rejestru Zasobów'),
(13, NULL, 'akceptacja zgłoszenia zasobu', 'ACCEPT_RESOURCE', 'akceptacja zgłoszenia zasobu'),
(14, NULL, 'odnotowanie realizacji wniosku zgłoszenia zasobu', 'RESOURCE_ACCEPTED', 'odnotowanie realizacji wniosku zgłoszenia zasobu'),
(15, NULL, 'import Rejestru Zasobów z ECM', 'IMPORT_ECM_RESOURCES', 'import Rejestru Zasobów z ECM'),
(16, NULL, 'odnotowanie wprowadzenia danych do Rejestru Zasobów', 'UPDATE_RESOURCES', 'odnotowanie wprowadzenia danych do Rejestru Zasobów'),
(17, NULL, 'ustalanie reguł czasowych i uruchamianie informacji o zadaniach', 'ZADANIA', 'ustalanie reguł czasowych i uruchamianie informacji o zadaniach'),
(18, NULL, 'modyfikacja poziomów dostępu (tej tabeli)', 'ACL_UPDATE', 'modyfikacja poziomów dostępu (tej tabeli)'),
(19, NULL, 'zarządzanie użytkownikami AkD', 'USER_MANAGEMENT', 'zarządzanie użytkownikami AkD');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `acl_role`
--

CREATE TABLE IF NOT EXISTS `acl_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deletedAt` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `opis` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=9 ;

--
-- Zrzut danych tabeli `acl_role`
--

INSERT INTO `acl_role` (`id`, `deletedAt`, `name`, `opis`) VALUES
(1, NULL, 'PARP_USER', 'obserwator/pracownik'),
(2, NULL, 'PARP_MANAGER', 'przełożony pracownika'),
(3, NULL, 'PARP_RESOURCE_OWNER', 'właściciel zasobu'),
(4, NULL, 'PARP_BZK_1', 'pracownik kadr 1'),
(5, NULL, 'PARP_BZK_2', 'pracownik kadr 2'),
(6, NULL, 'PARP_RESOURCE_ADMIN', 'administrator zasobu'),
(7, NULL, 'PARP_TECH_ADMIN', 'administrator techniczny'),
(8, NULL, 'PARP_ADMIN', 'administrator AkD');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `acl_role_action`
--

CREATE TABLE IF NOT EXISTS `acl_role_action` (
  `aclrole_id` int(11) NOT NULL,
  `aclaction_id` int(11) NOT NULL,
  PRIMARY KEY (`aclrole_id`,`aclaction_id`),
  KEY `IDX_81614D09746AF27F` (`aclrole_id`),
  KEY `IDX_81614D09B1F41654` (`aclaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Zrzut danych tabeli `acl_role_action`
--

INSERT INTO `acl_role_action` (`aclrole_id`, `aclaction_id`) VALUES
(1, 12),
(4, 3),
(5, 3),
(7, 19),
(8, 3);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `acl_user_role`
--

CREATE TABLE IF NOT EXISTS `acl_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL,
  `samaccountname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5AEDF096D60322AC` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Zrzut danych tabeli `acl_user_role`
--

INSERT INTO `acl_user_role` (`id`, `role_id`, `deletedAt`, `samaccountname`) VALUES
(1, 8, NULL, 'aktywny_dyrektor'),
(2, 4, NULL, 'aktywny_dyrektor'),
(3, 8, NULL, 'marcin_lipinski');

--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `acl_role_action`
--
ALTER TABLE `acl_role_action`
  ADD CONSTRAINT `FK_81614D09746AF27F` FOREIGN KEY (`aclrole_id`) REFERENCES `acl_role` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_81614D09B1F41654` FOREIGN KEY (`aclaction_id`) REFERENCES `acl_action` (`id`) ON DELETE CASCADE;

--
-- Ograniczenia dla tabeli `acl_user_role`
--
ALTER TABLE `acl_user_role`
  ADD CONSTRAINT `FK_5AEDF096D60322AC` FOREIGN KEY (`role_id`) REFERENCES `acl_role` (`id`);
