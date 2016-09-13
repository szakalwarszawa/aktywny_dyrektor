update wniosek_editor set deletedAt = current_timestamp where wniosek_id in (
    select id from (
        select id from wniosek where 
        numer like '%WU-447%' OR
        numer like '%WU-448%' OR
        numer like '%WU-451%' OR
        numer like '%WU-452%' OR
        numer like '%WU-466%'
    ) s
);
insert into wniosek_editor (wniosek_id, samaccountname)
select id, 'maciej_ziarko' from wniosek where 
    numer like '%WU-447%' OR
    numer like '%WU-448%' OR
    numer like '%WU-451%' OR
    numer like '%WU-452%' OR
    numer like '%WU-466%';
    
insert into wniosek_viewer (wniosek_id, samaccountname)
select id, 'maciej_ziarko' from wniosek where 
    numer like '%WU-447%' OR
    numer like '%WU-448%' OR
    numer like '%WU-451%' OR
    numer like '%WU-452%' OR
    numer like '%WU-466%';
    
update wniosek set editornames = 'maciej_ziarko', lockedBy = null, lockedAt = null where id in (
    select id from (
        select id from wniosek where 
        numer like '%WU-447%' OR
        numer like '%WU-448%' OR
        numer like '%WU-451%' OR
        numer like '%WU-452%' OR
        numer like '%WU-466%'    
    ) s
);