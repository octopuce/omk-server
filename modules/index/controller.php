<?php

class indexController extends AController {

  function indexController() {
    
  }

  function indexAction() {
    $view["msg"] = _("Welcome to the OpenMediaKit transcoder!");
    $this->render("index", $view);
  }
}
