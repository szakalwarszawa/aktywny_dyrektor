select * from wniosek_historia_statusow whs 
join wniosek w on w.id = whs.wniosek_id
join wniosek_nadanie_odebranie_zasobow wz on wz.wniosek_id = w.id
join userzasoby uz on uz.wniosek_id = w.id
join zasoby z on z.id = uz.zasob_id

where whs.status_id IN (select id from wniosek_status where nazwaSystemowa = '07_ROZPATRZONY_POZYTYWNIE')



03_EDYCJA_WLASCICIEL



select 
whs.*,
z.wlascicielZasobu,
whs.createdBy
 from wniosek_historia_statusow whs 
join wniosek w on w.id = whs.wniosek_id
join wniosek_nadanie_odebranie_zasobow wz on wz.wniosek_id = w.id
join userzasoby uz on uz.wniosek_id = w.id
join zasoby z on z.id = uz.zasob_id

where whs.status_id IN (select id from wniosek_status where nazwaSystemowa = '03_EDYCJA_WLASCICIEL')
and z.wlascicielZasobu not like concat('%', whs.createdBy ,'%');