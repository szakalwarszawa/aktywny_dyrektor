update zasoby set
grupyAD = concat(nazwa, ";", nazwa, ";", nazwa),
poziomDostepu = "Odbieranie wiadomości;Odbieranie + wysyłanie w imieniu grupy;Odbieranie + wysyłanie jako grupa"
where nazwa like 'INT-%' or nazwa like 'EXT-%';

update  userzasoby set
poziomDostepu = "Odbieranie wiadomości"
 where poziomDostepu = "Odbieranie wiadomosci";
 
 update  userzasoby set
poziomDostepu = "Odbieranie + wysyłanie w imieniu grupy"
 where poziomDostepu = "Odbieranie + wysylanie w imieniu grupy";
 
 update  userzasoby set
poziomDostepu = "Odbieranie + wysyłanie jako grupa"
 where poziomDostepu = "Odbieranie + wysylanie jako grupa";