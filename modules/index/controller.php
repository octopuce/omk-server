<?php

class indexController extends AController {

  function indexController() {
    // Check user identity with no requirement
    check_user_identity(false);
  }

  function indexAction() {
    $view["msg"] = _("Welcome to the OpenMediaKit transcoder!");
    $this->render("index", $view);
  }
}
