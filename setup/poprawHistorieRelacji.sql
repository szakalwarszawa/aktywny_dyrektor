update uprawnienia join 
(
select u.id,  group_concat(g.opis) as opis from uprawnienia u join 
uprawnienia_w_grupach ug on u.id = ug.uprawnienia_id JOIN 
grupyuprawnien g on g.id = ug.grupyuprawnien_id 
group by u.id
) s on s.id = uprawnienia.id
set grupyHistoriaZmian = s.opis;



update grupyuprawnien join 
(
select g.id,  group_concat(g.opis) as opis from uprawnienia u join 
uprawnienia_w_grupach ug on u.id = ug.uprawnienia_id JOIN 
grupyuprawnien g on g.id = ug.grupyuprawnien_id 
group by g.id
) s on s.id = grupyuprawnien.id
set uprawnieniaHistoriaZmian = s.opis;