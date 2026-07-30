<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? config()['site_name'];
$config = config();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Site de casamento de Kauã e Débora.">
  <title><?= h($pageTitle) ?></title>
  <link rel="icon" href="assets/images/gallery-3.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
  <header class="topbar" data-topbar>
    <a class="brand" href="#inicio" aria-label="Início">K <span>+</span> D</a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-menu-toggle>
      <span class="sr-only">Abrir menu</span>☰
    </button>
    <nav class="site-nav" id="site-nav" data-site-nav aria-label="Navegação principal">
      <a href="#inicio">Início</a>
      <a href="#detalhes">Detalhes</a>
      <a href="#presentes">Presentes</a>
      <a href="#rsvp">Confirmação</a>
    </nav>
    <a class="topbar-rsvp" href="#rsvp">Confirmar presença</a>
  </header>
