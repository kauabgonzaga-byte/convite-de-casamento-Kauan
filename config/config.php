<?php
declare(strict_types=1);

/*
 * Configurações do site.
 * Antes de publicar, altere o usuário e a senha do painel administrativo.
 * Para gerar uma nova senha: php -r "echo password_hash('NOVA_SENHA', PASSWORD_DEFAULT);"
 */
return [
    'site_name' => 'Kauã + Débora',
    'wedding_date' => '2026-11-21T18:00:00-03:00',
    'event_date' => '21 de novembro de 2026',
    'event_time' => '18h00',
    'event_place' => 'Fazenda da família, Itu - SP',
    'event_address' => 'Fazenda da família, Itu - SP',
    'admin_username' => 'admin',
    // Senha inicial: casamento2026 — altere antes de publicar.
    'admin_password_hash' => '$2y$10$UsHAnVxvYA04ur/T.y10zeHcgekBx5JPmsCzBpJje8gKkFCrSS5OC',
];
