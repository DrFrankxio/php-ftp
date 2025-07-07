<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$usersFile = "../users.txt";

// --- Funciones ---
function userExists($username) {
    global $usersFile;
    if (!file_exists($usersFile)) return false;
    $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($user, $hash) = explode(":", $line);
        if ($user === $username) return true;
    }
    return false;
}

function verifyUser($username, $password) {
    global $usersFile;
    if (!file_exists($usersFile)) return false;
    $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($user, $hash) = explode(":", $line);
        if ($user === $username && password_verify($password, $hash)) {
            return true;
        }
    }
    return false;
}

// --- Acciones POST ---
if (isset($_POST["registerSubmit1"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    if (userExists($username)) {
        $message = "El usuario '$username' ya existe.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        file_put_contents($usersFile, "$username:$hash\n", FILE_APPEND);
        $message = "Usuario '$username' registrado correctamente.";
    }
}

if (isset($_POST["loginSubmit1"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    if (verifyUser($username, $password)) {
        $_SESSION['username'] = $username;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Usuario o contraseña incorrectos.";
    }
}

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>
<meta charset="utf-8">
<?php if (isset($_SESSION['username'])): ?>
    <h2>Estás en línea como <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></h2>
    <form method="post"><button name="logout">Cerrar sesión</button></form>

    <?php
    $baseDir = __DIR__ . '/../usuarios';
    if (!is_dir($baseDir)) mkdir($baseDir, 0775, true);

    $user = basename($_SESSION['username']);
    $userDir = $baseDir . '/' . $user;
    if (!is_dir($userDir)) mkdir($userDir, 0775, true);

    // Ruta actual (subdirectorio)
    $ruta = isset($_GET['ruta']) ? $_GET['ruta'] : '';
    $ruta = str_replace('..', '', $ruta); // Evitar salir del directorio base
    $currentDir = realpath($userDir . '/' . $ruta);
    if (strpos($currentDir, realpath($userDir)) !== 0) $currentDir = $userDir;

    $rutaURL = urlencode($ruta);

    // Subida de archivo
    if (isset($_FILES['archivo'])) {
        $nombre = basename($_FILES['archivo']['name']);
        $destino = $currentDir . '/' . $nombre;
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
            echo "<p><strong>Archivo subido correctamente.</strong></p>";
        } else {
            echo "<p><strong>Error al subir el archivo.</strong></p>";
        }
    }

    // Descargar archivo
    if (isset($_GET['descargar'])) {
        $archivo = basename($_GET['descargar']);
        $rutaDescarga = $currentDir . '/' . $archivo;
        if (file_exists($rutaDescarga)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $archivo . '"');
            header('Content-Length: ' . filesize($rutaDescarga));
            readfile($rutaDescarga);
            exit;
        } else {
            echo "<p><strong>Archivo no encontrado.</strong></p>";
        }
    }

    // Crear archivo
    if (isset($_POST['crearArchivo']) && isset($_POST['nuevoNombre'])) {
        $nuevo = basename(trim($_POST['nuevoNombre']));
        if ($nuevo) {
            file_put_contents($currentDir . '/' . $nuevo, "");
        }
    }

    // Crear carpeta
    if (isset($_POST['crearCarpeta']) && isset($_POST['nuevaCarpeta'])) {
        $nueva = basename(trim($_POST['nuevaCarpeta']));
        if ($nueva) {
            mkdir($currentDir . '/' . $nueva, 0775, true);
        }
    }

    // Guardar archivo editado
    if (isset($_POST['guardarArchivo']) && isset($_POST['nombreArchivo']) && isset($_POST['contenido'])) {
        $archivo = basename($_POST['nombreArchivo']);
        file_put_contents($currentDir . '/' . $archivo, $_POST['contenido']);
    }

    // Eliminar archivo
    if (isset($_GET['eliminar'])) {
        $archivo = basename($_GET['eliminar']);
        $rutaEliminar = $currentDir . '/' . $archivo;
        if (file_exists($rutaEliminar)) unlink($rutaEliminar);
    }

    // Listar contenido
    $contenido = array_diff(scandir($currentDir), ['.', '..']);
    ?>

    <h3>Ruta actual: /<?= htmlspecialchars($ruta) ?></h3>
    <?php if ($ruta): ?>
        <?php
            $parent = dirname($ruta);
            if ($parent === ".") $parent = "";
        ?>
        <p><a href="?ruta=<?= urlencode($parent) ?>">🔙 Subir un nivel</a></p>
    <?php endif; ?>

    <h3>Subir archivo</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="archivo" required>
        <input type="hidden" name="ruta" value="<?= htmlspecialchars($ruta) ?>">
        <button>Subir</button>
    </form>

    <h3>Crear nuevo archivo</h3>
    <form method="post">
        Nombre: <input name="nuevoNombre" required>
        <input type="hidden" name="ruta" value="<?= htmlspecialchars($ruta) ?>">
        <button name="crearArchivo">Crear</button>
    </form>

    <h3>Crear carpeta</h3>
    <form method="post">
        Carpeta: <input name="nuevaCarpeta" required>
        <input type="hidden" name="ruta" value="<?= htmlspecialchars($ruta) ?>">
        <button name="crearCarpeta">Crear</button>
    </form>

    <h3>Contenido</h3>
    <ul>
        <?php foreach ($contenido as $item): ?>
            <li>
                <?php if (is_dir($currentDir . '/' . $item)): ?>
                    📁 <a href="?ruta=<?= urlencode(trim($ruta . '/' . $item, '/')) ?>"><?= htmlspecialchars($item) ?></a>
                <?php else: ?>
                    📄 <?= htmlspecialchars($item) ?>
                    [<a href="?ruta=<?= $rutaURL ?>&descargar=<?= urlencode($item) ?>">Descargar</a>]
                    [<a href="?ruta=<?= $rutaURL ?>&editar=<?= urlencode($item) ?>">Editar</a>]
                    [<a href="?ruta=<?= $rutaURL ?>&eliminar=<?= urlencode($item) ?>" onclick="return confirm('¿Eliminar archivo?')">Eliminar</a>]
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php
    // Editor de archivos
    if (isset($_GET['editar'])):
        $archivoEditar = basename($_GET['editar']);
        $rutaEditar = $currentDir . '/' . $archivoEditar;
        if (file_exists($rutaEditar)):
            $contenidoArchivo = htmlspecialchars(file_get_contents($rutaEditar));
    ?>
        <h3>Editando archivo: <?= htmlspecialchars($archivoEditar) ?></h3>
        <form method="post">
            <input type="hidden" name="nombreArchivo" value="<?= htmlspecialchars($archivoEditar) ?>">
            <input type="hidden" name="ruta" value="<?= htmlspecialchars($ruta) ?>">
            <textarea name="contenido" rows="10" cols="60"><?= $contenidoArchivo ?></textarea><br>
            <button name="guardarArchivo">Guardar cambios</button>
        </form>
    <?php endif; endif; ?>

<?php else: ?>
    <?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>
    <?php $view = $_GET['view'] ?? 'login'; ?>

    <?php if ($view === 'register'): ?>
        <h1>Registrarse:</h1>
        <form method="post" action="?view=register">
            Usuario: <input name="username" required><br>
            Contraseña: <input type="password" name="password" required><br>
            <input type="submit" name="registerSubmit1" value="Registrarse"><br>
        </form>
        <form method="get">
            <input type="hidden" name="view" value="login">
            <button>Ir a Iniciar sesión</button>
        </form>

    <?php else: ?>
        <h1>Entrar:</h1>
        <form method="post" action="?view=login">
            Usuario: <input name="username" required><br>
            Contraseña: <input type="password" name="password" required><br>
            <input type="submit" name="loginSubmit1" value="Entrar"><br>
        </form>
        <form method="get">
            <input type="hidden" name="view" value="register">
            <button>Ir a Registrarse</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
