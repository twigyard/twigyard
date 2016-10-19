# Installation
The easiest way to install TwigYard is to use the example [application](https://github.com/twigyard/example-app). It handles all dependencies and configuration and it should work out of the box.

In order to route the site static files correctly the application must be run by Apache2 webserver. If you would like to use some other webserver, please take a look at the `.htaccess` file and port the configuration to the webserver of your choice. 
 
## Install TwigYard
### Linux
Lets start with a fresh `Ubuntu` box. This guide should work for both `16.04` and `16.10` releases. 

First we install the required packages:
```
# apt-get install apache2 composer libapache2-mod-php php-curl php-gd php-intl php-xml unzip
```

Then we download the example app into `/srv/twigyard` and set permissions:
```
# cd /srv
# wget https://github.com/twigyard/example-app/archive/master.zip
# unzip master.zip
# rm master.zip
# mv example-app-master/ twigyard
# chown -R www-data /srv/twigyard
```

Now we need to create the Apache2 virtual host config file. It should be located in `/etc/apache2/sites-available/twigyard.conf`.
```
# /etc/apache2/sites-available/twigyard.conf
  
<VirtualHost *:80>
        ServerAlias *.twigyard.localhost
        DocumentRoot /srv/twigyard
        <Directory />
            Options FollowSymLinks
            AllowOverride all
            Require all granted
        </Directory>
</VirtualHost>
```

The new vhost now must be enabled:
```
# ln -s /etc/apache2/sites-available/twigyard.conf /etc/apache2/sites-enabled/twigyard.conf
```

ModRewrite must be enabled in Apache2:
```
# ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled
```

In order for Apache2 to start using the new configuration, we must restart it:
```
# systemctl restart apache2
```

Its time to add our first site:
```
# cd /srv/twigyard/sites
# wget https://github.com/twigyard/example-site-multi-language/archive/master.zip
# unzip master.zip
# rm master.zip
# mv example-site-multi-language-master example-site-multi-language
```

## Install dependencies
All PHP dependencies must be installed by composer.
### Linux
```
# cd /srv/twigyard 
# composer install
```

## Configure TwigYard
Local configuration files must be created from the defaults. This is done by copying config files with extension `.dist` to a new file name without the extension. So, for example, `parameters.yml.dist` should be copied into `parameters.yml`. All config files are located in `app/config/`.
 
TwigYard can operate in two modes. One is for production and the other for development and staging.

### Production
In this mode sites are accessible on their canonical domain. In this case set the `parent_domain` in `parameters.yml` to `~`. It is also recommended to remove all lines marked with `# remove on prdoduction` from the `.htaccess` file.   

### Dev / Staging
If you decide to change the `parent_domain` in `parameters.yml`, you must also change it in the `.htaccess` file to ensure that routing works correctly.


## Visit the site
Point your browser to `http://example-site-multi-language.twigyard.localhost` to see the example site. More sites van be added by simply adding another folder into the `sites` directory, so feel free to try it with the single language example site located at `https://github.com/twigyard/example-site-multi-language/archive/master.zip`.
