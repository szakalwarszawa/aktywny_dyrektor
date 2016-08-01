select
u.name,
d.name,
isu.departament,
isu.sekcja,
isu.sekcjaSkrot,
isu.pracownik,
isu.stanowisko
from import_sekcje_user isu
left join departament d on d.name = isu.departament and d.nowaStruktura = 1
left join ad_user u on UPPER(u.name) like UPPER(isu.pracownik)
where stanowisko like '%kierownik%';