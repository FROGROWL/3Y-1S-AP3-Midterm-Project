<?php

$isAdmin = (user()['role'] ?? '') === 'Administrator';
$layoutTitle = 'Overview';
$pdo = db();
$manilaToday = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$todayDate = $manilaToday->format('Y-m-d');
$watchPerPage = 7;

