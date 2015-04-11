FROM debian:wheezy
MAINTAINER Tobias Hößl <tobias@hoessl.eu>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update

RUN apt-get install -y procps screen vim
RUN apt-get install -y git curl wget unzip bzip2

RUN apt-get install -y python-software-properties
RUN apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db
RUN add-apt-repository 'deb http://mirror.23media.de/mariadb/repo/10.0/debian wheezy main'
RUN apt-key adv --keyserver pgp.mit.edu --recv-keys 573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62
RUN wget http://www.dotdeb.org/dotdeb.gpg
RUN apt-key add dotdeb.gpg
RUN echo 'deb http://packages.dotdeb.org wheezy-php56-zts all' >> /etc/apt/sources.list
RUN echo 'deb-src http://packages.dotdeb.org wheezy-php56-zts all'  >> /etc/apt/sources.list

RUN apt-get update
RUN apt-get upgrade -y

RUN echo 'mysql-server mysql-server/root_password password pw' | debconf-set-selections
RUN echo 'mysql-server mysql-server/root_password_again password pw' | debconf-set-selections
RUN apt-get install -y nginx-full
RUN apt-get install -y mariadb-server

RUN apt-get install -y php5 php5-fpm php5-mcrypt php5-intl php5-curl php5-mysqlnd php5-cli

RUN mkdir /var/www/
WORKDIR /var/www/
RUN git clone -b v3 https://github.com/CatoTH/antragsgruen.git
WORKDIR /var/www/antragsgruen/

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

RUN wget https://www.hoessl.eu/antragsgruen-v3-vendor.tar.bz2
RUN tar xfj antragsgruen-v3-vendor.tar.bz2
RUN rm antragsgruen-v3-vendor.tar.bz2

RUN wget https://www.hoessl.eu/antragsgruen-v3-bower.tar.bz2
RUN tar xfj antragsgruen-v3-bower.tar.bz2
RUN rm antragsgruen-v3-bower.tar.bz2
RUN mv bower web/js/

RUN composer global require "fxp/composer-asset-plugin:1.0.0"
#RUN composer install --prefer-source --no-interaction

RUN mkdir /var/www/antragsgruen/runtime
RUN chown www-data:www-data /var/www/antragsgruen/runtime
RUN chown -R www-data:www-data /var/www/antragsgruen/web/assets

RUN cp docker-vagrant/config.php config/
RUN cp docker-vagrant/config_tests.php config/


RUN apt-get install -y libgmp10 libgomp1 libicu-dev libicu48 libitm1 libmpc2 libmpfr4 libquadmath0 fontconfig-config zlib1g-dev libfontconfig
RUN wget -O /usr/local/bin/phantomjs https://www.hoessl.eu/phantomjs
RUN chmod a+x /usr/local/bin/phantomjs


RUN rm /etc/nginx/sites-enabled/default
RUN echo "daemon off;" >> /etc/nginx/nginx.conf
RUN cp docker-vagrant/nginx-antragsgruen /etc/nginx/sites-enabled/antragsgruen


RUN apt-get install -y supervisor



EXPOSE 80

# volume for mysql database and wordpress install
VOLUME ["/var/www/antragsgruen"]

CMD ["/bin/bash", "/var/www/antragsgruen/docker-vagrant/start.sh"]