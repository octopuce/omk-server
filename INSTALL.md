## OpenMediaKit Server installation instructions ##

This documentation is for a Debian Wheezy properly installed machine. If you are using another Linux or BSD distributions, this will work, but you'll need to adjust the installation instructions for your distribution.

To install the OpenMediaKit Server, you need the following: 

* a Linux or *Nix install with a webserver, php5 (version 5.3 minimum), MySQL-server and php libraries (php5-curl)
* imagemagick, zip and ffmpeg WITH proprietary codecs if you want to be able to encode to mp4, mp3 etc.

If you are using a standard Debian Linux, you will need to use [deb-multimedia](http://www.deb-multimedia.org) repository as follow (add this line in /etc/apt/sources.list.d/deb-multimedia.list )

    deb http://debian.octopuce.fr/debian-multimedia wheezy main non-free

then launch, as root: 

    apt-get update
    apt-get install deb-multimedia-keyring
    apt-get update
    apt-get install ffmpeg 

other recommended commands to install the omk-server properly (on Debian): 

    apt-get install libapache2-mod-php5 php5-cli apache2-mpm-prefork php5-curl php-apc mysql-server zip imagemagick git

then clone this repository:

    cd /var/www && git clone https://github.com/octopuce/omk-server.git omk

Then install a vhost pointing to your omk-server public/ folder (in our example with the source code in /var/www/omk). For example, put this in a file named /etc/apache2/sites-available/omk

    <Virtualhost *:80>
      ServerName <your domain name here>
      DocumentRoot /var/www/omk/public

      ErrorLog ${APACHE_LOG_DIR}/error.log
      CustomLog ${APACHE_LOG_DIR}/access.log combined

      <Directory /var/www/omk>
        Order Allow,deny
        Allow from all
        AllowOverride All
      </Directory>
    </VirtualHost>	  

then

    a2enmod rewrite
    a2ensite omk
    /etc/init.d/apache2 restart

Install the database and configure it properly in /var/www/omk/config.php

NOTE: If you want to install a MULTISERVER openmediakit transcoder, you will need to use a *remote mysql* configuration, read [MULTISERVER.md](MULTISERVER.md) for more information.

    mysql -B -e "CREATE DATABASE omk; GRANT ALL ON omk.* TO 'omk'@'localhost' IDENTIFIED BY 'randompassword';"
    cp /var/www/omk/config.inc.php.sample /var/www/omk/config.inc.php
    emacs /var/www/omk/config.inc.php

Launch the install script

    /var/www/omk/install

This process initializes the database (it will also UPGRADE the database from one version to the next one.). It also installs and launch all the daemons of the omk-server by copying the init scripts into /etc/init.d/ and ask Debian boot system to launch them at boottime as www-data. It also installs a daily crontab for cleanup purpose.

The current process we run are: 

* a "crontab" file installed as a system daily cron job.
* modules/api/scripts/cron.daemon.php must be launched as a daemon, it calls the cron action of each valid client every minute or so.
* modules/http/downlaoder.daemon.php must be launched as a daemon on any number of machines you want (as long as they share the same NFS and MySQL access, see [MULTISERVER.md](MULTISERVER.md) for this) We recommend launching it on the NFS server though (faster download)
* modules/api/scripts/metadata.daemon.php must be launched as a daemon on any number of machines you want (as long as they share the same NFS and MySQL access). It recognize the video file metadata (tracks, codecs, box etc.) using ffmpeg.
* modules/api/scripts/api.daemon.php must be launched as a daemon on one machine. It calls the API of any OpenMediaKit API compliant client, to tell this client that a video has been recognized, transcoded, etc.


## Public OpenMediaKit Servers ##

If you are a big hosting provider or a network operator, you could provide anybody with free (as in 'free beer') video transcoding service. 

To do so, you will need to set "PUBLIC_TRANSCODER" to "true" in the config.inc.php file, set a name for your server in "TRANSCODER_NAME", ensure it has a publicly visible hostname and IP address (IPv4 and IPv6 could be nice ;) ) and that you set a contact email address in "TRANSCODER_ADMIN_EMAIL". 

We will confirm your email address automatically, and add your server to the list of all the publicly available OpenMediaKit Servers.

The OpenMediaKit Client library know how to get te list of all publicly available OpenMediaKit Servers, available at http://discovery.open-mediakit.org/

