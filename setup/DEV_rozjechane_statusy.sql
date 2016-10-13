select 
hs.status_id,
w.status_id,
wz.*
from wniosek_nadanie_odebranie_zasobow wz join (
select max(id) as statusreal, wniosek_id from wniosek_historia_statusow whs group by wniosek_id
) s on s.wniosek_id = wz.wniosek_id
join wniosek_historia_statusow hs on hs.id = s.statusreal
join wniosek w on w.id = wz.wniosek_id
where hs.status_id != w.status_id;

update wniosek w join (
select max(id) as statusreal, wniosek_id from wniosek_historia_statusow whs group by wniosek_id
) s on s.wniosek_id = w.id
join wniosek_historia_statusow hs on hs.id = s.statusreal
set 
w.status_id = hs.status_id
where hs.status_id != w.status_id;