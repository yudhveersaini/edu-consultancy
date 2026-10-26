<?php
/* ==========================================================================
 * EduVista Admissions — counselling enquiry handler
 *
 * Both enquiry forms (home hero + contact page) post here with fetch() and
 * read back JSON. Mail goes out through PHP's own mail(), which shared cPanel
 * hosting already provides.
 *
 * From stays on the site's own domain (hosting rejects or spam-folders
 * anything else); the visitor's address goes in Reply-To.
 * ========================================================================== */

date_default_timezone_set('Asia/Kolkata');

/* ------------------------------------------------------------ where to */
$TO = 'info@eduvista.in';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
  && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

header('X-Content-Type-Options: nosniff');
header($isAjax
  ? 'Content-Type: application/json; charset=utf-8'
  : 'Content-Type: text/html; charset=utf-8');

/* --------------------------------------------------------------- replies */
function page($heading, $text) {
  $h = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
  $t = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

  echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
     . '<meta name="viewport" content="width=device-width, initial-scale=1">'
     . '<title>' . $h . ' | EduVista Admissions</title>'
     . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;'
     . 'background:#0a1733;color:#dfe5ef;padding:24px;'
     . 'font:400 15px/1.6 system-ui,-apple-system,Segoe UI,sans-serif}'
     . '.box{max-width:460px;text-align:center}h1{font:600 26px Georgia,serif;margin:0 0 10px;color:#fff}'
     . 'a{color:#d9b86c;font-weight:600}</style></head>'
     . '<body><div class="box"><h1>' . $h . '</h1><p>' . $t . '</p>'
     . '<p><a href="contact.html">&larr; Contact page par wapas jayein</a></p>'
     . '</div></body></html>';
}

function fail($message, $status = 400) {
  global $isAjax;
  http_response_code($status);
  if ($isAjax) echo json_encode(array('ok' => false, 'error' => $message));
  else page('Enquiry bhej nahi paaye', $message);
  exit;
}

function done() {
  global $isAjax;
  if ($isAjax) echo json_encode(array('ok' => true));
  else page('Shukriya!', 'Aapki enquiry hamari team tak pahunch gayi hai. Counsellor 24 ghante mein sampark karenge.');
  exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  fail('Ye page sirf form submit ke liye hai.', 405);
}

/* ---------------------------------------------------------------- inputs */
function field($key, $max) {
  $value = isset($_POST[$key]) ? $_POST[$key] : '';
  if (!is_string($value)) return '';
  $value = trim($value);
  return function_exists('mb_substr')
    ? mb_substr($value, 0, $max)
    : substr($value, 0, $max);
}

/* Header values must never contain line breaks (header injection). */
function oneline($value) {
  return trim(str_replace(array("\r", "\n", "\0"), ' ', $value));
}

$name          = oneline(field('name', 120));
$phone         = oneline(field('phone', 40));
$email         = oneline(field('email', 160));
$city          = oneline(field('city', 80));
$qualification = oneline(field('qualification', 80));
$course        = oneline(field('course', 120));
$source        = oneline(field('source', 60));
$message       = field('message', 4000);

/* Honeypot: bots fill every field. Answer OK so they stop retrying. */
if (field('website', 200) !== '') done();

if ($name === '' || $phone === '' || $course === '') {
  fail('Naam, mobile number aur course zaroor bhariye.');
}

if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
  fail('Mobile number sahi nahi lag raha.');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail('Email address sahi nahi lag raha.');
}

/* ------------------------------------------------------------------ mail */
$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$host = preg_replace('/:\d+$/', '', $host);
$host = preg_replace('/^www\./', '', $host);
$host = preg_replace('/[^a-z0-9.\-]/', '', $host);
$from = ($host !== '' && strpos($host, '.') !== false) ? 'no-reply@' . $host : $TO;

$subject = 'Counselling Enquiry — ' . $name . ' (' . $course . ')';

$dash = function ($v) { return $v !== '' ? $v : '-'; };
$body =
  "New counselling enquiry from the website.\n" .
  "------------------------------------------\n\n" .
  "Name          : " . $name . "\n" .
  "Phone         : " . $phone . "\n" .
  "Email         : " . $dash($email) . "\n" .
  "City          : " . $dash($city) . "\n" .
  "Qualification : " . $dash($qualification) . "\n" .
  "Course        : " . $course . "\n" .
  "Form          : " . $dash($source) . "\n\n" .
  "Message :\n" . $dash($message) . "\n\n" .
  "------------------------------------------\n" .
  "Sent          : " . date('d M Y, h:i A') . "\n" .
  "IP            : " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-') . "\n";

$headers = array(
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'From: EduVista Admissions <' . $from . '>',
  'Reply-To: ' . ($email !== '' ? $email : $TO),
  'X-Mailer: EduVista-Website'
);

$sent = @mail(
  $TO,
  '=?UTF-8?B?' . base64_encode($subject) . '?=',
  $body,
  implode("\r\n", $headers),
  '-f' . $from
);

if (!$sent) {
  fail('Mail abhi bhej nahi paaye. Kripya call ya WhatsApp kijiye.', 500);
}

done();
