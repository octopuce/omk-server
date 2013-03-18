<?php

class DummyHooks extends AHooks {
 
  public function adapterList(&$list) {
    $list[]="dummy";
  }

}

