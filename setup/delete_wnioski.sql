delete from wniosek_historia_statusow;
delete from userzasoby where not wniosek_id is null;
delete from wniosek_editor;
delete from wniosek_viewer;
update wniosek set parent_id = null, WniosekNadanieOdebranieZasobow_id = null, wniosekUtworzenieZasobu_id = null;
update wniosek_nadanie_odebranie_zasobow set wniosek_id = null;
update wniosek_utworzenie_zasobu set wniosek_id = null;
update zasoby set wniosekUtworzenieZasobu_id = null,WniosekUtworzenieZasobuDoSkasowania_id = null;
delete from entry;
delete from wniosek;
delete from wniosek_nadanie_odebranie_zasobow;
delete from wniosek_utworzenie_zasobu;
delete from wniosekNumer;
delete from userzasoby;
ALTER TABLE wniosek_nadanie_odebranie_zasobow AUTO_INCREMENT = 1;
ALTER TABLE wniosek_utworzenie_zasobu AUTO_INCREMENT = 1;
ALTER TABLE wniosek AUTO_INCREMENT = 1;
DELETE FROM `historia_wersji` WHERE 1;


delete from zasoby ;