
delete from acl_role where name = 'PARP_ADMIN_REJESTRU_ZASOBOW';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_ADMIN_REJESTRU_ZASOBOW', 'administrator rejestru zasobów');

delete from acl_role where name = 'PARP_WLASCICIEL_ZASOBOW';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_WLASCICIEL_ZASOBOW', 'właściciel zasobów');

delete from acl_role where name = 'PARP_ADMIN_ZASOBOW';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_ADMIN_ZASOBOW', 'administrator zasobów');


delete from acl_role where name = 'PARP_ADMIN_TECHNICZNY_ZASOBOW';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_ADMIN_TECHNICZNY_ZASOBOW', 'administrator techniczny zasobów');
