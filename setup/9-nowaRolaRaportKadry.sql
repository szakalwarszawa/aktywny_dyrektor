delete from acl_role where name = 'PARP_BZK_RAPORTY';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_BZK_RAPORTY', 'rola mogąca generować raporty kadrowe');