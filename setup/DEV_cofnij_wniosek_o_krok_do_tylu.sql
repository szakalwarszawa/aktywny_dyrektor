/* wniosekOdebranieNadanieUprawnien_id = 93
    wniosekId = 1954 */
    
update wniosek_editor set deletedAt = current_timestamp where wniosek_id = 1954 and deletedAt is null;
update wniosek_viewer set deletedAt = current_timestamp where wniosek_id = 1954 and deletedAt is null;
    
update wniosek_editor set deletedAt = null where wniosek_id = 1954 and deletedAt = (select max(createdAt) from wniosek_historia_statusow where wniosek_id = 1954);
update wniosek_viewer set deletedAt = null where wniosek_id = 1954 and deletedAt = (select max(createdAt) from wniosek_historia_statusow where wniosek_id = 1954);;
    
    
    
update wniosek_historia_statusow set deletedAt = current_timestamp where id in (select id from (select max(id) as id from wniosek_historia_statusow where wniosek_id = 1954) as s);
update wniosek set status_id = (select status_id from wniosek_historia_statusow where id = (select max(id) from wniosek_historia_statusow where wniosek_id = 1954 and deletedAt is null));
    
update wniosek set 
editornames = (select group_concat(samaccountname) from wniosek_editor where wniosek_id = 1954 and deletedAt is null),
viewernames = (select group_concat(samaccountname) from wniosek_viewer where wniosek_id = 1954 and deletedAt is null);
/*
delete from wniosek_historia_statusow where id in (select max(id) from wniosek_historia_statusow where wniosek_id = 1954);
update wniosek set
*/