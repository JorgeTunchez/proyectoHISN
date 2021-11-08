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
        $strQuery = "SELECT DISTINCT 
                            usuarios.id
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nickname 
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

function getRolUserSession($sessionName)
{
    $strRolUserSession = "";
    if ($sessionName != '') {
        $conn = getConexion();
        $strQuery = "SELECT DISTINCT tipo_usuario.nombre
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nickname 
                            INNER JOIN tipo_usuario 
                                    ON usuarios.tipo = tipo_usuario.id
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strRolUserSession = $row["nombre"];
            }
        }
    }

    return $strRolUserSession;
}

function draMenu($strNamePage = "", $strRolUserSession){
    if( $strRolUserSession == "admin" ){
        ?>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Inicio")? "nav-link active":"nav-link"; ?>" aria-current="page" href="menu.php">
            <i class="fas fa-star"></i>
            Inicio
            </a>
        </li>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Asignaciones")? "nav-link active":"nav-link"; ?>" href="asignaciones.php">
            <i class="fas fa-clipboard-list"></i>
            Asignaciones 
            </a>
        </li>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Herramientas")? "nav-link active":"nav-link"; ?>" href="herramientas.php">
            <i class="fas fa-tools"></i>
            Herramientas
            </a>
        </li>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Talleres")? "nav-link active":"nav-link"; ?>" href="talleres.php">
            <i class="fas fa-home"></i>
            Talleres
            </a>
        </li>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Usuarios")? "nav-link active":"nav-link"; ?>" href="usuarios.php">
            <i class="fas fa-users"></i>
            Usuarios
            </a>
        </li>
        <?php
    }
    if( $strRolUserSession == "mecanico" ){
        ?>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Inicio")? "nav-link active":"nav-link"; ?>" aria-current="page" href="menu.php">
            <i class="fas fa-star"></i>
            Inicio
            </a>
        </li>
        <li class="nav-item">
            <a class="<?php print ($strNamePage == "Asignaciones")? "nav-link active":"nav-link"; ?>" href="asignaciones.php">
            <i class="fas fa-clipboard-list"></i>
            Asignaciones 
            </a>
        </li>
        <?php
    }
}

?>