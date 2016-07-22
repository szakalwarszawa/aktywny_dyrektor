update wniosek_status set typWniosku = 'wniosekONadanieUprawnien' where typWniosku = '';

DELETE FROM  `wniosek_status` where typWniosku = 'wniosekOUtworzenieZasobu';
INSERT INTO `wniosek_status` (`deletedAt`, `nazwa`, `nazwaSystemowa`, `opis`, `viewers`, `editors`, `finished`, `typWniosku`) VALUES
( NULL, 'Tworzony', '00_TWORZONY_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', 'wnioskodawca', 0, 'wniosekOUtworzenieZasobu'),
( NULL, 'W edycji u wnioskodawcy', '01_EDYCJA_WNIOSKODAWCA_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', 'wnioskodawca', 0, 'wniosekOUtworzenieZasobu'),
( NULL, 'W akceptacji u właściciela zasobu', '02_EDYCJA_WLASCICIEL_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', 'wlasciciel', 0, 'wniosekOUtworzenieZasobu'),
( NULL, 'W edycji u administratora rejestru zasobów', '03_EDYCJA_PARP_ADMIN_REJESTRU_ZASOBOW', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', 'administratorZasobow', 0, 'wniosekOUtworzenieZasobu'),
( NULL, 'Rozpatrzony pozytywnie', '07_ROZPATRZONY_POZYTYWNIE_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', '', 1, 'wniosekOUtworzenieZasobu'),
( NULL, 'Rozpatrzony negatywnie', '08_ROZPATRZONY_NEGATYWNIE_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', '', 1, 'wniosekOUtworzenieZasobu'),
( NULL, 'Zaakceptowany i wdrożony', '11_OPUBLIKOWANY_O_ZASOB', NULL, 'wnioskodawca,wlasciciel,administratorZasobow', '', 1, 'wniosekOUtworzenieZasobu');


