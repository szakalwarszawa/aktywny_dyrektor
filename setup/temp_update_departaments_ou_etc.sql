update departament
set ouAD = concat('OU=', shortname, ',OU=Zespoly') where nowaStruktura = 0;
update departament
set ouAD = concat('OU=', shortname, ',OU=Zespoly 2016') where nowaStruktura = 1;

update wniosek_status
set viewers = 'wnioskodawca,podmiot,przelozony,wlasciciel,administrator,techniczny';


delete from acl_role where name like 'PARP_WNIOSEK_WIDZI_WSZYSTKICH';
INSERT INTO `acl_role` (`deletedAt`, `name`, `opis`) VALUES
(NULL, 'PARP_WNIOSEK_WIDZI_WSZYSTKICH', 'tylko ta rola widzi wszystkich pracowników PARP przy składaniu wniosku o uprawnienia');