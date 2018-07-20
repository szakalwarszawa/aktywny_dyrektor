FROM alpine:3.7
RUN apk add --no-cache git wget curl util-linux gnupg gawk grep tidyhtml
RUN apk add --no-cache php7 php7-curl php7-openssl php7-ldap php7-phar php7-zlib php7-zip php7-json php7-iconv php7-mbstring php7-intl php7-ctype php7-xml php7-simplexml php7-xmlwriter php7-xmlreader php7-tokenizer php7-dom php7-pdo php7-gd
RUN ln -sf /usr/bin/php7 /usr/bin/php
RUN echo 'date.timezone = "Europe/Warsaw"' > /etc/php7/conf.d/timezone.ini
RUN php --version
RUN curl -sS https://getcomposer.org/installer | php
RUN cp composer.phar /usr/bin/composer.phar
RUN php composer.phar --version
RUN php -d memory_limit=512M composer.phar global require "hirak/prestissimo" --prefer-dist --no-suggest --no-progress --no-interaction --no-scripts
RUN php -d memory_limit=2G composer.phar global require "jakub-onderka/php-parallel-lint" "jakub-onderka/php-console-highlighter" "squizlabs/php_codesniffer" "phploc/phploc" "sebastian/phpcpd" "allocine/twigcs" "phpmd/phpmd" --prefer-dist --no-suggest --no-progress --no-interaction --no-scripts
RUN curl -O http://get.sensiolabs.org/sami.phar
RUN cp sami.phar /usr/bin/sami.phar
RUN apk add --no-cache nodejs nodejs-npm
RUN mkdir /usr/bin/node_modules
RUN npm config set prefix "/usr/bin/node_modules/"
RUN npm install -g eslint eslint-plugin-html eslint-config-idiomatic htmllint htmllint-cli
RUN touch ~/.profile
RUN curl -o- -L https://yarnpkg.com/install.sh | sh
RUN ln -sf ~/.yarn/bin/yarn /usr/bin/yarn
RUN yarn --version
RUN wget -O  /tmp/neleof-1.0.0.zip https://github.com/luklub/neleof/archive/v1.0.0.zip
RUN unzip -o /tmp/neleof-1.0.0.zip -d /tmp
RUN cp /tmp/neleof-1.0.0/neleof.sh /usr/bin/neleof.sh
RUN chmod 0777 /usr/bin/neleof.sh
RUN neleof.sh -v
CMD ["/bin/sh"]
