delete from acl_user_role where role_id IN (select id from acl_role where name = 'PARP_NADZORCA_DOMEN');
delete from acl_role where name = 'PARP_NADZORCA_DOMEN';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_NADZORCA_DOMEN', 'rola mogąca zarządzać wnioskami o utworzenie domeny');


INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'kamil_jakacki');


INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'artur_marszalek')

/*

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'aleksandra_burzynska');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'ewa_czarnecka');
*/

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'leszek_czech');
/*

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'grazyna_czerwinska');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'elzbieta_demianiuk');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'karol_demski');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'karolina_dorywalska');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'anna_drozd');
*/

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'monika_dylag');
/*

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'katarzyna_jaroszewic');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'Agnieszka_jozefowicz');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'magdalena_ksiazek');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'marcin_olejnik');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'miroslawa_plyta');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'pawel_skowera');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'karolina_starzyk');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'piotr_tyrakowski');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'karolina_skornicka');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'konrad_zdanowski');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'marta_zielinska');

INSERT INTO `acl_user_role`(`role_id`, `deletedAt`, `samaccountname`) VALUES 
((select id from acl_role where name = 'PARP_NADZORCA_DOMEN'),null,'aneta_zielinska');
*/


DELETE FROM  `wniosek_status` where typWniosku = 'wniosekOUtworzenieZasobu'
and nazwaSystemowa in ('021_EDYCJA_NADZORCA_DOMEN');
INSERT INTO `wniosek_status` (`deletedAt`, `nazwa`, `nazwaSystemowa`, `opis`, `viewers`, `editors`, `finished`, `typWniosku`) VALUES
( NULL, 'W edycji u nadzorcy domen', '021_EDYCJA_NADZORCA_DOMEN', NULL, 'wnioskodawca,wlasciciel,administratorZasobow,administrator,nadzorcaDomen', 'nadzorcaDomen', 0, 'wniosekOUtworzenieZasobu');
