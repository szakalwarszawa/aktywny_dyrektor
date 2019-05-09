# Łatanie paczki w node_modules

## Dlaczego?

Mamy przyjemną paczkę bootstrap-sass dodaną w zależnościach (package.json) w wersji 3.4.1.
W jednym z plików ścieżynka do glyphicons jest tak zdefiniowana, że przy imporcie paczki z poziomu katalogu web/assets/scss composer nie jest sobie w stanie zbudować prawidłowej ścieżki i wywala builda.

Poprawka, to zmieniona jedna linijka, z:
`~~$icon-font-path: if($bootstrap-sass-asset-helper, "bootstrap/", "../fonts/bootstrap/") !default;~~`
na:
`$icon-font-path: "~bootstrap-sass/assets/fonts/bootstrap/";`
Ale jak zachować możliwość zarządzania paczkami z poziomu Webpack Encore?

## patch-package

Z pomocą przychodzi patch-package.
https://www.npmjs.com/package/patch-package

Poprawka jest nanoszona na moduł przed postawieniem builda (yarn run encore production) przy pomocy polecenia yarn patch-package (zdefioniowane w pliku composer.info). Poprawka trzyma tylko różnicę w pliku i umożliwia webpackowi zarządzanie zależnościami.
