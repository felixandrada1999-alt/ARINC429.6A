<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$error = '';
$editingInstrument = null;
$successMessages = [
    'created' => 'Instrumento creado correctamente.',
    'updated' => 'Instrumento actualizado correctamente.',
    'deleted' => 'Instrumento eliminado correctamente.',
];
$messageKey = $_GET['mensaje'] ?? '';
$successMessage = is_string($messageKey) ? ($successMessages[$messageKey] ?? '') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(400);
        $error = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'delete') {
                $id = filter_input(INPUT_POST, 'ins_id', FILTER_VALIDATE_INT);
                if (!$id || $id < 1) {
                    throw new InvalidArgumentException('El identificador del instrumento no es válido.');
                }

                $statement = $db->prepare('DELETE FROM instrumentos WHERE ins_id = ?');
                $statement->bind_param('i', $id);
                $statement->execute();
                $statement->close();

                header('Location: index.php?mensaje=deleted');
                exit;
            }

            if ($action === 'create' || $action === 'update') {
                $submittedDescription = $_POST['ins_descripcion'] ?? null;
                if (!is_string($submittedDescription)) {
                    throw new InvalidArgumentException('La descripción debe ser texto.');
                }

                $description = trim($submittedDescription);
                if ($description === '' || mb_strlen($description) > 50) {
                    throw new InvalidArgumentException('La descripción es obligatoria y debe tener como máximo 50 caracteres.');
                }

                if ($action === 'create') {
                    $statement = $db->prepare('INSERT INTO instrumentos (ins_descripcion) VALUES (?)');
                    $statement->bind_param('s', $description);
                    $statement->execute();
                    $statement->close();

                    header('Location: index.php?mensaje=created');
                    exit;
                }

                $id = filter_input(INPUT_POST, 'ins_id', FILTER_VALIDATE_INT);
                if (!$id || $id < 1) {
                    throw new InvalidArgumentException('El identificador del instrumento no es válido.');
                }

                $statement = $db->prepare('UPDATE instrumentos SET ins_descripcion = ? WHERE ins_id = ?');
                $statement->bind_param('si', $description, $id);
                $statement->execute();
                $statement->close();

                header('Location: index.php?mensaje=updated');
                exit;
            }

            throw new InvalidArgumentException('La acción solicitada no es válida.');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error = 'No se pudo completar la operación. Verifica la conexión y vuelve a intentarlo.';
        }
    }
}

if (isset($_GET['editar'])) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        $statement = $db->prepare('SELECT ins_id, ins_descripcion FROM instrumentos WHERE ins_id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $editingInstrument = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
    }
}

$result = $db->query('SELECT ins_id, ins_descripcion FROM instrumentos ORDER BY ins_id');
$instruments = $result->fetch_all(MYSQLI_ASSOC);

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRUD de instrumentos</title>
    <style>
        body { font-family: sans-serif; margin: 2rem auto; max-width: 760px; padding: 0 1rem; }
        h1, h2 { color: #17324d; }
        form { margin: 1rem 0; }
        input[type="text"] { box-sizing: border-box; max-width: 28rem; padding: .55rem; width: 100%; }
        button, .button { background: #1769aa; border: 0; color: white; cursor: pointer; padding: .5rem .75rem; text-decoration: none; }
        .delete { background: #a52a2a; }
        table { border-collapse: collapse; margin-top: 1rem; width: 100%; }
        th, td { border: 1px solid #ccc; padding: .6rem; text-align: left; }
        .message { background: #e8f5e9; padding: .75rem; }
        .error { background: #ffebee; padding: .75rem; }
        .actions { display: flex; gap: .5rem; }
        .actions form { margin: 0; }
    </style>
</head>
<body>
    <h1>Instrumentos</h1>

    <?php if ($successMessage !== ''): ?>
        <p class="message"><?= escape($successMessage) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="error"><?= escape($error) ?></p>
    <?php endif; ?>

    <h2><?= $editingInstrument ? 'Editar instrumento' : 'Agregar instrumento' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
        <input type="hidden" name="action" value="<?= $editingInstrument ? 'update' : 'create' ?>">
        <?php if ($editingInstrument): ?>
            <input type="hidden" name="ins_id" value="<?= (int) $editingInstrument['ins_id'] ?>">
        <?php endif; ?>
        <label for="ins_descripcion">Descripción (máximo 50 caracteres)</label><br>
        <input
            id="ins_descripcion"
            name="ins_descripcion"
            type="text"
            maxlength="50"
            required
            value="<?= escape($editingInstrument['ins_descripcion'] ?? '') ?>"
        >
        <button type="submit"><?= $editingInstrument ? 'Guardar cambios' : 'Crear instrumento' ?></button>
        <?php if ($editingInstrument): ?>
            <a class="button" href="index.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <h2>Listado de instrumentos</h2>
    <?php if ($instruments === []): ?>
        <p>No hay instrumentos registrados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Descripción</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($instruments as $instrument): ?>
                    <tr>
                        <td><?= (int) $instrument['ins_id'] ?></td>
                        <td><?= escape($instrument['ins_descripcion']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="button" href="?editar=<?= (int) $instrument['ins_id'] ?>">Editar</a>
                                <form method="post" onsubmit="return confirm('¿Eliminar este instrumento?');">
                                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="ins_id" value="<?= (int) $instrument['ins_id'] ?>">
                                    <button class="delete" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
