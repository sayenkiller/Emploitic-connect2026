<?php
$to = 'your.email@gmail.com';
$subject = 'PHP Mail Test';
$message = 'This is a test';
$headers = "Content-Type: text/html; charset=UTF-8\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✓ mail() executed (email might have been sent)";
} else {
    echo "✗ mail() failed (check with hosting provider)";
}
?>