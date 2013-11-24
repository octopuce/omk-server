<?php
class StatusHooks extends AHooks {
  public function menu(&$menu) {
    if (is_admin()) {
      $menu[] = array(
		      'url' => '/status',
		      'name' => _("Status"),
		      );
    }
  }


}
