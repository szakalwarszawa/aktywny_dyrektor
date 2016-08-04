SELECT * FROM `zasoby` WHERE `nazwa` LIKE 'SGG-%' ORDER BY `zasoby`.`poziomDostepu` ASC;

update `zasoby` 
set poziomDostepu = 'RO - Tylko do odczytu, RW - Odczyt + zapis'
WHERE `nazwa` LIKE 'SGG-%' 
ORDER BY `zasoby`.`poziomDostepu` ASC;



update `zasoby` 
set 
grupyAD = concat(name, '-RO;',name, '-RW'),
poziomDostepu = 'RO - Tylko do odczytu, RW - Odczyt + zapis'
WHERE `nazwa` LIKE 'SGG-%' 
ORDER BY `zasoby`.`poziomDostepu` ASC;



update `wniosek` 
set `status_id` = 63,
`statusname` = 'Rozpatrzony negatywnie'
WHERE id in (18,19,20);