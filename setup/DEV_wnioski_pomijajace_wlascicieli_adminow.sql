select 
s2.updated,
s.*,
s.statusy not like '%W akceptacji u właściciela zasobu%' as brakWlasciciela,
s.statusy not like '%W akceptacji u administratora zasobu%' as brakAdministratora

from (
    select wz.id, concat(w.id, '') as idd, w.numer, w.`createdBy`,w.`createdAt`, ws.nazwa, group_concat(whs.statusname) statusy from wniosek_nadanie_odebranie_zasobow wz join wniosek w on wz.wniosek_id = w.id join wniosek_status ws on ws.id = w.status_id
    join wniosek_historia_statusow whs on whs.`wniosek_id`= w.id where w.`status_id` in (
    	select id from wniosek_status where nazwaSystemowa in ("07_ROZPATRZONY_POZYTYWNIE", "11_OPUBLIKOWANY")
    )
    group by w.numer, ws.nazwa, w.`createdBy`
) as s 

join (
    select max(logged_at) as updated, hw.object_id as idd,  hw.object_class from historia_wersji hw
    where  hw.object_class = 'Parp\\MainBundle\\Entity\\Wniosek' group by hw.object_id, hw.object_class 
) as s2 on s2.idd like s.idd
where s.statusy not like '%W akceptacji u właściciela zasobu%' and s.statusy not like '%W akceptacji u administratora zasobu%'
order by updated desc

;

