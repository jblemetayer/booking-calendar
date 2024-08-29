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


$date = date('Y-m-d', strtotime('+'.$f3->get('day_reminder_email_delay').' days'));
$rows = $f3->get('DB')->exec('select u.login, u.firstname, u.lastname, u.email, u.event from user u INNER JOIN booking b ON u.id = b.user_id WHERE u.is_active = 1 AND b.start_date = "'.$date.'"');
$counter = 0;
$success = 0;
foreach ($rows as $row) {
  if (!$row['email'])
    continue;
  $f3->set('user', $row);
  $f3->set('date', date('d/m/Y', strtotime('+'.$f3->get('day_reminder_email_delay').' days')));
  $message = \Template::instance()->render('emails/reminder.html');
  $sended = mb_send_mail($row['email'], $f3->get('email.subject_reminder'), $message, ['From' => $f3->get('email.from'), 'Content-Type' => 'text/plain; charset=UTF-8', 'MIME-Version' => '1.0', 'Content-Transfer-Encoding' => '8bit']);
  if ($sended) {
    $success++;
  }
  $counter++;
}

echo "$counter emails to send ($success sent successfully)";
