<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

function getConexion()
{

    $servername = "localhost:3308";
    $username = "root";
    $password = "";
    $dbname = "proyectohisn";
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        return $conn;
    }
}

function auth_user($username, $password)
{
    $conn = getConexion();
    $arrValues = array();

    if ($username != '') {
        $strQuery = "SELECT password FROM usuarios WHERE nickname = '{$username}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrValues["PASSWORD"] = $row["password"];
            }
        }
    }

    if (isset($arrValues["PASSWORD"])) {
        if (($arrValues["PASSWORD"] == $password)) {
            session_start();
            $_SESSION['user_id'] = $username;
            $strValueSession = $_SESSION['user_id'];
            insertSession($strValueSession);
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function insertSession($strSession)
{
    if ($strSession != '') {
        $conn = getConexion();
        $strQuery = "INSERT INTO session_user (nombre, add_user, add_fecha) VALUES ('{$strSession}', 1, now())";
        mysqli_query($conn, $strQuery);
    }
}

function getIDUserSession($sessionName)
{
    $intIDUserSession = "";
    if ($sessionName != '') {
        $conn = getConexion();
        $strQuery = "SELECT usuarios.id
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $intIDUserSession = $row["id"];
            }
        }
    }

    return $intIDUserSession;
}

function draMenu($strNamePage = "")
{
    ?>
    <li class="nav-item">
        <a class="<?php print ($strNamePage == "Inicio")? "nav-link active":"nav-link"; ?>" aria-current="page" href="menu.php">
        <span data-feather="home"></span>
        Inicio
        </a>
    </li>
    <li class="nav-item">
        <a class="<?php print ($strNamePage == "Asignaciones")? "nav-link active":"nav-link"; ?>" href="asignaciones.php">
        <span data-feather="file"></span>
        Asignaciones 
        </a>
    </li>
    <li class="nav-item">
        <a class="<?php print ($strNamePage == "Herramientas")? "nav-link active":"nav-link"; ?>" href="herramientas.php">
        <span data-feather="shopping-cart"></span>
        Herramientas
        </a>
    </li>
    <li class="nav-item">
        <a class="<?php print ($strNamePage == "Talleres")? "nav-link active":"nav-link"; ?>" href="talleres.php">
        <span data-feather="users"></span>
        Talleres
        </a>
    </li>
    <li class="nav-item">
        <a class="<?php print ($strNamePage == "Usuarios")? "nav-link active":"nav-link"; ?>" href="usuarios.php">
        <span data-feather="bar-chart-2"></span>
        Usuarios
        </a>
    </li>
    <?php
}

?>