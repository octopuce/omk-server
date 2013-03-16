<?php


function filterSettings($s) {
  global $allowsettings;
  $res=array();
  foreach($s as $k=>$v) {
    if (in_array($k,$allowsettings)) {
      $res[$k]=$v;
    }
  }
  ksort($res);
  return $res;
}

function fail($id,$msg) {
  $t=new StdClass();
  $t->code=$id;
  $t->message=$msg;
  echo json_encode($t);
  exit();
}

function fail_human($id,$msg) {
  echo "<html><body><h1>Message:</h1>\n";
  echo "<p>($id) <b>$msg</b></p>";
  echo "</body></html>";
  exit();
}

function validate($id,$email) {
  // send the validation email
  $key = substr(md5(RANDOM_SALT . "_" .$email),0,10);

  $to      = $email;
  $subject = _("Email validation for the public OpenMediakit Transcoder");
  $message = sprintf(_("
Hi,

Someone, maybe you, created a PUBLICLY AVAILABLE instance of the OpenMediakit Transcoder service.

This email is sent to the administrator email to validate its ownership. 
Please click the link below if you want to confirm that you want your transcoder instance to be made public

%s

Please note that your transcoder will not appear as a publicly available one until your email has been validated, since we may need to contact you if something goes wrong with this service.

If you didn't asked for this account, please ignore this message.

--
Regards,

The OpenMediakit Team
"),FULL_URL."?action=validate&id=".$id."&key=".$key);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);    
}

