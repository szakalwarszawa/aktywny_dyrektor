update wniosek_editor  set samaccountname = 'grzegorz_bialowarczu' where wniosek_id IN (
    select id from wniosek where status_id in (select id from wniosek_status where nazwaSystemowa = '04_EDYCJA_IBI')
) and deletedAt is null;

insert into wniosek_viewer(wniosek_id, samaccountname, deletedAt)
select wniosek_id, 'grzegorz_bialowarczu', null from wniosek_editor where wniosek_id in (
    select id from wniosek where status_id in (select id from wniosek_status where nazwaSystemowa = '04_EDYCJA_IBI')
) and deletedAt is null;


