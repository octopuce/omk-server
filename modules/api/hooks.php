<?php
class ApiHooks extends AHooks {
  public function menu(&$menu) {
    $menu[] = array(
		    'url' => '/api',
		    'name' => _("api"),
		    );
  }


}
