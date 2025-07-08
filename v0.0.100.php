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

// --- Manejar descarga antes de cualquier salida ---
if (isset($_SESSION['username']) && isset($_GET['descargar'])) {
    $baseDir = __DIR__ . '/../usuarios';
    $user = basename($_SESSION['username']);
    $userDir = $baseDir . '/' . $user;

    $ruta = isset($_GET['ruta']) ? $_GET['ruta'] : '';
    $ruta = str_replace('..', '', $ruta);
    $currentDir = realpath($userDir . '/' . $ruta);
    if (strpos($currentDir, realpath($userDir)) !== 0) exit("Ruta no permitida.");

    $archivo = basename($_GET['descargar']);
    $rutaDescarga = $currentDir . '/' . $archivo;

    if (file_exists($rutaDescarga)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($rutaDescarga));
        readfile($rutaDescarga);
        exit;
    } else {
        exit("Archivo no encontrado.");
    }
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

<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    h1, h2, h3 {
        color: #2c3e50;
    }

    form {
        background: #fff;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        max-width: 400px;
    }

    input[type="text"],
    input[type="password"],
    input[type="file"],
    input[type="submit"],
    button,
    textarea {
        display: block;
        width: 100%;
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    input[type="submit"],
    button {
        background: #3498db;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }

    input[type="submit"]:hover,
    button:hover {
        background: #2980b9;
    }

    ul {
        list-style: none;
        padding: 0;
    }

    li {
        background: #fff;
        margin-bottom: 10px;
        padding: 10px;
        border-left: 5px solid #3498db;
        border-radius: 4px;
        box-shadow: 0 0 3px rgba(0,0,0,0.05);
    }

    a {
        color: #3498db;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .message {
        background: #ffefc4;
        border-left: 5px solid #f39c12;
        padding: 10px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .editor textarea {
        width: 100%;
        font-family: monospace;
        resize: vertical;
        background: #f9f9f9;
        border: 1px solid #ccc;
    }

    .logout-btn {
        margin-top: 10px;
        background: #e74c3c;
    }

    .logout-btn:hover {
        background: #c0392b;
    }
</style>

    <h2>Estás en línea como <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></h2>
    <form method="post"><button name="logout">Cerrar sesión</button></form>
    <div id="slot_355610"><script src="https://linkslot.ru/bancode_new.php?id=355610" async></script></div>

    <?php
    $baseDir = __DIR__ . '/../usuarios';
    if (!is_dir($baseDir)) mkdir($baseDir, 0775, true);

    $user = basename($_SESSION['username']);
    $userDir = $baseDir . '/' . $user;
    if (!is_dir($userDir)) mkdir($userDir, 0775, true);

    $ruta = isset($_GET['ruta']) ? $_GET['ruta'] : '';
    $ruta = str_replace('..', '', $ruta);
    $currentDir = realpath($userDir . '/' . $ruta);
    if (strpos($currentDir, realpath($userDir)) !== 0) $currentDir = $userDir;

    $rutaURL = urlencode($ruta);

    if (isset($_FILES['archivo'])) {
        $nombre = basename($_FILES['archivo']['name']);
        $destino = $currentDir . '/' . $nombre;
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
            echo "<p><strong>Archivo subido correctamente.</strong></p>";
        } else {
            echo "<p><strong>Error al subir el archivo.</strong></p>";
        }
    }

    if (isset($_POST['crearArchivo']) && isset($_POST['nuevoNombre'])) {
        $nuevo = basename(trim($_POST['nuevoNombre']));
        if ($nuevo) {
            file_put_contents($currentDir . '/' . $nuevo, "");
        }
    }

    if (isset($_POST['crearCarpeta']) && isset($_POST['nuevaCarpeta'])) {
        $nueva = basename(trim($_POST['nuevaCarpeta']));
        if ($nueva) {
            mkdir($currentDir . '/' . $nueva, 0775, true);
        }
    }

    if (isset($_POST['guardarArchivo']) && isset($_POST['nombreArchivo']) && isset($_POST['contenido'])) {
        $archivo = basename($_POST['nombreArchivo']);
        file_put_contents($currentDir . '/' . $archivo, $_POST['contenido']);
    }

    if (isset($_GET['eliminar'])) {
        $archivo = basename($_GET['eliminar']);
        $rutaEliminar = $currentDir . '/' . $archivo;
        if (file_exists($rutaEliminar)) unlink($rutaEliminar);
    }

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

    <h3>Suba sus archivos en este formulario:</h3>
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

<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    h1, h2, h3 {
        color: #2c3e50;
    }

    form {
        background: #fff;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        max-width: 400px;
    }

    input[type="text"],
    input[type="password"],
    input[type="file"],
    input[type="submit"],
    button,
    textarea {
        display: block;
        width: 100%;
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    input[type="submit"],
    button {
        background: #3498db;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }

    input[type="submit"]:hover,
    button:hover {
        background: #2980b9;
    }

    ul {
        list-style: none;
        padding: 0;
    }

    li {
        background: #fff;
        margin-bottom: 10px;
        padding: 10px;
        border-left: 5px solid #3498db;
        border-radius: 4px;
        box-shadow: 0 0 3px rgba(0,0,0,0.05);
    }

    a {
        color: #3498db;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .message {
        background: #ffefc4;
        border-left: 5px solid #f39c12;
        padding: 10px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .editor textarea {
        width: 100%;
        font-family: monospace;
        resize: vertical;
        background: #f9f9f9;
        border: 1px solid #ccc;
    }

    .logout-btn {
        margin-top: 10px;
        background: #e74c3c;
    }

    .logout-btn:hover {
        background: #c0392b;
    }
</style>


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

<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    h1, h2, h3 {
        color: #2c3e50;
    }

    form {
        background: #fff;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        max-width: 400px;
    }

    input[type="text"],
    input[type="password"],
    input[type="file"],
    input[type="submit"],
    button,
    textarea {
        display: block;
        width: 100%;
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    input[type="submit"],
    button {
        background: #3498db;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }

    input[type="submit"]:hover,
    button:hover {
        background: #2980b9;
    }

    ul {
        list-style: none;
        padding: 0;
    }

    li {
        background: #fff;
        margin-bottom: 10px;
        padding: 10px;
        border-left: 5px solid #3498db;
        border-radius: 4px;
        box-shadow: 0 0 3px rgba(0,0,0,0.05);
    }

    a {
        color: #3498db;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .message {
        background: #ffefc4;
        border-left: 5px solid #f39c12;
        padding: 10px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .editor textarea {
        width: 100%;
        font-family: monospace;
        resize: vertical;
        background: #f9f9f9;
        border: 1px solid #ccc;
    }

    .logout-btn {
        margin-top: 10px;
        background: #e74c3c;
    }

    .logout-btn:hover {
        background: #c0392b;
    }
</style>

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