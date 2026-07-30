<?php
declare(strict_types=1);

require __DIR__ . '/includes/functions.php';

$config = config();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rsvp') {
    if (!valid_csrf($_POST['csrf'] ?? null)) {
        flash('rsvp_error', 'Sua sessão expirou. Atualize a página e tente novamente.');
    } else {
        try {
            $confirmation = save_confirmation($_POST);
            $message = $confirmation['gift_name'] !== ''
                ? 'Obrigada! Sua presença e o presente “' . $confirmation['gift_name'] . '” foram registrados.'
                : 'Obrigada! Sua confirmação foi registrada.';
            flash('rsvp_success', $message);
        } catch (Throwable $exception) {
            flash('rsvp_error', $exception->getMessage());
        }
    }
    redirect('index.php#rsvp');
}

$gifts = read_json('gifts');
$selectedGiftId = clean_text($_GET['gift'] ?? '', 80);
$selectedGift = gift_by_id($gifts, $selectedGiftId);
if ($selectedGift !== null && !gift_has_slots($selectedGift)) {
    $selectedGift = null;
    $selectedGiftId = '';
}
$rsvpSuccess = flash('rsvp_success');
$rsvpError = flash('rsvp_error');
$pageTitle = $config['site_name'] . ' | Nosso casamento';

require __DIR__ . '/includes/header.php';
?>
  <main id="conteudo">
    <section class="hero section" id="inicio" aria-labelledby="hero-title">
      <img class="hero-floral hero-floral-left" src="assets/images/floral-corner.svg" alt="" aria-hidden="true">
      <img class="hero-floral hero-floral-right" src="assets/images/floral-corner.svg" alt="" aria-hidden="true">
      <img class="hero-photo" src="assets/images/foto-casal.png" alt="Kauã e Débora">
      <div class="hero-inner reveal">
        <p class="eyebrow">com alegria, celebramos nosso amor</p>
        <h1 id="hero-title">KAUÃ <em>♥</em> DÉBORA</h1>
        <p class="hero-date">21 . 11 . 2026</p>
        <a class="round-link" href="#contagem" aria-label="Ver contagem regressiva">↓</a>
      </div>
      <div class="hero-arch" aria-hidden="true"><span></span><span></span><span></span></div>
    </section>

    <section class="countdown section section-soft" id="contagem" aria-labelledby="countdown-title" data-wedding-date="<?= h($config['wedding_date']) ?>">
      <div class="section-heading reveal">
        <p class="eyebrow">falta pouco</p>
        <h2 id="countdown-title">Contagem regressiva</h2>
        <div class="leaf-divider" aria-hidden="true">❦</div>
      </div>
      <div class="clock reveal" aria-live="polite">
        <div><strong data-countdown="days">--</strong><span>dias</span></div>
        <div><strong data-countdown="hours">--</strong><span>horas</span></div>
        <div><strong data-countdown="minutes">--</strong><span>minutos</span></div>
        <div><strong data-countdown="seconds">--</strong><span>segundos</span></div>
      </div>
    </section>

    <section class="section story" id="detalhes" aria-labelledby="story-title">
      <div class="story-art reveal" aria-hidden="true">
        <span class="story-sun"></span><span class="story-rings"><i></i><i></i></span>
        <span class="story-stem stem-one"></span><span class="story-stem stem-two"></span>
      </div>
      <div class="story-copy reveal">
        <p class="eyebrow">nosso grande dia</p>
        <h2 id="story-title">Uma nova história começa aqui</h2>
        <p>Depois de tantos encontros, conversas e sonhos compartilhados, chegou a hora de celebrar o nosso “sim”. Sua presença deixa esse momento ainda mais especial.</p>
        <a class="text-link" href="#rsvp">Confirmar presença <span>→</span></a>
      </div>
    </section>

    <section class="events section section-soft" aria-labelledby="event-title">
      <article class="event-card reveal">
        <div class="event-icon" aria-hidden="true">✦</div>
        <p class="eyebrow">para brindar</p>
        <h2 id="event-title">Celebração</h2>
        <p>Depois da cerimônia, continuamos a festa com música, abraços e muitas memórias para guardar.</p>
        <dl>
          <div><dt>Quando</dt><dd><?= h($config['event_date']) ?><br><?= h($config['event_time']) ?></dd></div>
          <div><dt>Onde</dt><dd><?= h($config['event_place']) ?></dd></div>
        </dl>
        <button class="outline-button" type="button" data-copy-address="<?= h($config['event_address']) ?>">Copiar endereço</button>
      </article>
    </section>

    <section class="gallery section" aria-labelledby="gallery-title">
      <div class="section-heading reveal">
        <p class="eyebrow">instantes que guardamos</p>
        <h2 id="gallery-title">Nosso caminho</h2>
        <div class="leaf-divider" aria-hidden="true">❦</div>
      </div>
      <div class="gallery-grid reveal">
        <?php foreach (['gallery-1.svg', 'gallery-2.svg', 'gallery-3.svg', 'gallery-4.svg', 'gallery-5.svg'] as $index => $image): ?>
          <img src="assets/images/<?= h($image) ?>" alt="Ilustração decorativa <?= $index + 1 ?>">
        <?php endforeach; ?>
      </div>
    </section>

    <section class="gifts section section-tint" id="presentes" aria-labelledby="gifts-title">
      <div class="section-heading reveal">
        <p class="eyebrow">com carinho</p>
        <h2 id="gifts-title">Lista de presentes</h2>
        <div class="leaf-divider" aria-hidden="true">❦</div>
        <p class="section-intro">Se quiser nos presentear, escolha um item e depois confirme sua presença. Assim, ele fica reservado para você.</p>
      </div>
      <div class="gift-toolbar reveal"><p><span><?= count($gifts) ?></span> sugestões para celebrar com a gente</p><p>Itens reservados aparecem indisponíveis.</p></div>
      <div class="gift-grid">
        <?php foreach ($gifts as $gift): ?>
          <?php $available = gift_has_slots($gift); $giftImage = gift_image_path($gift['image'] ?? null) !== null ? (string) $gift['image'] : ''; $remainingSlots = gift_reservation_limit($gift) - gift_reservation_count($gift); ?>
          <article class="gift-card<?= $available ? '' : ' gift-card-reserved' ?> reveal">
            <div class="gift-art<?= $giftImage !== '' ? ' has-image' : '' ?>" aria-hidden="true"><?php if ($giftImage !== ''): ?><img src="<?= h($giftImage) ?>" alt=""><?php else: ?><span><?= h($gift['icon'] ?? '✦') ?></span><?php endif; ?></div>
            <div class="gift-info">
              <h3><?= h($gift['name'] ?? 'Presente') ?></h3>
              <p class="gift-price"><?= format_brl($gift['price'] ?? 0) ?></p>
              <?php if ($available): ?>
                <p class="gift-availability"><?= $remainingSlots ?> <?= $remainingSlots === 1 ? 'escolha disponível' : 'escolhas disponíveis' ?></p>
                <a class="outline-button gift-choice" href="?gift=<?= rawurlencode((string) $gift['id']) ?>#rsvp">Escolher presente</a>
              <?php else: ?>
                <span class="gift-status">Indisponível</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="rsvp section" id="rsvp" aria-labelledby="rsvp-title">
      <div class="rsvp-heading reveal">
        <p class="eyebrow">nos conte</p>
        <h2 id="rsvp-title">Você vem celebrar com a gente?</h2>
        <p>A confirmação nos ajuda a deixar cada detalhe pronto para receber vocês.</p>
        <?php if ($selectedGift !== null): ?>
          <div class="selected-gift"><small>Presente escolhido</small><strong><?= h($selectedGift['name'] ?? '') ?></strong><a href="#presentes">Trocar</a></div>
        <?php endif; ?>
      </div>
      <form class="rsvp-form reveal" method="post" action="index.php#rsvp" novalidate>
        <input type="hidden" name="action" value="rsvp">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="gift_id" value="<?= h($selectedGiftId) ?>">
        <?php if ($rsvpSuccess): ?><p class="notice success field-full" role="status"><?= h($rsvpSuccess) ?></p><?php endif; ?>
        <?php if ($rsvpError): ?><p class="notice error field-full" role="alert"><?= h($rsvpError) ?></p><?php endif; ?>
        <div class="field field-full"><label for="rsvp-name">Nome completo</label><input id="rsvp-name" name="name" autocomplete="name" required></div>
        <fieldset class="field-full attendance"><legend>Você estará presente?</legend><label><input type="radio" name="attendance" value="sim" required> Sim, estarei</label><label><input type="radio" name="attendance" value="nao"> Não conseguirei ir</label></fieldset>
        <div class="field"><label for="rsvp-adults">Adultos, incluindo você</label><select id="rsvp-adults" name="adults"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></div>
        <div class="field"><label for="rsvp-children">Crianças</label><select id="rsvp-children" name="children"><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></div>
        <div class="field"><label for="rsvp-email">E-mail</label><input id="rsvp-email" name="email" type="email" autocomplete="email" required></div>
        <div class="field"><label for="rsvp-phone">Telefone</label><input id="rsvp-phone" name="phone" inputmode="tel" autocomplete="tel" required></div>
        <div class="field field-full"><label for="rsvp-note">Observações</label><textarea id="rsvp-note" name="note" rows="3" placeholder="Alguma informação que devemos saber?"></textarea></div>
        <label class="check field-full"><input type="checkbox" name="consent" value="1" required><span>Li e concordo com a utilização destas informações apenas para a organização da celebração.</span></label>
        <div class="field-full form-actions"><button class="solid-button" type="submit">Enviar confirmação</button></div>
      </form>
    </section>
  </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
