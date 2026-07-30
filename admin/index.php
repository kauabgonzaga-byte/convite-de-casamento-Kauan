<?php
declare(strict_types=1);

require __DIR__ . '/../includes/functions.php';
require_admin();

function admin_redirect(string $anchor = ''): void
{
    redirect('index.php' . $anchor);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf'] ?? null)) {
        flash('admin_error', 'Sua sessão expirou. Atualize a página e tente novamente.');
        admin_redirect();
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_gift') {
            $id = clean_text($_POST['id'] ?? '', 80);
            $name = clean_text($_POST['name'] ?? '', 120);
            $priceRaw = clean_text($_POST['price'] ?? '', 20);
            if (str_contains($priceRaw, ',')) {
                $priceRaw = str_replace(',', '.', str_replace('.', '', $priceRaw));
            }
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $priceRaw)) {
                throw new InvalidArgumentException('Informe um valor válido, por exemplo 99,90.');
            }
            $price = (float) $priceRaw;
            $icon = clean_text($_POST['icon'] ?? '✦', 8);
            $limit = (int) ($_POST['reservation_limit'] ?? 1);
            $removeImage = isset($_POST['remove_image']);
            if ($name === '' || $price <= 0) {
                throw new InvalidArgumentException('Informe o nome e um valor maior que zero.');
            }
            if ($limit < 1 || $limit > 50) {
                throw new InvalidArgumentException('O limite de escolhas deve ficar entre 1 e 50.');
            }
            $uploadedImage = upload_gift_image($_FILES['image'] ?? null);
            $oldImage = '';
            try {
                update_json('gifts', function (array &$gifts) use ($id, $name, $price, $icon, $limit, $removeImage, $uploadedImage, &$oldImage): void {
                foreach ($gifts as &$gift) {
                    if (($gift['id'] ?? '') === $id && $id !== '') {
                        $reservations = gift_reservations($gift);
                        if (count($reservations) > $limit) {
                            throw new InvalidArgumentException('Não é possível reduzir o limite abaixo das escolhas já registradas.');
                        }
                        $currentImage = (string) ($gift['image'] ?? '');
                        $gift['name'] = $name;
                        $gift['price'] = round($price, 2);
                        $gift['icon'] = $icon === '' ? '✦' : $icon;
                        $gift['image'] = $uploadedImage ?? ($removeImage ? '' : $currentImage);
                        $gift['reservation_limit'] = $limit;
                        $gift['reservations'] = $reservations;
                        $gift['status'] = count($reservations) >= $limit ? 'reserved' : 'available';
                        $gift['reserved_by'] = implode(', ', gift_reserver_names($gift));
                        if ($gift['image'] !== $currentImage) {
                            $oldImage = $currentImage;
                        }
                        return;
                    }
                }
                $gifts[] = [
                    'id' => 'gift-' . bin2hex(random_bytes(5)),
                    'name' => $name,
                    'price' => round($price, 2),
                    'icon' => $icon === '' ? '✦' : $icon,
                    'image' => $uploadedImage ?? '',
                    'reservation_limit' => $limit,
                    'reservations' => [],
                    'status' => 'available',
                    'reserved_by' => '',
                    'reserved_at' => null,
                ];
                });
            } catch (Throwable $exception) {
                if ($uploadedImage !== null) {
                    delete_gift_image($uploadedImage);
                }
                throw $exception;
            }
            if ($oldImage !== '') {
                delete_gift_image($oldImage);
            }
            flash('admin_success', $id === '' ? 'Presente adicionado.' : 'Presente atualizado.');
            admin_redirect('#presentes');
        }

        if ($action === 'release_gift') {
            $id = clean_text($_POST['id'] ?? '', 80);
            update_json('gifts', function (array &$gifts) use ($id): void {
                foreach ($gifts as &$gift) {
                    if (($gift['id'] ?? '') === $id) {
                        $gift['status'] = 'available';
                        $gift['reservations'] = [];
                        $gift['reserved_by'] = '';
                        $gift['reserved_at'] = null;
                        return;
                    }
                }
                throw new RuntimeException('Presente não encontrado.');
            });
            flash('admin_success', 'Presente liberado novamente.');
            admin_redirect('#presentes');
        }

        if ($action === 'delete_gift') {
            $id = clean_text($_POST['id'] ?? '', 80);
            $image = '';
            update_json('gifts', function (array &$gifts) use ($id, &$image): void {
                $before = count($gifts);
                $gifts = array_values(array_filter($gifts, static function (array $gift) use ($id, &$image): bool {
                    if (($gift['id'] ?? '') === $id) {
                        $image = (string) ($gift['image'] ?? '');
                        return false;
                    }
                    return true;
                }));
                if (count($gifts) === $before) {
                    throw new RuntimeException('Presente não encontrado.');
                }
            });
            delete_gift_image($image);
            flash('admin_success', 'Presente removido.');
            admin_redirect('#presentes');
        }

        if ($action === 'delete_confirmation') {
            $id = clean_text($_POST['id'] ?? '', 80);
            update_json('confirmations', function (array &$confirmations) use ($id): void {
                $before = count($confirmations);
                $confirmations = array_values(array_filter($confirmations, static fn (array $confirmation): bool => ($confirmation['id'] ?? '') !== $id));
                if (count($confirmations) === $before) {
                    throw new RuntimeException('Confirmação não encontrada.');
                }
            });
            flash('admin_success', 'Confirmação removida. O presente relacionado não foi liberado automaticamente.');
            admin_redirect('#confirmacoes');
        }
    } catch (Throwable $exception) {
        flash('admin_error', $exception->getMessage());
        admin_redirect();
    }
}

$gifts = read_json('gifts');
$editId = clean_text($_GET['edit'] ?? '', 80);
$editingGift = gift_by_id($gifts, $editId);
$editingGiftImage = $editingGift !== null && gift_image_path($editingGift['image'] ?? null) !== null ? (string) $editingGift['image'] : '';
$confirmations = read_json('confirmations');
$reserved = array_filter($gifts, static fn (array $gift): bool => ($gift['status'] ?? '') === 'reserved');
$attending = array_filter($confirmations, static fn (array $confirmation): bool => ($confirmation['attendance'] ?? '') === 'sim');
$guests = array_sum(array_map(static fn (array $confirmation): int => (int) ($confirmation['adults'] ?? 0) + (int) ($confirmation['children'] ?? 0), $attending));
$success = flash('admin_success');
$error = flash('admin_error');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel administrativo | <?= h(config()['site_name']) ?></title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
  <header class="admin-header"><a href="../index.php" class="admin-brand">Kauã <i>+</i> Débora</a><nav><a href="#presentes">Presentes</a><a href="#confirmacoes">Confirmações</a><a href="logout.php">Sair</a></nav></header>
  <main class="admin-shell">
    <section class="admin-intro"><div><p class="admin-kicker">visão geral</p><h1>Organização da celebração</h1><p>Administre a lista de presentes e acompanhe as confirmações em um só lugar.</p></div><a class="admin-export" href="export.php">Baixar confirmações (CSV)</a></section>
    <?php if ($success): ?><p class="admin-notice success" role="status"><?= h($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="admin-notice error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <section class="stat-grid" aria-label="Resumo"><article><strong><?= count($gifts) ?></strong><span>presentes na lista</span></article><article><strong><?= count($reserved) ?></strong><span>presentes escolhidos</span></article><article><strong><?= count($confirmations) ?></strong><span>respostas recebidas</span></article><article><strong><?= $guests ?></strong><span>pessoas confirmadas</span></article></section>

    <section class="admin-section" id="presentes">
      <div class="section-title"><div><p class="admin-kicker">lista</p><h2>Presentes</h2></div></div>
      <div class="gift-manager">
        <form class="admin-form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save_gift"><input type="hidden" name="id" value="<?= h($editingGift['id'] ?? '') ?>">
          <h3><?= $editingGift === null ? 'Adicionar presente' : 'Editar presente' ?></h3><label for="gift-name">Nome</label><input id="gift-name" name="name" value="<?= h($editingGift['name'] ?? '') ?>" required maxlength="120"><label for="gift-price">Valor (R$)</label><input id="gift-price" name="price" inputmode="decimal" placeholder="99,90" value="<?= $editingGift !== null ? h(number_format((float) ($editingGift['price'] ?? 0), 2, ',', '.')) : '' ?>" required><label for="gift-limit">Limite de escolhas</label><input id="gift-limit" name="reservation_limit" type="number" min="1" max="50" value="<?= $editingGift !== null ? gift_reservation_limit($editingGift) : 1 ?>" required><small class="input-hint">Ex.: 2 permite duas pessoas escolherem este mesmo presente.</small><label for="gift-icon">Símbolo</label><input id="gift-icon" name="icon" value="<?= h($editingGift['icon'] ?? '✦') ?>" maxlength="8"><label for="gift-image">Foto do presente</label><input id="gift-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small class="input-hint">JPG, PNG, WEBP ou GIF, até 5 MB.</small><?php if ($editingGiftImage !== ''): ?><div class="admin-image-preview"><img src="../<?= h($editingGiftImage) ?>" alt="Foto atual de <?= h($editingGift['name'] ?? 'presente') ?>"><label class="image-remove"><input type="checkbox" name="remove_image" value="1"> Remover foto atual</label></div><?php endif; ?><button type="submit"><?= $editingGift === null ? 'Adicionar à lista' : 'Salvar alterações' ?></button><?php if ($editingGift !== null): ?><a class="cancel-edit" href="index.php#presentes">Cancelar edição</a><?php endif; ?>
        </form>
        <div class="admin-table-wrap"><table><thead><tr><th>Foto</th><th>Presente</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead><tbody>
          <?php foreach ($gifts as $gift): ?>
            <?php $giftImage = gift_image_path($gift['image'] ?? null) !== null ? (string) $gift['image'] : ''; $reservationCount = gift_reservation_count($gift); $reservationLimit = gift_reservation_limit($gift); $fullyReserved = $reservationCount >= $reservationLimit; $reserverNames = gift_reserver_names($gift); ?>
            <tr><td><?php if ($giftImage !== ''): ?><img class="gift-thumb" src="../<?= h($giftImage) ?>" alt="Foto de <?= h($gift['name'] ?? 'presente') ?>"><?php else: ?><span class="gift-thumb-placeholder" aria-label="Sem foto"><?= h($gift['icon'] ?? '✦') ?></span><?php endif; ?></td><td><strong><?= h($gift['name'] ?? '') ?></strong><?php if ($reserverNames !== []): ?><small>por <?= h(implode(', ', $reserverNames)) ?></small><?php endif; ?></td><td><?= format_brl($gift['price'] ?? 0) ?></td><td><span class="status <?= $fullyReserved ? 'reserved' : 'available' ?>"><?= $reservationCount ?> de <?= $reservationLimit ?> escolhido(s)<?= $fullyReserved ? ' · indisponível' : '' ?></span></td><td class="actions">
              <a class="button-link" href="?edit=<?= rawurlencode((string) ($gift['id'] ?? '')) ?>#presentes">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="release_gift"><input type="hidden" name="id" value="<?= h($gift['id'] ?? '') ?>"><button type="submit" class="button-link"<?= $reservationCount > 0 ? '' : ' disabled' ?>>Liberar</button></form>
              <form method="post" data-confirm="Remover este presente da lista?"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_gift"><input type="hidden" name="id" value="<?= h($gift['id'] ?? '') ?>"><button type="submit" class="button-link danger">Remover</button></form>
            </td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      </div>
    </section>

    <section class="admin-section" id="confirmacoes">
      <div class="section-title"><div><p class="admin-kicker">respostas</p><h2>Confirmações</h2></div></div>
      <div class="admin-table-wrap"><table class="confirmation-table"><thead><tr><th>Convidado</th><th>Presença</th><th>Pessoas</th><th>Presente</th><th>Recebido em</th><th></th></tr></thead><tbody>
        <?php if ($confirmations === []): ?><tr><td colspan="6" class="empty-cell">Nenhuma confirmação recebida ainda.</td></tr><?php endif; ?>
        <?php foreach ($confirmations as $confirmation): ?>
          <tr><td><strong><?= h($confirmation['name'] ?? '') ?></strong><small><?= h($confirmation['email'] ?? '') ?><br><?= h($confirmation['phone'] ?? '') ?><?= !empty($confirmation['note']) ? '<br>Obs.: ' . h($confirmation['note']) : '' ?></small></td><td><?= ($confirmation['attendance'] ?? '') === 'sim' ? 'Confirmou' : 'Não irá' ?></td><td><?= (int) ($confirmation['adults'] ?? 0) ?> adulto(s)<br><?= (int) ($confirmation['children'] ?? 0) ?> criança(s)</td><td><?= h(($confirmation['gift_name'] ?? '') ?: '—') ?></td><td><?= !empty($confirmation['created_at']) ? h(date('d/m/Y H:i', strtotime($confirmation['created_at']))) : '—' ?></td><td><form method="post" data-confirm="Remover esta confirmação?"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete_confirmation"><input type="hidden" name="id" value="<?= h($confirmation['id'] ?? '') ?>"><button type="submit" class="button-link danger">Remover</button></form></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </section>
  </main>
  <script>document.querySelectorAll('[data-confirm]').forEach(function (form) { form.addEventListener('submit', function (event) { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }); });</script>
</body>
</html>
