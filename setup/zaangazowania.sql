ALTER TABLE userengagement ADD kto_usunal VARCHAR(255) DEFAULT NULL, ADD kiedy_usuniety DATETIME DEFAULT NULL, ADD czy_nowy TINYINT(1) DEFAULT NULL;
ALTER TABLE userengagement CHANGE percent percent INT DEFAULT NULL;

INSERT INTO `acl_role` (`name`, `opis`) VALUES ('PARP_HELPDESK_BI', 'helpdesk Biura Informatyki');
INSERT INTO `acl_role` (`name`, `opis`) VALUES ('PARP_KOMP', 'rola związana z procesem wydawania i odbierania sprzętu komputerowego');
INSERT INTO `acl_role` (`name`, `opis`) VALUES ('PARP_POWIERNIK_ZARZADU', 'otrzymuje maile o zmianie zaangażowania zamiast zarządu');
INSERT INTO `acl_role` (`name`, `opis`) VALUES ('PARP_IMPORT_ZAANGAZOWANIE', 'może zaimportować plik z zaangażowaniami');