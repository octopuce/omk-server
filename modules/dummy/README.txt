This folder is an example of a global adapter plugin for the OpenMediakit Transcoder.

Usually, if you need to develop your own adapter, 
it means that you also developped your own proper FileAdapter in the OpenMediakit Client at the other end,
and that the two classes know how to talk to each others.

copy this dummy/ folder to yourname/ folder and start from this.

You need to change hooks.php to add your adapter name in the adapterList method

and also change the name of both Hook and Adapter classes in hooks.php and adapter.php

Then change the functions in adapter.php class accordingly, and create a controller.php if needed

Finally, in the "users" table, 
add "yourname" to the comma-separated list of allowed adapters for the users who should be able to use this adapter.

note: the daemon.php is a dummy daemon that behave almost like the http daemon... you may use it as a sample if your adapter need to be able to download things ...

