#phantomjs --webdriver 4444 > /var/log/phantomjs.log 2>&1 &
#cd /var/www/antragsgruen/tests/
#./start_debug_server.sh > /var/log/php-test-server.log 2>&1  &


/etc/init.d/mysql start
echo "CREATE DATABASE antragsgruen;" | mysql -u root -ppw
echo "CREATE DATABASE antragsgruen_tests;" | mysql -u root -ppw

cd /var/www/antragsgruen/
./yii database/create

/etc/init.d/php5-fpm start
/etc/init.d/nginx start
