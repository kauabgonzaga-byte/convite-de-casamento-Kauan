<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function data_file(string $name): string
{
    return __DIR__ . '/../data/' . basename($name) . '.json';
}

function read_json(string $name): array
{
    $handle = @fopen(data_file($name), 'rb');
    if ($handle === false) {
        return [];
    }

    try {
        flock($handle, LOCK_SH);
        $decoded = json_decode(stream_get_contents($handle) ?: '[]', true);
        flock($handle, LOCK_UN);
        return is_array($decoded) ? $decoded : [];
    } finally {
        fclose($handle);
    }
}

/**
 * Executa uma alteração com bloqueio exclusivo, evitando duas reservas do mesmo presente.
 * O callback recebe a lista por referência e pode devolver qualquer valor.
 *
 * @return mixed
 */
function update_json(string $name, callable $callback)
{
    $file = data_file($name);
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível abrir o arquivo de dados. Verifique a permissão de escrita da pasta data/.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Não foi possível bloquear o arquivo de dados.');
        }
        rewind($handle);
        $records = json_decode(stream_get_contents($handle) ?: '[]', true);
        if (!is_array($records)) {
            $records = [];
        }
        $result = $callback($records);
        $encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Não foi possível salvar os dados.');
        }
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $encoded . PHP_EOL);
        fflush($handle);
        flock($handle, LOCK_UN);
        return $result;
    } finally {
        fclose($handle);
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function valid_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return is_string($message) ? $message : null;
}

function redirect(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

function clean_text(?string $value, int $max = 255): string
{
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}

function is_admin(): bool
{
    return !empty($_SESSION['wedding_admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('admin_error', 'Faça login para acessar o painel.');
        redirect('login.php');
    }
}

function gift_by_id(array $gifts, string $id): ?array
{
    foreach ($gifts as $gift) {
        if (($gift['id'] ?? '') === $id) {
            return $gift;
        }
    }
    return null;
}

function gift_reservation_limit(array $gift): int
{
    return max(1, min(50, (int) ($gift['reservation_limit'] ?? 1)));
}

function gift_reservations(array $gift): array
{
    if (isset($gift['reservations']) && is_array($gift['reservations'])) {
        return array_values(array_filter($gift['reservations'], static fn ($reservation): bool => is_array($reservation) && !empty($reservation['name'])));
    }

    // Mantém compatibilidade com presentes reservados antes da criação do limite.
    if (!empty($gift['reserved_by'])) {
        return [[
            'name' => (string) $gift['reserved_by'],
            'reserved_at' => $gift['reserved_at'] ?? null,
        ]];
    }
    return [];
}

function gift_reservation_count(array $gift): int
{
    return count(gift_reservations($gift));
}

function gift_has_slots(array $gift): bool
{
    return gift_reservation_count($gift) < gift_reservation_limit($gift);
}

function gift_reserver_names(array $gift): array
{
    return array_values(array_filter(array_map(static fn (array $reservation): string => clean_text($reservation['name'] ?? '', 100), gift_reservations($gift))));
}

function gift_image_path(?string $image): ?string
{
    if (!is_string($image) || !preg_match('#^assets/images/gifts/[a-f0-9]{32}\.(?:jpg|png|webp|gif)$#', $image)) {
        return null;
    }
    return __DIR__ . '/../' . $image;
}

function upload_gift_image(?array $upload): ?string
{
    if ($upload === null || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a imagem. Tente novamente com um arquivo menor.');
    }
    if (!isset($upload['tmp_name'], $upload['size']) || !is_uploaded_file($upload['tmp_name'])) {
        throw new RuntimeException('O arquivo de imagem enviado é inválido.');
    }
    if ((int) $upload['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
    }

    $detector = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $detector === false ? false : finfo_file($detector, $upload['tmp_name']);
    if ($detector !== false) {
        finfo_close($detector);
    }
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!is_string($mime) || !isset($extensions[$mime])) {
        throw new InvalidArgumentException('Envie uma imagem JPG, PNG, WEBP ou GIF.');
    }

    $directory = __DIR__ . '/../assets/images/gifts';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }
    $relative = 'assets/images/gifts/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($upload['tmp_name'], __DIR__ . '/../' . $relative)) {
        throw new RuntimeException('Não foi possível salvar a imagem. Verifique a permissão de escrita em assets/images/gifts/.');
    }
    return $relative;
}

function delete_gift_image(?string $image): void
{
    $path = gift_image_path($image);
    if ($path !== null && is_file($path)) {
        unlink($path);
    }
}

function save_confirmation(array $input): array
{
    $name = clean_text($input['name'] ?? '', 100);
    $email = clean_text($input['email'] ?? '', 150);
    $phone = clean_text($input['phone'] ?? '', 40);
    $attendance = clean_text($input['attendance'] ?? '', 3);
    $adults = max(1, min(20, (int) ($input['adults'] ?? 1)));
    $children = max(0, min(20, (int) ($input['children'] ?? 0)));
    $note = clean_text($input['note'] ?? '', 500);
    $giftId = clean_text($input['gift_id'] ?? '', 80);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
        throw new InvalidArgumentException('Preencha nome, e-mail e telefone corretamente.');
    }
    if (!in_array($attendance, ['sim', 'nao'], true)) {
        throw new InvalidArgumentException('Informe se você estará presente.');
    }
    if (($input['consent'] ?? '') !== '1') {
        throw new InvalidArgumentException('É necessário autorizar o uso dos dados para enviar a confirmação.');
    }
    if ($attendance === 'nao') {
        $adults = 0;
        $children = 0;
    }

    $confirmationId = bin2hex(random_bytes(8));
    $giftName = '';
    if ($giftId !== '') {
        $giftName = update_json('gifts', function (array &$gifts) use ($giftId, $name, $confirmationId): string {
            foreach ($gifts as &$gift) {
                if (($gift['id'] ?? '') !== $giftId) {
                    continue;
                }
                $limit = gift_reservation_limit($gift);
                $reservations = gift_reservations($gift);
                if (count($reservations) >= $limit) {
                    throw new RuntimeException('Este presente já atingiu o limite de escolhas. Selecione outro item.');
                }
                $reservedAt = date(DATE_ATOM);
                $reservations[] = ['confirmation_id' => $confirmationId, 'name' => $name, 'reserved_at' => $reservedAt];
                $gift['reservation_limit'] = $limit;
                $gift['reservations'] = $reservations;
                $gift['status'] = count($reservations) >= $limit ? 'reserved' : 'available';
                $gift['reserved_by'] = implode(', ', gift_reserver_names($gift));
                $gift['reserved_at'] = $reservedAt;
                return (string) ($gift['name'] ?? 'Presente');
            }
            throw new RuntimeException('O presente selecionado não está mais disponível.');
        });
    }

    $confirmation = [
        'id' => $confirmationId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'attendance' => $attendance,
        'adults' => $adults,
        'children' => $children,
        'note' => $note,
        'gift_id' => $giftId,
        'gift_name' => $giftName,
        'created_at' => date(DATE_ATOM),
    ];
    update_json('confirmations', function (array &$confirmations) use ($confirmation): void {
        array_unshift($confirmations, $confirmation);
    });

    return $confirmation;
}

function format_brl($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}
