<?php

class indexController extends AController {

  function indexController() {
    
  }

  function indexAction() {
    $view["msg"] = _("Welcome in the OpenMediaKit transcoder!");
    $this->render("index", $view);
  }
}
