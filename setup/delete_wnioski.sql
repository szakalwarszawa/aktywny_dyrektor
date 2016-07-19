delete from wniosek_historia_statusow;
delete from userzasoby where not wniosek_id is null;
delete from wniosek_editor;
delete from wniosek_viewer;
update wniosek set parent_id = null, WniosekNadanieOdebranieZasobow_id = null;
update wniosek_nadanie_odebranie_zasobow set wniosek_id = null;
delete from wniosek;
delete from wniosek_nadanie_odebranie_zasobow;