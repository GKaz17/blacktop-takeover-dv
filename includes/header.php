<?php
require_once __DIR__ . '/data.php';
$pageTitle = $pageTitle ?? 'Blacktop Takeover';
$pageDescription = $pageDescription ?? 'Blacktop Takeover tournament system.';
$activePage = $activePage ?? '';
$bodyClass = $bodyClass ?? '';
$hideNavigation = $hideNavigation ?? false;
$documentTitle = $pageTitle === 'Blacktop Takeover'
    ? $pageTitle
    : $pageTitle . ' | Blacktop Takeover';
$stylesheetVersion = (string) filemtime(__DIR__ . '/../assets/css/styles.css');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($documentTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Bebas+Neue&family=Inter:wght@400;500;600&family=Rubik+Spray+Paint&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/blacktop-takeover/assets/css/styles.css?v=<?= e($stylesheetVersion) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php if (!$hideNavigation) require __DIR__ . '/navigation.php'; ?>
<main id="main-content">
