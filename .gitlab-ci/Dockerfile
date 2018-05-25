# Definicja obrazu używanego przez GitLab CI dla proejktu Aktywny Dyrektor.
# Nazwa: gitlab.parp.gov.pl:4567/parp/aktywny_dyrektor:latest
FROM alpine:3.7
RUN apk add --no-cache git curl util-linux gawk tidyhtml
RUN apk add --no-cache php7 php7-curl php7-openssl php7-ldap php7-phar php7-zlib php7-json php7-iconv php7-mbstring php7-intl php7-ctype php7-xml php7-simplexml php7-xmlwriter php7-tokenizer php7-dom php7-pdo
RUN ln -sf /usr/bin/php7 /usr/bin/php
RUN echo 'date.timezone = "Europe/Warsaw"' > /etc/php7/conf.d/timezone.ini
RUN mkdir /usr/bin/composer
RUN export COMPOSER_HOME=/usr/bin/composer/
RUN touch /usr/bin/composer/composer.json
RUN echo "{\"require\": {}}" >> /usr/bin/composer/composer.json
RUN curl -sS https://getcomposer.org/installer | php
RUN php -d memory_limit=512M composer.phar global require "hirak/prestissimo" --prefer-dist --no-suggest --no-progress --no-interaction --no-scripts
RUN php -d memory_limit=2G composer.phar global require "jakub-onderka/php-parallel-lint" "jakub-onderka/php-console-highlighter" "squizlabs/php_codesniffer" "phploc/phploc" "sebastian/phpcpd" "allocine/twigcs" "phpmd/phpmd" --prefer-dist --no-suggest --no-progress --no-interaction --no-scripts
RUN curl -O http://get.sensiolabs.org/sami.phar
RUN cp sami.phar /usr/bin/sami.phar
RUN apk add --no-cache nodejs nodejs-npm
RUN mkdir /usr/bin/node_modules
RUN npm config set prefix "/usr/bin/node_modules/"
RUN npm install -g eslint eslint-plugin-html eslint-config-idiomatic htmllint htmllint-cli
COPY ./missing_empty_line_detector.sh /usr/bin/missing_empty_line_detector.sh
RUN chmod 0777 /usr/bin/missing_empty_line_detector.sh
CMD ["/bin/sh"]
