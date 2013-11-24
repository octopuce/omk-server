<?php
class StatusController extends AController {

  public function indexAction() {
    check_user_identity();

    if (!is_admin()) {
      not_found();
    }
    $this->render('index');
  }

  public function jobAction($params) {
    global $db;
    check_user_identity();

    if (!is_admin()) {
      not_found();
    }

    // default = show job TO BE DONE
    $where=' q.datedone="0000-00-00 00:00:00" ';
    $message=_("List of queued jobs not yet finished");
    $limit='';
    if (isset($_GET["user"])) {
      $message=sprintf(_("List of 100 last jobs from user #%d"),intval($_GET["user"]));
      $where=' q.user='.intval($_GET["user"]).' ';
    }
    if (isset($_GET["mediaid"])) {
      $message=sprintf(_("List of jobs for media #%d"),intval($_GET["mediaid"]));
      $where=' q.mediaid='.intval($_GET["mediaid"]).' ';
    }
    if (isset($_GET["last"])) {
      $message=_("List of lat 100 jobs queued");
      $where=' 1 ';
      $limit=' LIMIT 100 ';
    }
    // List the currently planned jobs
    $queue = $db->qlist('SELECT q.*, m.remoteid, u.email, u.uid, UNIX_TIMESTAMP(q.datequeue) AS ts FROM queue q LEFT JOIN media m ON m.id=q.mediaid LEFT JOIN users u ON u.uid=q.user WHERE '.$where.' ORDER BY q.datequeue DESC '.$limit,null,PDO::FETCH_ASSOC);
    $this->render('job', array('queue' => $queue , 'message' => $message));

  }



  public function mediaAction($params) {
    global $db;
    check_user_identity();

    if (!is_admin()) {
      not_found();
    }

    // default = show job TO BE DONE
    $where=' 1 ';
    $message=_("List of last announced media");
    $limit=' LIMIT 100 ';
    if (isset($_GET["user"])) {
      $message=sprintf(_("List of 100 last media from user #%d"),intval($_GET["user"]));
      $where=' m.owner='.intval($_GET["user"]).' ';
    }

    $sql='SELECT m.*, u.email, u.uid FROM media m LEFT JOIN users u ON u.uid=m.owner WHERE '.$where.' ORDER BY m.datecreate DESC '.$limit;
    $media = $db->qlist($sql,null,PDO::FETCH_ASSOC);
    $this->render('media', array('media' => $media , 'message' => $message));

  }

  public function reloadAction($params) {
    global $db;
    check_user_identity();

    if (!is_admin()) {
      not_found();
    }
    $db->q("LOCK TABLES queue");
    $q=$db->qone('SELECT * FROM queue WHERE id=?',array($params[0]),PDO::FETCH_ASSOC);
    if (!$q) {
      $this->render('index',array('message'=>_("Queue id not found")));
      $db->q("UNLOCK TABLES");
      exit();
    }
    if ($q["lockhost"]) {
      $this->render('index',array('message'=>_("Queue currently processing this task.")));
      $db->q("UNLOCK TABLES");
      exit();
    }
    $db->q('UPDATE queue SET retry=10, datedone="0000-00-00 00:00:00", datetry=NOW(), status=0 WHERE id=?',array($params[0]));
    $db->q("UNLOCK TABLES");
    $this->mediaAction();
  }

  public function deleteAction($params) {
    global $db;
    check_user_identity();

    if (!is_admin()) {
      not_found();
    }
    $db->q("LOCK TABLES queue");
    $q=$db->qone('SELECT * FROM queue WHERE id=?',array($params[0]),PDO::FETCH_ASSOC);
    if (!$q) {
      $this->render('index',array('message'=>_("Queue id not found")));
      $db->q("UNLOCK TABLES");
      exit();
    }
    if ($q["lockhost"]) {
      $this->render('index',array('message'=>_("Queue currently processing this task.")));
      $db->q("UNLOCK TABLES");
      exit();
    }
    $db->q('DELETE FROM queue WHERE id=?',array($params[0]));
    $db->q("UNLOCK TABLES");
    $this->mediaAction();
  }

  
  
  
  
}
