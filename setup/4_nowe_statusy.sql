DELETE FROM  `wniosek_status` where typWniosku = 'wniosekOUtworzenieZasobu'
and nazwaSystemowa in ('04_EDYCJA_ADMINISTRATOR_O_ZASOB', '05_EDYCJA_TECHNICZNY_O_ZASOB');
INSERT INTO `wniosek_status` (`deletedAt`, `nazwa`, `nazwaSystemowa`, `opis`, `viewers`, `editors`, `finished`, `typWniosku`) VALUES
( NULL, 'W edycji u administratora zasobu', '04_EDYCJA_ADMINISTRATOR_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow,administrator', 'administrator', 0, 'wniosekOUtworzenieZasobu'),
( NULL, 'W edycji u administratora technicznego zasobu', '05_EDYCJA_TECHNICZNY_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow,techniczny', 'techniczny', 0, 'wniosekOUtworzenieZasobu');
