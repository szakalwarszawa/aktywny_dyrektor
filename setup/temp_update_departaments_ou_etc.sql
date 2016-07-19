update departament
set ouAD = concat('OU=', shortname, ',OU=Zespoly');

update wniosek_status
set viewers = 'wnioskodawca,podmiot,przelozony,wlasciciel,administrator,techniczny'