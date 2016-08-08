update wniosek w
inner join (
select wniosek_id, max(id) as id from wniosek_historia_statusow where deletedAt is null group by wniosek_id
) as s on s.wniosek_id = w.id
inner join wniosek_historia_statusow whs on whs.id = s.id
set w.status_id = whs.status_id;