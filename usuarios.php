<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
if (isset($_SESSION['user_id'])) {
    $strRolUserSession = getRolUserSession($_SESSION['user_id']);
    $intIDUserSession = getIDUserSession($_SESSION['user_id']);

    if ($strRolUserSession != '') {
        $arrRolUser["ID"] = $intIDUserSession;
        $arrRolUser["NAME"] = $_SESSION['user_id'];

        if ($strRolUserSession == "admin") {
            $arrRolUser["ADMIN"] = true;
        } elseif ($strRolUserSession == "mecanico") {
            $arrRolUser["MECANICO"] = true;
        }
    }
} else {
    header("Location: index.php");
}
$objController = new usuario_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class usuario_controller{
    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new usuario_model();
        $this->objView = new usuario_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController()
    {
        $this->objView->drawContent();
    }

    public function runAjax()
    {
        $this->ajaxDestroySession();
    }

    public function ajaxDestroySession()
    {
        if (isset($_POST["destroSession"])) {
            header("Content-Type: application/json;");
            session_destroy();
            $arrReturn["Correcto"] = "Y";
            print json_encode($arrReturn);
            exit();
        }
    }

    public function process(){
        $intUser = intval($this->arrRolUser["ID"]);
        reset($_POST);
        while ($arrTMP = each($_POST)) {
            $arrExplode = explode("_", $arrTMP['key']);

            if ($arrExplode[0] == "hdnUsuario") {
                $intUsuario = $arrExplode[1];
                $strAccion = isset($_POST["hdnUsuario_{$intUsuario}"]) ? trim($_POST["hdnUsuario_{$intUsuario}"]) : '';
                $strNickname = isset($_POST["txtNickname_{$intUsuario}"]) ? trim($_POST["txtNickname_{$intUsuario}"]) : '';
                $strPassword = isset($_POST["txtPassword_{$intUsuario}"]) ? trim($_POST["txtPassword_{$intUsuario}"]) : '';
                $strNombres = isset($_POST["txtNombres_{$intUsuario}"]) ? trim($_POST["txtNombres_{$intUsuario}"]) : '';
                $strApellidos = isset($_POST["txtApellidos_{$intUsuario}"]) ? trim($_POST["txtApellidos_{$intUsuario}"]) : '';
                $intTaller = isset($_POST["selectTaller_{$intUsuario}"]) ? intval($_POST["selectTaller_{$intUsuario}"]) : 0;
                

                if ($strAccion == "A") {
                    $this->objModel->insertUsuario($strNickname, $strPassword, $strNombres, $strApellidos, $intTaller, $intUser);
                } elseif ($strAccion == "D") {
                    $this->objModel->deleteUsuario($intUsuario);
                } elseif ($strAccion == "E") {
                    $this->objModel->updateUsuario($intUsuario, $strNickname, $strPassword, $strNombres, $strApellidos, $intTaller, $intUser);
                }
            }
        }
    }

}

class usuario_model{

    public function getUsuarios(){
        $conn = getConexion();
        $arrUsuarios = array();
        $strQuery = "SELECT usuarios.id, 
                            usuarios.nickname, 
                            usuarios.password, 
                            usuarios.nombres, 
                            usuarios.apellidos, 
                            talleres.id taller_id,
                            talleres.nombre taller_nombre 
                       FROM usuarios
                       INNER JOIN talleres ON usuarios.taller = talleres.id
                       WHERE usuarios.tipo = 2 
                      ORDER BY usuarios.nickname ASC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrUsuarios[$row["id"]]["NICKNAME"] = $row["nickname"];
                $arrUsuarios[$row["id"]]["PASSWORD"] = $row["password"];
                $arrUsuarios[$row["id"]]["NOMBRES"] = $row["nombres"];
                $arrUsuarios[$row["id"]]["APELLIDOS"] = $row["apellidos"];
                $arrUsuarios[$row["id"]]["TALLER_ID"] = $row["taller_id"];
                $arrUsuarios[$row["id"]]["TALLER_NOMBRE"] = $row["taller_nombre"];
            }
        }
        return $arrUsuarios;
    }

    public function insertUsuario($strNickname, $strPassword, $strNombres, $strApellidos, $intTaller, $intUser){
        if ($strNickname != '' && $strPassword != '' && $strNombres != '' && $strApellidos != '' && $intTaller > 0 && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "INSERT INTO usuarios (nickname, password, tipo, nombres, apellidos, taller, add_fecha, add_user) 
                                       VALUES ('{$strNickname}', '{$strPassword}', 2, '{$strNombres}', '{$strApellidos}', {$intTaller}, now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
    }

    public function deleteUsuario($intUsuario){
        if ($intUsuario > 0) {
            $conn = getConexion();
            $strQuery = "DELETE FROM usuarios WHERE id = {$intUsuario}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateUsuario($intUsuario, $strNickname, $strPassword, $strNombres, $strApellidos, $intTaller, $intUser){
        if ($intUsuario > 0 && $strNickname != '' && $strPassword != '' && $strNombres != '' && $strApellidos != '' && $intTaller > 0 && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "UPDATE usuarios 
                            SET nickname = '{$strNickname}',
                                password = '{$strPassword}',
                                nombres = '{$strNombres}',
                                apellidos = '{$strApellidos}',
                                taller = {$intTaller},
                                mod_fecha = now(),
                                mod_user = {$intUser} 
                          WHERE id = {$intUsuario}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function getTalleres(){
        $conn = getConexion();
        $arrTalleres = array();
        $strQuery = "SELECT id, nombre, nit, direccion FROM talleres ORDER BY nombre ASC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrTalleres[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }
        return $arrTalleres;
    }
}

class usuario_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new usuario_model();
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContent(){
        ?>
        <html>
            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <title>Inventario Herramientas</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
                <link rel="icon" href="images/tools.png" type="image/x-icon" />
                <style>

                    .bd-placeholder-img {
                        font-size: 1.125rem;
                        text-anchor: middle;
                        -webkit-user-select: none;
                        -moz-user-select: none;
                        user-select: none;
                    }

                    @media (min-width: 768px) {
                        .bd-placeholder-img-lg {
                        font-size: 3.5rem;
                        }
                    }

                    body {
                        font-size: .875rem;
                    }

                    .feather {
                        width: 16px;
                        height: 16px;
                        vertical-align: text-bottom;
                    }

                    .sidebar {
                        position: fixed;
                        top: 0;
                        bottom: 0;
                        left: 0;
                        z-index: 100;
                        padding: 48px 0 0; 
                        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
                    }

                    @media (max-width: 767.98px) {
                        .sidebar {
                            top: 5rem;
                        }
                    }

                    .sidebar-sticky {
                        position: relative;
                        top: 0;
                        height: calc(100vh - 48px);
                        padding-top: .5rem;
                        overflow-x: hidden;
                        overflow-y: auto;
                    }

                    .sidebar .nav-link {
                        font-weight: 500;
                        color: #333;
                    }

                    .sidebar .nav-link .feather {
                        margin-right: 4px;
                        color: #727272;
                    }

                    .sidebar .nav-link.active {
                        color: #2470dc;
                    }

                    .sidebar .nav-link:hover .feather,
                    .sidebar .nav-link.active .feather {
                        color: inherit;
                    }

                    .sidebar-heading {
                        font-size: .75rem;
                        text-transform: uppercase;
                    }

                    .navbar-brand {
                        padding-top: .75rem;
                        padding-bottom: .75rem;
                        font-size: 1rem;
                        background-color: rgba(0, 0, 0, .25);
                        color: #fff;
                        box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
                    }

                    .navbar .navbar-toggler {
                        top: .25rem;
                        right: 1rem;
                    }

                    .navbar .form-control {
                        padding: .75rem 1rem;
                        border-width: 0;
                        border-radius: 0;
                    }

                    .navbarsession{
                        color:#fff;
                    }
                </style>
            </head>
            <body>
                <header class="navbar navbar-info sticky-top bg-info flex-md-nowrap p-0 shadow">
                    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="menu.php">Inventario Herramientas</a>
                    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="navbar-info">
                        <div class="nav-item text-nowrap" onclick="destroSession()" style="cursor:pointer;">
                        <a class="navbarsession px-3" href="#">Cerrar Session</a>
                        </div>
                    </div>
                </header>
                <div class="container-fluid">
                    <div class="row">
                        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                        <div class="position-sticky pt-3">
                            <ul class="nav flex-column">
                                <?php 
                                draMenu("Usuarios");
                                ?>
                            </ul>            
                        </div>
                        </nav>

                        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <div class="row">
                                <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                                    <h1 class="h2">Usuarios</h1>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-info" onclick="agregarUsuario()" title="Agregar"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-success" onclick="checkForm()" title="Guardar"><i class="far fa-save"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tblUsuarios">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>NickName</th>
                                    <th>Password</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Taller</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $arrTalleres = $this->objModel->getTalleres();
                                $arrUsuarios = $this->objModel->getUsuarios();
                                $intConteo = 0;
                                reset($arrTalleres);
                                while ($rTMP = each($arrUsuarios)) {
                                    $intConteo++;
                                    $intID = $rTMP["key"];
                                    $strNickname = trim($rTMP["value"]["NICKNAME"]);
                                    $strPassword = trim($rTMP["value"]["PASSWORD"]);
                                    $strNombres = trim($rTMP["value"]["NOMBRES"]);
                                    $strApellidos = trim($rTMP["value"]["APELLIDOS"]);
                                    $intTaller = intval($rTMP["value"]["TALLER_ID"]);
                                    $strTallerNombre = trim($rTMP["value"]["TALLER_NOMBRE"]);
                                    ?>
                                    <tr id="trUsuario_<?php print $intID; ?>">
                                        <td data-title="No." style="text-align:left; vertical-align:middle;">
                                            <h3><?php print $intConteo; ?></h3>
                                            <input id="hdnUsuario_<?php print $intID; ?>" name="hdnUsuario_<?php print $intID; ?>" type="hidden" value="N">
                                        </td>
                                        <td data-title="Nickname" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowUsuarioNickname_<?php print $intID; ?>">
                                                <?php print $strNickname; ?>
                                            </div>
                                            <div id="divEditUsuarioNickname_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtNickname_<?php print $intID; ?>" name="txtNickname_<?php print $intID; ?>" value="<?php print $strNickname; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Password" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowUsuarioPassword_<?php print $intID; ?>">
                                                <?php print $strPassword; ?>
                                            </div>
                                            <div id="divEditUsuarioPassword_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtPassword_<?php print $intID; ?>" name="txtPassword_<?php print $intID; ?>" value="<?php print $strPassword; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Nombres" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowUsuarioNombres_<?php print $intID; ?>">
                                                <?php print $strNombres; ?>
                                            </div>
                                            <div id="divEditUsuarioNombres_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtNombres_<?php print $intID; ?>" name="txtNombres_<?php print $intID; ?>" value="<?php print $strNombres; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Apellidos" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowUsuarioApellidos_<?php print $intID; ?>">
                                                <?php print $strApellidos; ?>
                                            </div>
                                            <div id="divEditUsuarioApellidos_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtApellidos_<?php print $intID; ?>" name="txtApellidos_<?php print $intID; ?>" value="<?php print $strApellidos; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Taller" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowUsuarioTaller_<?php print $intID; ?>">
                                                <?php print $strTallerNombre; ?>
                                            </div>
                                            <div id="divEditUsuarioTaller_<?php print $intID; ?>" style="display:none;">
                                                <select id="selectTaller_<?php print $intID; ?>" name="selectTaller_<?php print $intID; ?>" style="text-align: center;" class="form-control">
                                                    <?php
                                                    reset($arrTalleres);
                                                    while ($rTMP = each($arrTalleres)) {
                                                        $strSelected = (($rTMP["key"] == $intTaller)) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php print $rTMP["key"]; ?>" <?php print $strSelected; ?>><?php print $rTMP["value"]["NOMBRE"]; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </td>
                                        
                                        <td data-title="Acciones" style="text-align:left;">
                                            <button class="btn btn-info btn-block" onclick="editUsuario('<?php print $intID; ?>')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-block" onclick="deleteUsuario('<?php print $intID; ?>')" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        </div>
                        <!-- Fin Contenido -->
                        </main>
                    </div>
                    </div>



                <script src="js/jquery.min.js"></script>
                <script>
                    function destroSession() {
                        if (confirm("¿Desea salir de la aplicación?")) {
                            $.ajax({
                                url: "usuarios.php",
                                data: {
                                    destroSession: true
                                },
                                type: "post",
                                dataType: "json",
                                success: function(data) {
                                    if (data.Correcto == "Y") {
                                        alert("Usted ha cerrado sesión");
                                        location.href = "index.php";
                                    }
                                }
                            });
                        }
                    }

                    function editUsuario(id) {
                        $("#divEditUsuarioNickname_" + id).show();
                        $("#divShowUsuarioNickname_" + id).hide();

                        $("#divEditUsuarioPassword_" + id).show();
                        $("#divShowUsuarioPassword_" + id).hide();

                        $("#divEditUsuarioNombres_" + id).show();
                        $("#divShowUsuarioNombres_" + id).hide();

                        $("#divEditUsuarioApellidos_" + id).show();
                        $("#divShowUsuarioApellidos_" + id).hide();

                        $("#divEditUsuarioTaller_" + id).show();
                        $("#divShowUsuarioTaller_" + id).hide();

                        $("#hdnUsuario_" + id).val("E");
                    }

                    function deleteUsuario(id) {
                        $("#trUsuario_" + id).css('background-color', '#f4d0de');
                        $("#hdnUsuario_" + id).val("D");
                    }

                    function fntGetCountUsuario() {
                        var intCount = 0;
                        $("input[name*='txtNombres_']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function fntGetCountMax() {
                        var valores = [];
                        var intCount = 0;
                        $("input[name*='hdnTaller_']").each(function() {
                            var arrSplit = $(this).attr("id").split("_");
                            valores.push(arrSplit[1]);
                        });
                        var max = parseInt(Math.max.apply(null, valores));
                        if (isNaN(max)) {
                            max = fntGetCountUsuario();
                        }
                        return max + 1;
                    }

                    var intFilasUsuario = 0;

                    function agregarUsuario() {
                        intFilasUsuario = fntGetCountUsuario();
                        intFilasUsuario++;

                        max = fntGetCountMax();

                        var $tabla = $("#tblUsuarios");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasUsuario + "<b><input class='form-control' type='hidden' id='hdnUsuario_" + max + "' name='hdnUsuario_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nickname' style='text-align:center;'><input class='form-control' type='text' id='txtNickname_" + max + "' name='txtNickname_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Password' style='text-align:center;'><input class='form-control' type='text' id='txtPassword_" + max + "' name='txtPassword_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombres' style='text-align:center;'><input class='form-control' type='text' id='txtNombres_" + max + "' name='txtNombres_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Apellidos' style='text-align:center;'><input class='form-control' type='text' id='txtApellidos_" + max + "' name='txtApellidos_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Taller' style='text-align:center;'><select class='form-control' id='selectTaller_" + max + "' name='selectTaller_" + max + "' style='text-align: center;'><?php $arrTalleres = $this->objModel->getTalleres(); reset($arrTalleres); while ($rTMP = each($arrTalleres)) { ?><option value='<?php print $rTMP["key"]; ?>'><?php print $rTMP["value"]["NOMBRE"]; ?></option><?php } ?></select></td>");
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    function checkForm() {
                        var response = confirm("Desea confirmar los cambios?");
                        if (response == true) {
                            var boolError = false;
                            $("input[name*='txtNickname_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtPassword_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtNombres_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtApellidos_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            if (boolError == false) {
                                var objSerialized = $("#tblUsuarios").find("select, input").serialize();
                                $.ajax({
                                    url: "usuarios.php",
                                    data: objSerialized,
                                    type: "POST",
                                    beforeSend: function() {
                                        $("#divShowLoadingGeneralBig").show();
                                    },
                                    success: function(data) {
                                        $("#divShowLoadingGeneralBig").hide();
                                        location.href = "usuarios.php";
                                    }
                                });
                            } else {
                                alert('Faltan campos por llenar o revisar que no existan campos duplicados');
                            }
                        }
                    }

                </script>
            </body>
        </html>
        <?php
    }
}
?>