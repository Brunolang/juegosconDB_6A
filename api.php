<?php

declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function getDatabase(): PDO
{
    $settings = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $settings['host'],
        $settings['port'],
        $settings['database']
    );
    return new PDO($dsn, $settings['username'], $settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function readJson(): array
{
    $body = file_get_contents('php://input');
    $data = json_decode($body ?: '', true);
    if (!is_array($data)) {
        respond(['error' => 'El cuerpo de la solicitud no es JSON válido.'], 400);
    }
    return $data;
}

function validId(mixed $raw): int
{
    $id = filter_var($raw, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        respond(['error' => 'El ID de usuario no es válido.'], 400);
    }
    return $id;
}

function validateUser(array $data): array
{
    $fields = [];
    foreach (['usu_usuario' => 'usuario', 'usu_nombre' => 'nombre', 'usu_apellido' => 'apellido'] as $key => $label) {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value === '') {
            respond(['error' => "El campo {$label} es obligatorio."], 422);
        }
        if (preg_match_all('/./us', $value) > 50) {
            respond(['error' => "El campo {$label} no puede superar 50 caracteres."], 422);
        }
        $fields[$key] = $value;
    }
    return $fields;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pathId = $_GET['id'] ?? null;

try {
    $pdo = getDatabase();

    if ($method === 'GET') {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if ($pathId !== null) {
            $stmt = $pdo->prepare('SELECT usu_id, usu_usuario, usu_nombre, usu_apellido FROM usuarios WHERE usu_id = :id');
            $stmt->execute(['id' => validId($pathId)]);
            $user = $stmt->fetch();
            if (!$user) {
                respond(['error' => 'No se encontró ese usuario.'], 404);
            }
            respond(['usuario' => $user, 'csrfToken' => $_SESSION['csrf_token']]);
        }
        $users = $pdo->query(
            'SELECT usu_id, usu_usuario, usu_nombre, usu_apellido FROM usuarios ORDER BY usu_id DESC'
        )->fetchAll();
        respond(['usuarios' => $users, 'csrfToken' => $_SESSION['csrf_token']]);
    }

    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE');
        respond(['error' => 'Método no permitido.'], 405);
    }

    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        respond(['error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.'], 419);
    }

    if ($method === 'POST') {
        $user = validateUser(readJson());
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (usu_usuario, usu_nombre, usu_apellido) VALUES (:usu_usuario, :usu_nombre, :usu_apellido)'
        );
        $stmt->execute($user);
        respond(['mensaje' => 'Usuario creado correctamente.', 'id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT') {
        $id = validId($pathId);
        $user = validateUser(readJson());
        $user['id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET usu_usuario = :usu_usuario, usu_nombre = :usu_nombre, usu_apellido = :usu_apellido WHERE usu_id = :id'
        );
        $stmt->execute($user);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM usuarios WHERE usu_id = :id');
            $exists->execute(['id' => $id]);
            if (!$exists->fetchColumn()) {
                respond(['error' => 'No se encontró ese usuario.'], 404);
            }
        }
        respond(['mensaje' => 'Usuario actualizado correctamente.']);
    }

    $id = validId($pathId);
    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE usu_id = :id');
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() === 0) {
        respond(['error' => 'No se encontró ese usuario.'], 404);
    }
    respond(['mensaje' => 'Usuario eliminado correctamente.']);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    respond([
        'error' => 'No se pudo conectar a MySQL o consultar usuarios. Verifica config.php, la base juegos_6A, los permisos del usuario y que la tabla exista.',
    ], 500);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    respond(['error' => 'Ocurrió un error interno en el servidor.'], 500);
}
