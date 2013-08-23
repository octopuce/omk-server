## Installation instructions for the OpenMediaKit Server in Multiple Server Mode ##

For generic install information, read [INSTALL.md](INSTALL.md)
For copyright and authors information, read [README.md](README.md)

This documentation explains: 

How to deploy a multi-server OpenMediaKit-Server instance

## Introduction ##

If your OpenMediaKit-Server instance is likely to have *a lot* of videos to transcode, you can deploy it in a multiserver mode.

The multiserver mode works that way:

* you have a mysql server, remote or local, but that can be joined remotely by any server participating in the transcoding process.
* you have a web server which serves a properly installed OpenMediaKit-Server instance.  
* you have one or more other servers, called the "secondary servers", which will only run the transcoder daemon (and can run the metadata daemon too)
* your main server is sharing its www root using NFS and a gigabit or multigigabit network with its secondary peers.

NOTE: 

* If your webserver serving the OpenMediaKit-Server don't have a lot of CPU power available, you can disable the metadata and/or OpenMediaKit-Server daemons there. just remove their init script and launch the metadata/transcoder daemons only on secondary servers.
* If you have a lot of videos to transcode and recognize, you should have a lot of disk I/O available on the web document root of the OpenMediaKit-Server. Using SSD's is recommended.
* Since you are using NFS to share the documentroot between servers, you shall have a private IP network between your hosts. The secondary servers DON'T NEED to have Internet access though. 
* You can use as many secondary OpenMediaKit-Server as you want, as long as your NFS server have enough disk I/O capabilities to handle the load :) Each secondary server will use as much CPU power as it can using multithreaded ffmpeg.


## Deployment process ##

To deploy a multiserver instance of the OpenMediaKit-Server, proceeed as follow:

Deploy the OMK in a standard server. Let's say /var/www/omk is your document root. and 10.2.1.41 is our server's private ip address.

share /var/www/ using NFS.

Under Debian Wheezy, do:

    apt-get install nfs-kernel-server
    echo "/var/www/  10.2.1.0/255.255.255.0(rw,async,no_subtree_check,no_root_squash)" >>/etc/exports
    /etc/init.d/nfs-kernel-server reload

in our case, all secondary omk instances will be on 10.2.0.X network.

deploy a secondary Debian Wheezy machine. Give it a unique hostname (eg: if your main OpenMediaKit-Server instance is named "omk", you can name it "omk2", but NOT "omk" too.)

add the deb-multimedia.org repository, example :

    echo "deb http://debian.octopuce.fr/debian-multimedia wheezy main non-free"  >/etc/apt/sources.list.d/multimedia.list
    apt-get update
    apt-get install deb-multimedia-keyring

install the required packages : 

    apt-get install nfs-client php5-cli php5-mysql ffmpeg

mount the /var/www of the main omk into the secondary one : 

    mkdir /var/www
    echo "10.2.1.41:/var/www /var/www  nfs  auto,rsize=8192,wsize=8192,vers=3	0	0" >>/etc/fstab
    mount /var/www

(you may need to allow communication between the hosts in your firewall)

ensure that your OMK MAIN instance has a REMOTE MySQL configuration. (no "localhost" or "127.0.0.1" for your mysql server connection in config.inc.php, but something like "10.2.1.41")

If your mysql is LOCAL make it listen into the network (comment the "bind_address" directive in /etc/mysql/my.cnf) and allow connections from all the IP addresses of your OMK-s instances using: 

    mysql -B -e "GRANT ALL ON omk.* TO 'omk'@'%' IDENTIFIED BY 'randompassword';" 

launch the metadata daemon and transcode daemon in your omk-2 machine, using the 2 initscripts provided with the OMK.

Under Debian Wheezy:

    cd /var/www/omk/init/
    cp omk-transcoder omk-metadata /etc/init.d/
    update-rc.d omk-transcoder defaults
    update-rc.d omk-metadata defaults
    invoke-rc.d omk-transcoder restart
    invoke-rc.d omk-metadata restart


