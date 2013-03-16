This folder is an example of a global adapter plugin for the OpenMediakit Transcoder.

Usually, if you need to developp your own adapter, 
it means that you also developped your own proper FileAdapter in the OpenMediakit Client at the other end,
and that the two classes know how to talk to each others.

copy this dummy-adapter/ folder to yourname-adapter/ folder and start from this.

You need to change hooks.php to add your adapter name in the adapterList method

and also change the name of both Hook and Interface classes in hooks.php and interface.php

Then change the functions in interface.php class accordingly.

Finally, in the "users" table, 
add "yourname" to the comma-separated list of allowed adapters for the users who should be able to use this adapter.

