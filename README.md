# Site de casamento — Kauã + Débora

Site em PHP sem banco de dados. As escolhas de presentes e as confirmações ficam em arquivos JSON editáveis na pasta `data/`.

## Publicação

1. Envie todos os arquivos desta pasta para um servidor com PHP 7.4+.
2. Garanta que o servidor tenha permissão de escrita em `data/gifts.json`, `data/confirmations.json` e `assets/images/gifts/`.
3. Ajuste data, local e credenciais em `config/config.php` antes de publicar.

## Painel oculto

Abra diretamente `seu-dominio.com/admin/login.php`.

- Usuário inicial: `admin`
- Senha inicial: `casamento2026`

Troque a senha antes de publicar, gerando um hash novo com o comando indicado em `config/config.php`.

## Como funciona

- O convidado escolhe um presente e confirma os dados no formulário.
- A reserva usa bloqueio de arquivo para impedir que duas pessoas reservem o mesmo item ao mesmo tempo.
- No painel, é possível adicionar, editar, fotografar, liberar ou remover presentes, ver e exportar confirmações e remover respostas.
- As fotos podem ser JPG, PNG, WEBP ou GIF e têm limite de 5 MB por arquivo.
- Os arquivos JSON podem ser editados manualmente quando o site não estiver recebendo confirmações.
