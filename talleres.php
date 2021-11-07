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
$objController = new taller_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class taller_controller{
    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new taller_model();
        $this->objView = new taller_view($arrRolUser);
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

            if ($arrExplode[0] == "hdnTaller") {
                $intTaller = $arrExplode[1];
                $strAccion = isset($_POST["hdnTaller_{$intTaller}"]) ? trim($_POST["hdnTaller_{$intTaller}"]) : '';
                $strNombre = isset($_POST["txtNombre_{$intTaller}"]) ? trim($_POST["txtNombre_{$intTaller}"]) : '';
                $strDireccion = isset($_POST["txtDireccion_{$intTaller}"]) ? trim($_POST["txtDireccion_{$intTaller}"]) : '';
                $strNit = isset($_POST["txtNit_{$intTaller}"]) ? trim($_POST["txtNit_{$intTaller}"]) : '';
                

                if ($strAccion == "A") {
                    $this->objModel->insertTaller($strNombre, $strDireccion, $strNit, $intUser);
                } elseif ($strAccion == "D") {
                    $this->objModel->deleteTaller($intTaller);
                } elseif ($strAccion == "E") {
                    $this->objModel->updateTaller($intTaller, $strNombre, $strDireccion, $strNit, $intUser);
                }
            }
        }
    }

}

class taller_model{

    public function getTalleres(){
        $conn = getConexion();
        $arrTalleres = array();
        $strQuery = "SELECT id, nombre, nit, direccion FROM talleres ORDER BY nombre ASC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrTalleres[$row["id"]]["NOMBRE"] = $row["nombre"];
                $arrTalleres[$row["id"]]["DIRECCION"] = $row["direccion"];
                $arrTalleres[$row["id"]]["NIT"] = $row["nit"];
            }
        }
        return $arrTalleres;
    }

    public function insertTaller($strNombre, $strDireccion, $strNit, $intUser){
        if ($strNombre != '' && $strDireccion != '' && $strNit != '' && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "INSERT INTO talleres (nombre, direccion, nit, add_fecha, add_user) 
                                       VALUES ('{$strNombre}', '{$strDireccion}', '{$strNit}', now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
    }

    public function deleteTaller($intTaller){
        if ($intTaller > 0) {
            $conn = getConexion();
            $strQuery = "DELETE FROM talleres WHERE id = {$intTaller}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateTaller($intTaller, $strNombre, $strDireccion, $strNit, $intUser){
        if ($intTaller > 0 && $strNombre != '' && $strDireccion != '' && $strNit != '' && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "UPDATE talleres 
                            SET nombre = '{$strNombre}',
                                direccion = '{$strDireccion}',
                                nit = '{$strNit}',
                                mod_fecha = now(),
                                mod_user = {$intUser} 
                          WHERE id = {$intTaller}";
            mysqli_query($conn, $strQuery);
        }
    }

}

class taller_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new taller_model();
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
                                draMenu("Talleres");
                                ?>
                            </ul>            
                        </div>
                        </nav>
                        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <div class="row">
                                <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                                    <h1 class="h2">Talleres</h1>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-info" onclick="agregarTaller()" title="Agregar"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-success" onclick="checkForm()" title="Guardar"><i class="far fa-save"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tblTalleres">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>NIT</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $arrTalleres = $this->objModel->getTalleres();
                                $intConteo = 0;
                                reset($arrTalleres);
                                while ($rTMP = each($arrTalleres)) {
                                    $intConteo++;
                                    $intID = $rTMP["key"];
                                    $strNombre = trim($rTMP["value"]["NOMBRE"]);
                                    $strDireccion = trim($rTMP["value"]["DIRECCION"]);
                                    $strNit = trim($rTMP["value"]["NIT"]);
                                    ?>
                                    <tr id="trTaller_<?php print $intID; ?>">
                                        <td data-title="No." style="text-align:left; vertical-align:middle;">
                                            <h3><?php print $intConteo; ?></h3>
                                            <input id="hdnTaller_<?php print $intID; ?>" name="hdnTaller_<?php print $intID; ?>" type="hidden" value="N">
                                        </td>
                                        <td data-title="Nombre" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowTallerNombre_<?php print $intID; ?>">
                                                <?php print $strNombre; ?>
                                            </div>
                                            <div id="divEditTallerNombre_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtNombre_<?php print $intID; ?>" name="txtNombre_<?php print $intID; ?>" value="<?php print $strNombre; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Direccion" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowTallerDireccion_<?php print $intID; ?>">
                                                <?php print $strDireccion; ?>
                                            </div>
                                            <div id="divEditTallerDireccion_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtDireccion_<?php print $intID; ?>" name="txtDireccion_<?php print $intID; ?>" value="<?php print $strDireccion; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Nit" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowTallerNit_<?php print $intID; ?>">
                                                <?php print $strNit; ?>
                                            </div>
                                            <div id="divEditTallerNit_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtNit_<?php print $intID; ?>" name="txtNit_<?php print $intID; ?>" value="<?php print $strNit; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Acciones" style="text-align:left;">
                                            <button class="btn btn-info btn-block" onclick="editTaller('<?php print $intID; ?>')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-block" onclick="deleteTaller('<?php print $intID; ?>')" title="Eliminar">
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
                                url: "talleres.php",
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

                    function editTaller(id) {
                        $("#divEditTallerNombre_" + id).show();
                        $("#divShowTallerNombre_" + id).hide();

                        $("#divEditTallerDireccion_" + id).show();
                        $("#divShowTallerDireccion_" + id).hide();

                        $("#divEditTallerNit_" + id).show();
                        $("#divShowTallerNit_" + id).hide();

                        $("#hdnTaller_" + id).val("E");
                    }

                    function deleteTaller(id) {
                        $("#trTaller_" + id).css('background-color', '#f4d0de');
                        $("#hdnTaller_" + id).val("D");
                    }

                    function fntGetCountTaller() {
                        var intCount = 0;
                        $("input[name*='txtNombre_']").each(function() {
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
                            max = fntGetCountTaller();
                        }
                        return max + 1;
                    }

                    var intFilasTaller = 0;

                    function agregarTaller() {
                        intFilasTaller = fntGetCountTaller();
                        intFilasTaller++;

                        max = fntGetCountMax();

                        var $tabla = $("#tblTalleres");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasTaller + "<b><input class='form-control' type='hidden' id='hdnTaller_" + max + "' name='hdnTaller_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombre' style='text-align:center;'><input class='form-control' type='text' id='txtNombre_" + max + "' name='txtNombre_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Direccion' style='text-align:center;'><input class='form-control' type='text' id='txtDireccion_" + max + "' name='txtDireccion_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nit' style='text-align:center;'><input class='form-control' type='text' id='txtNit_" + max + "' name='txtNit_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);
                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    function checkForm() {
                        var response = confirm("Desea confirmar los cambios?");
                        if (response == true) {
                            var boolError = false;
                            $("input[name*='txtNombre_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtDireccion_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtNit_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            if (boolError == false) {
                                var objSerialized = $("#tblTalleres").find("select, input").serialize();
                                $.ajax({
                                    url: "talleres.php",
                                    data: objSerialized,
                                    type: "POST",
                                    beforeSend: function() {
                                        $("#divShowLoadingGeneralBig").show();
                                    },
                                    success: function(data) {
                                        $("#divShowLoadingGeneralBig").hide();
                                        location.href = "talleres.php";
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