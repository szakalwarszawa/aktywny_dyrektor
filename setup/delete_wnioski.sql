delete from userzasoby where not wniosek_id is null;
delete from wniosek_nadanie_odebranie_zasobow_editor;
delete from wniosek_nadanie_odebranie_zasobow_viewer;
update wniosek_nadanie_odebranie_zasobow set parent_id = null;
delete from wniosek_nadanie_odebranie_zasobow;