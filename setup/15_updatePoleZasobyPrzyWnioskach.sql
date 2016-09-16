
select 
w.id,
group_concat(distinct z.nazwa ORDER BY z.nazwa ASC SEPARATOR ', ') as nazwa
from wniosek_nadanie_odebranie_zasobow w 
join userzasoby uz on uz.wniosek_id = w.id
join zasoby z on uz.zasob_id = z.id
group by w.id;



update wniosek_nadanie_odebranie_zasobow set zasoby = null;


SET SESSION group_concat_max_len = 4000;

update wniosek_nadanie_odebranie_zasobow w
join (
    select 
w.id,
group_concat(distinct z.nazwa ORDER BY z.nazwa ASC SEPARATOR ', ') as nazwa
from wniosek_nadanie_odebranie_zasobow w 
join userzasoby uz on uz.wniosek_id = w.id
join zasoby z on uz.zasob_id = z.id
group by w.id
) as s on s.id = w.id
set zasoby = s.nazwa;




