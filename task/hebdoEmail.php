<?php

require __DIR__ . '/../vendor/autoload.php';

$f3 = \Base::instance();

$f3->set('ROOT', __DIR__.'/..');
$f3->set('UI', $f3->get('ROOT')."/views/");
$f3->set('TEMP',$f3->get('ROOT').'/tmp/');
$f3->set('DB', new \DB\SQL('sqlite:'. $f3->get('ROOT').'/database/datas.sqlite'));
$f3->set('ENCODING','UTF-8');
$f3->set('LANGUAGE','fr-FR');
$f3->config($f3->get('ROOT').'/config/app.conf');
$f3->set('DEBUG', 3);

$today = strtotime(date('Y-m-d'));
$firstDayWeek = new \DateTime();
$lastDayWeek = new \DateTime();
$firstDayWeek->setTimestamp(strtotime('next monday', $today));
$lastDayWeek->setTimestamp(strtotime('next friday', $today));

$rows = $f3->get('DB')->exec('select b.start_date, b.user_id, b.title from booking b WHERE b.user_id IS NOT NULL AND b.start_date >= "'.$firstDayWeek->format('Y-m-d').'" AND b.start_date <= "'.$lastDayWeek->format('Y-m-d').'"');

$resultPerDates = [];
foreach ($rows as $row) {
  if (!isset($resultPerDates[$row['start_date']])) {
    $resultPerDates[$row['start_date']] = [];
  }
  $resultPerDates[$row['start_date']][] = $row['user_id'].' - '.$row['title'];
}

$nbJour = 0;
$creneaux = [];

while ($firstDayWeek <= $lastDayWeek) {
    if ($nbJour != 2) {
      if ($nbJour == 0 && !isset($resultPerDates[$firstDayWeek->format('Y-m-d')])) {
        $creneaux[] = "2 personnes pour le ".$firstDayWeek->format('d/m/Y');
      }
      elseif ($nbJour == 0 && count($resultPerDates[$firstDayWeek->format('Y-m-d')]) == 1) {
        $creneaux[] = "1 personne pour le ".$firstDayWeek->format('d/m/Y');
      }
      elseif (!isset($resultPerDates[$firstDayWeek->format('Y-m-d')])) {
        $creneaux[] = "1 personne pour le ".$firstDayWeek->format('d/m/Y');
      }
    }
    $nbJour++;
    $firstDayWeek->modify('+1 day');
}
if ($creneaux) {
  $rows = $f3->get('DB')->exec('select u.login, u.firstname, u.lastname, u.email from user u WHERE u.is_active = 1');
  $counter = 0;
  $success = 0;
  foreach ($rows as $row) {
    if (!$row['email'])
      continue;
    $f3->set('user', $row);
    $f3->set('creneaux', $creneaux);
    $message = \Template::instance()->render('emails/hebdo.html');
    if (mail($row['email'], $f3->get('email.subject_hebdo'), $message, ['From' => $f3->get('email.from')])) {
      $success++;
    }
    $counter++;
  }
  echo "$counter emails to send ($success sent successfully)";
} else {
  echo "0 email to send";
}
