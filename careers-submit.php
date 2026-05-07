<?php
/**
 * OnFoot, careers application handler (local log + optional résumé upload).
 * Ensure PHP has write access to /storage (created automatically).
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: careers.html');
    exit;
}

// Honeypot
if (!empty($_POST['website'] ?? '')) {
    header('Location: careers.html');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$position = trim((string) ($_POST['position'] ?? ''));
$employment = trim((string) ($_POST['employment'] ?? ''));
$portfolio = trim((string) ($_POST['portfolio'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: careers.html?error=1');
    exit;
}

$storage = __DIR__ . DIRECTORY_SEPARATOR . 'storage';
if (!is_dir($storage)) {
    mkdir($storage, 0755, true);
}

$logLine = sprintf(
    "%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n",
    gmdate('c'),
    str_replace(["\t", "\r", "\n"], ' ', $name),
    str_replace(["\t", "\r", "\n"], ' ', $email),
    str_replace(["\t", "\r", "\n"], ' ', $phone),
    str_replace(["\t", "\r", "\n"], ' ', $position),
    str_replace(["\t", "\r", "\n"], ' ', $employment),
    str_replace(["\t", "\r", "\n"], ' ', $portfolio),
    str_replace(["\t", "\r", "\n"], ' ', $message),
    $_SERVER['REMOTE_ADDR'] ?? ''
);
file_put_contents($storage . DIRECTORY_SEPARATOR . 'career_applications.log', $logLine, FILE_APPEND | LOCK_EX);

$resumeInfo = '';
if (!empty($_FILES['resume']['tmp_name']) && is_uploaded_file($_FILES['resume']['tmp_name'])) {
    $allowedExt = ['pdf', 'doc', 'docx'];
    $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExt, true) && $_FILES['resume']['size'] > 0 && $_FILES['resume']['size'] <= 5 * 1024 * 1024) {
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($_FILES['resume']['name'], PATHINFO_FILENAME));
        $dest = $storage . DIRECTORY_SEPARATOR . 'resume_' . date('Ymd_His') . '_' . $base . '.' . $ext;
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $dest)) {
            $resumeInfo = basename($dest);
        }
    }
}
if ($resumeInfo !== '') {
    file_put_contents($storage . DIRECTORY_SEPARATOR . 'career_applications.log', gmdate('c') . "\tRESUME_FILE\t" . $resumeInfo . "\t" . $email . "\n", FILE_APPEND | LOCK_EX);
}

header('Location: careers.html?thanks=1');
exit;
