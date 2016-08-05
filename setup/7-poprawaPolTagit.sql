update Departament set grupyAD = replace(grupyAD, ",", ";");
update Zasoby set 
    modulFunkcja = replace(modulFunkcja, ",", ";"),
    poziomDostepu = replace(poziomDostepu, ",", ";"),
    grupyAD = replace(grupyAD, ",", ";")
    ;
