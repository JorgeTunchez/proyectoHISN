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
$objController = new menu_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class menu_controller{
    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new menu_model();
        $this->objView = new menu_view($arrRolUser);
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

            if ($arrExplode[0] == "hdnHerramienta") {
                $intHerramienta = $arrExplode[1];
                $strAccion = isset($_POST["hdnHerramienta_{$intHerramienta}"]) ? trim($_POST["hdnHerramienta_{$intHerramienta}"]) : '';
                $strCodigo = isset($_POST["txtCodigo_{$intHerramienta}"]) ? trim($_POST["txtCodigo_{$intHerramienta}"]) : '';
                $strNombre = isset($_POST["txtNombre_{$intHerramienta}"]) ? trim($_POST["txtNombre_{$intHerramienta}"]) : '';
                $strMedida = isset($_POST["txtMedida_{$intHerramienta}"]) ? trim($_POST["txtMedida_{$intHerramienta}"]) : '';
                $fltPrecioCompra = isset($_POST["txtPrecioCompra_{$intHerramienta}"]) ? floatval($_POST["txtPrecioCompra_{$intHerramienta}"]) : '';
                $strEstado = isset($_POST["selectEstado_{$intHerramienta}"]) ? $_POST["selectEstado_{$intHerramienta}"] : "";
                $intObsoleto = isset($_POST["selectObsoleto_{$intHerramienta}"]) ? $_POST["selectObsoleto_{$intHerramienta}"] : 0;
                $intReciclado = isset($_POST["selectReciclado_{$intHerramienta}"]) ? $_POST["selectReciclado_{$intHerramienta}"] : 0;
                
                if ($strAccion == "A") {
                    $this->objModel->insertHerramienta($strCodigo, $strNombre, $strMedida, $fltPrecioCompra, $strEstado, $intObsoleto, $intReciclado, $intUser);
                } elseif ($strAccion == "D") {
                    $this->objModel->deleteHerramienta($intHerramienta);
                } elseif ($strAccion == "E") {
                    $this->objModel->updateHerramienta($intHerramienta, $strCodigo, $strNombre, $strMedida, $fltPrecioCompra, $strEstado, $intObsoleto, $intReciclado, $intUser);
                }
            }
        }
    }

}

class menu_model{
    public function getHerramientas(){
        $conn = getConexion();
        $arrHerramientas = array();
        $strQuery = "SELECT id, 
                            codigo,
                            nombre, 
                            medida, 
                            precio_compra, 
                            estado, 
                            obseleto,
                            reciclado 
                       FROM herramientas
                      ORDER BY codigo ASC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrHerramientas[$row["id"]]["CODIGO"] = $row["codigo"];
                $arrHerramientas[$row["id"]]["NOMBRE"] = $row["nombre"];
                $arrHerramientas[$row["id"]]["MEDIDA"] = $row["medida"];
                $arrHerramientas[$row["id"]]["PRECIO_COMPRA"] = $row["precio_compra"];
                $arrHerramientas[$row["id"]]["ESTADO"] = $row["estado"];
                $arrHerramientas[$row["id"]]["OBSOLETO"] = $row["obsoleto"];
                $arrHerramientas[$row["id"]]["RECICLADO"] = $row["reciclado"];
            }
        }
        return $arrHerramientas;
    }

    public function insertHerramienta($strCodigo, $strNombre, $strMedida, $fltPrecioCompra, $strEstado, $intObsoleto, $intReciclado, $intUser){
        if ($strCodigo!='' && $strNombre!='' && $strMedida!='' && $fltPrecioCompra>0 && $strEstado!='' && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "INSERT INTO herramientas (codigo, nombre, medida, precio_compra, estado, obseleto, reciclado, add_fecha, add_user) 
                                       VALUES ('{$strCodigo}', '{$strNombre}', '{$strMedida}', {$fltPrecioCompra}, '{$strEstado}', {$intObsoleto}, {$intReciclado}, now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
    }

    public function deleteHerramienta($intHerramienta){
        if ($intHerramienta > 0) {
            $conn = getConexion();
            $strQuery = "DELETE FROM herramientas WHERE id = {$intHerramienta}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateHerramienta($intHerramienta, $strCodigo, $strNombre, $strMedida, $fltPrecioCompra, $strEstado, $intObsoleto, $intReciclado, $intUser){
        if ($intHerramienta > 0 && $strCodigo!='' && $strNombre!='' && $strMedida!='' && $fltPrecioCompra>0 && $strEstado!='' && $intUser>0) {
            $conn = getConexion();
            $strQuery = "UPDATE herramientas 
                            SET codigo = '{$strCodigo}',
                                nombre = '{$strNombre}',
                                medida = '{$strMedida}',
                                precio_compra = {$fltPrecioCompra}, 
                                estado = '{$strEstado}',
                                obseleto = {$intObsoleto},
                                reciclado = {$intReciclado},
                                mod_fecha = now(),
                                mod_user = {$intUser} 
                          WHERE id = {$intHerramienta}";
            mysqli_query($conn, $strQuery);
        }
    }
}

class menu_view{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new menu_model();
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
                                draMenu("Herramientas");
                                ?>
                            </ul>            
                        </div>
                        </nav>

                        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <div class="row">
                                <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                                    <h1 class="h2">Herramientas</h1>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-info" onclick="agregarHerramienta()" title="Agregar"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-success" onclick="checkForm()" title="Guardar"><i class="far fa-save"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tblHerramientas">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Medida</th>
                                    <th>Precio Compra</th>
                                    <th>Estado</th>
                                    <th>Obsoleto</th>
                                    <th>Reciclado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $arrHerramientas = $this->objModel->getHerramientas();
                                $intConteo = 0;
                                reset($arrHerramientas);
                                while ($rTMP = each($arrHerramientas)) {
                                    $intConteo++;
                                    $intID = $rTMP["key"];
                                    $strCodigo = trim($rTMP["value"]["CODIGO"]);
                                    $strNombre = trim($rTMP["value"]["NOMBRE"]);
                                    $strMedida = trim($rTMP["value"]["MEDIDA"]);
                                    $fltPrecioCompra = $rTMP["value"]["PRECIO_COMPRA"];
                                    $strEstado = trim($rTMP["value"]["ESTADO"]);
                                    if( $strEstado == "M" ){
                                        $strEstadoDescripcion = "Malo";
                                    }elseif ( $strEstado == "R" ) {
                                        $strEstadoDescripcion = "Regular";
                                    }elseif ( $strEstado == "B" ) {
                                        $strEstadoDescripcion = "Bueno";
                                    }elseif ( $strEstado == "E" ) {
                                        $strEstadoDescripcion = "Excelente";
                                    }
                                    
                                    $intObsoleto = intval($rTMP["value"]["OBSOLETO"]);
                                    if( $intObsoleto == 0 ){
                                        $strObsoletoDescripcion = "NO";
                                    }elseif ( $intObsoleto == 1 ) {
                                        $strObsoletoDescripcion = "SI";
                                    }

                                    $intReciclado = intval($rTMP["value"]["RECICLADO"]);
                                    if( $intReciclado == 0 ){
                                        $strRecicladoDescripcion = "NO";
                                    }elseif ( $intReciclado == 1 ) {
                                        $strRecicladoDescripcion = "SI";
                                    }
                                    
                                    ?>
                                    <tr id="trHerramienta_<?php print $intID; ?>">
                                        <td data-title="No." style="text-align:left; vertical-align:middle;">
                                            <h3><?php print $intConteo; ?></h3>
                                            <input id="hdnHerramienta_<?php print $intID; ?>" name="hdnHerramienta_<?php print $intID; ?>" type="hidden" value="N">
                                        </td>
                                        <td data-title="Codigo" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaCodigo_<?php print $intID; ?>">
                                                <?php print $strCodigo; ?>
                                            </div>
                                            <div id="divEditHerramientaCodigo_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtCodigo_<?php print $intID; ?>" name="txtCodigo_<?php print $intID; ?>" value="<?php print $strCodigo; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Nombre" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaNombre_<?php print $intID; ?>">
                                                <?php print $strNombre; ?>
                                            </div>
                                            <div id="divEditHerramientaNombre_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtNombre_<?php print $intID; ?>" name="txtNombre_<?php print $intID; ?>" value="<?php print $strNombre; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Medida" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaMedida_<?php print $intID; ?>">
                                                <?php print $strMedida; ?>
                                            </div>
                                            <div id="divEditHerramientaMedida_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtMedida_<?php print $intID; ?>" name="txtMedida_<?php print $intID; ?>" value="<?php print $strMedida; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Precio Compra" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaPrecioCompra_<?php print $intID; ?>">
                                                <?php print $fltPrecioCompra; ?>
                                            </div>
                                            <div id="divEditHerramientaPrecioCompra_<?php print $intID; ?>" style="display:none;">
                                                <input class="form-control" type="text" id="txtPrecioCompra_<?php print $intID; ?>" name="txtPrecioCompra_<?php print $intID; ?>" value="<?php print $fltPrecioCompra; ?>">
                                            </div>
                                        </td>
                                        <td data-title="Estado" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaEstado_<?php print $intID; ?>">
                                                <?php print $strEstadoDescripcion; ?>
                                            </div>
                                            <div id="divEditHerramientaEstado_<?php print $intID; ?>" style="display:none;">
                                                <select id="selectEstado_<?php print $intID; ?>" name="selectEstado_<?php print $intID; ?>" style="text-align: center;" class="form-control">
                                                    <option value="M" <?php print ($strEstado == "M") ? "selected" : ""; ?>>Malo</option>
                                                    <option value="R" <?php print ($strEstado == "R") ? "selected" : ""; ?>>Regular</option>
                                                    <option value="B" <?php print ($strEstado == "B") ? "selected" : ""; ?>>Bueno</option>
                                                    <option value="E" <?php print ($strEstado == "E") ? "selected" : ""; ?>>Excelente</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td data-title="Obsoleto" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaObsoleto_<?php print $intID; ?>">
                                                <?php print $strObsoletoDescripcion; ?>
                                            </div>
                                            <div id="divEditHerramientaObsoleto_<?php print $intID; ?>" style="display:none;">
                                                <select id="selectObsoleto_<?php print $intID; ?>" name="selectObsoleto_<?php print $intID; ?>" style="text-align: center;" class="form-control">
                                                    <option value="0" <?php print ($intObsoleto == 0) ? "selected" : ""; ?>>No</option>
                                                    <option value="1" <?php print ($intObsoleto == 1) ? "selected" : ""; ?>>Si</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td data-title="Reciclado" style="text-align:left; vertical-align:middle;">
                                            <div id="divShowHerramientaReciclado_<?php print $intID; ?>">
                                                <?php print $strRecicladoDescripcion; ?>
                                            </div>
                                            <div id="divEditHerramientaReciclado_<?php print $intID; ?>" style="display:none;">
                                                <select id="selectReciclado_<?php print $intID; ?>" name="selectReciclado_<?php print $intID; ?>" style="text-align: center;" class="form-control">
                                                    <option value="0" <?php print ($intReciclado == 0) ? "selected" : ""; ?>>No</option>
                                                    <option value="1" <?php print ($intReciclado == 1) ? "selected" : ""; ?>>Si</option>
                                                </select>
                                            </div>
                                        </td>    
                                        <td data-title="Acciones" style="text-align:left;">
                                            <button class="btn btn-info btn-block" onclick="editHerramienta('<?php print $intID; ?>')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-block" onclick="deleteHerramienta('<?php print $intID; ?>')" title="Eliminar">
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
                                url: "herramientas.php",
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

                    function editHerramienta(id) {
                        $("#divEditHerramientaCodigo_" + id).show();
                        $("#divShowHerramientaCodigo_" + id).hide();

                        $("#divEditHerramientaNombre_" + id).show();
                        $("#divShowHerramientaNombre_" + id).hide();

                        $("#divEditHerramientaMedida_" + id).show();
                        $("#divShowHerramientaMedida_" + id).hide();

                        $("#divEditHerramientaPrecioCompra_" + id).show();
                        $("#divShowHerramientaPrecioCompra_" + id).hide();

                        $("#divEditHerramientaEstado_" + id).show();
                        $("#divShowHerramientaEstado_" + id).hide();

                        $("#divEditHerramientaObsoleto_" + id).show();
                        $("#divShowHerramientaObsoleto_" + id).hide();

                        $("#divEditHerramientaReciclado_" + id).show();
                        $("#divShowHerramientaReciclado_" + id).hide();

                        $("#hdnHerramienta_" + id).val("E");
                    }

                    function deleteHerramienta(id) {
                        $("#trHerramienta_" + id).css('background-color', '#f4d0de');
                        $("#hdnHerramienta_" + id).val("D");
                    }

                    function fntGetCountHerramienta() {
                        var intCount = 0;
                        $("input[name*='txtCodigo_']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function fntGetCountMax() {
                        var valores = [];
                        var intCount = 0;
                        $("input[name*='hdnHerramienta_']").each(function() {
                            var arrSplit = $(this).attr("id").split("_");
                            valores.push(arrSplit[1]);
                        });
                        var max = parseInt(Math.max.apply(null, valores));
                        if (isNaN(max)) {
                            max = fntGetCountHerramienta();
                        }
                        return max + 1;
                    }

                    var intFilasHerramienta = 0;

                    function agregarHerramienta() {
                        intFilasHerramienta = fntGetCountHerramienta();
                        intFilasHerramienta++;

                        max = fntGetCountMax();

                        var $tabla = $("#tblHerramientas");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasHerramienta + "<b><input class='form-control' type='hidden' id='hdnHerramienta_" + max + "' name='hdnHerramienta_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Codigo' style='text-align:center;'><input class='form-control' type='text' id='txtCodigo_" + max + "' name='txtCodigo_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombre' style='text-align:center;'><input class='form-control' type='text' id='txtNombre_" + max + "' name='txtNombre_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Medida' style='text-align:center;'><input class='form-control' type='text' id='txtMedida_" + max + "' name='txtMedida_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Precio Compra' style='text-align:center;'><input class='form-control' type='text' id='txtPrecioCompra_" + max + "' name='txtPrecioCompra_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Estado' style='text-align:center;'><select class='form-control' id='selectEstado_" + max + "' name='selectEstado_" + max + "' style='text-align: center;'><option value='M'>Malo</option><option value='R'>Regular</option><option value='B'>Bueno</option><option value='E'>Excelente</option></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Obsoleto' style='text-align:center;'><select class='form-control' id='selectObsoleto_" + max + "' name='selectObsoleto_" + max + "' style='text-align: center;'><option value='0'>No</option><option value='1'>Si</option></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Reciclado' style='text-align:center;'><select class='form-control' id='selectReciclado_" + max + "' name='selectReciclado_" + max + "' style='text-align: center;'><option value='0'>No</option><option value='1'>Si</option></select></td>");
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    function checkForm() {
                        var response = confirm("Desea confirmar los cambios?");
                        if (response == true) {
                            var boolError = false;
                            $("input[name*='txtCodigo_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtNombre_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtMedida_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            $("input[name*='txtPrecioCompra_']").each(function() {
                                if ($(this).val() == '' ) {
                                    $(this).css('background-color', '#f4d0de');
                                    boolError = true;
                                } else {
                                    $(this).css('background-color', '');
                                }
                            });

                            if (boolError == false) {
                                var objSerialized = $("#tblHerramientas").find("select, input").serialize();
                                $.ajax({
                                    url: "herramientas.php",
                                    data: objSerialized,
                                    type: "POST",
                                    beforeSend: function() {
                                        $("#divShowLoadingGeneralBig").show();
                                    },
                                    success: function(data) {
                                        $("#divShowLoadingGeneralBig").hide();
                                        location.href = "herramientas.php";
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