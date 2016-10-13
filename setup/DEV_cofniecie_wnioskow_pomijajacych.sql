select w.status_id, ws.nazwa from wniosek w 

join wniosek_status ws on ws.id = w.status_id
where w.id in (
    488, 719, 106, 1574, 1575, 1664, 1690, 1691, 1757, 1758, 1771, 1801, 1803, 1806, 1807, 1809, 1811, 1812, 1813, 1814, 1817, 1819, 1826, 1827, 1830, 1880, 1906, 1909, 1910, 1911, 1938, 1941, 1945, 1946, 1950, 1951, 1952
);



update wniosek_historia_statusow set deletedAt = current_timestamp where wniosek_id in (
    488, 719, 106, 1574, 1575, 1664, 1690, 1691, 1757, 1758, 1771, 1801, 1803, 1806, 1807, 1809, 1811, 1812, 1813, 1814, 1817, 1819, 1826, 1827, 1830, 1880, 1906, 1909, 1910, 1911, 1938, 1941, 1945, 1946, 1950, 1951, 1952
) and status_id in (62,66);


update wniosek w join (
select max(id) as statusreal, wniosek_id from wniosek_historia_statusow whs where deletedAt is null group by wniosek_id
) s on s.wniosek_id = w.id
join wniosek_historia_statusow hs on hs.id = s.statusreal
set 
w.status_id = hs.status_id
where hs.status_id != w.status_id;

update wniosek set lockedBy = null, lockedAt = null where id in (
    488, 719, 106, 1574, 1575, 1664, 1690, 1691, 1757, 1758, 1771, 1801, 1803, 1806, 1807, 1809, 1811, 1812, 1813, 1814, 1817, 1819, 1826, 1827, 1830, 1880, 1906, 1909, 1910, 1911, 1938, 1941, 1945, 1946, 1950, 1951, 1952
);

update wniosek_editor set deletedAt = current_timestamp where wniosek_id in (
    488, 719, 106, 1574, 1575, 1664, 1690, 1691, 1757, 1758, 1771, 1801, 1803, 1806, 1807, 1809, 1811, 1812, 1813, 1814, 1817, 1819, 1826, 1827, 1830, 1880, 1906, 1909, 1910, 1911, 1938, 1941, 1945, 1946, 1950, 1951, 1952
);

insert into wniosek_editor (wniosek_id , samaccountname)
select w.id, d.dyrektor from wniosek w 
join wniosek_status ws on ws.id = w.status_id
join departament d on d.name = w.jednostkaOrganizacyjna
where w.id in (
    488, 719, 106, 1574, 1575, 1664, 1690, 1691, 1757, 1758, 1771, 1801, 1803, 1806, 1807, 1809, 1811, 1812, 1813, 1814, 1817, 1819, 1826, 1827, 1830, 1880, 1906, 1909, 1910, 1911, 1938, 1941, 1945, 1946, 1950, 1951, 1952
) and ws.id = 57;



