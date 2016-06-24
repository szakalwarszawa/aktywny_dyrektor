delete from wniosek_historia_statusow;
delete from userzasoby where not wniosek_id is null;
delete from wniosek_editor;
delete from wniosek_viewer;
update wniosek set parent_id = null, wniosekNadanieOdebranieZasobow_id = null;
update wniosek_nadanie_odebranie_zasobow set wniosek_id = null;
delete from wniosek_nadanie_odebranie_zasobow;
delete from wniosek;
delete from userzasoby ;

UPDATE `wniosek_viewer` SET `samaccountname`= 'jaroslaw_bednarczyk' WHERE `samaccountname`= 'jarosław_bednarczyk'