<?php
/*
take the settings.csv file in ../../settings.csv
and update settings.php in ../libs/
*/

$f=@fopen(__DIR__."/../../../settings.csv","r");
if (!$f) {
  echo "Can't open settings.csv, please check\n";
  exit();
}
$g=@fopen(__DIR__."/../libs/settings.php","w");
if (!$g) {
  echo "Can't open settings.php, please check\n";
  exit();
}

fputs($g,"<?php \n/*\n * These are the standard settings of OpenMediaKit\n * See http://www.open-mediakit.org/ for more information\n * WARNING: this file is automatically generated from ../scripts/update_settings.php !\n */\n\n");
fputs($g,'$settings=array('."\n");
$s=fgetcsv($f);
$fields=array();
foreach($s as $field) {
  $fields[]=strtolower($field);
}

while ($s=fgetcsv($f)) {
  fputs($g,"   array(\n");
  $i=0;
  foreach($fields as $field) {
    fputs($g,"          \"".$field."\" => \"".str_replace('"','\"',$s[$i])."\",\n");
    $i++;
  }
  fputs($g,"        ),\n\n");
}

fputs($g,"\n );\n");
fputs($g,"\n?>\n");

fclose($f);
fclose($g);

echo "Done\n";


