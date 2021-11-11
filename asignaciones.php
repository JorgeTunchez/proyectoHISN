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
        $arrRolUser["ROL"] = $strRolUserSession;

        if ($strRolUserSession == "admin") {
            $arrRolUser["ADMIN"] = true;
        } elseif ($strRolUserSession == "mecanico") {
            $arrRolUser["MECANICO"] = true;
        }
    }
} else {
    header("Location: index.php");
}
$objController = new asignaciones_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class asignaciones_controller{
    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new asignaciones_model($arrRolUser);
        $this->objView = new asignaciones_view($arrRolUser);
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
            if ($arrExplode[0] == "hdnItem") {
                $intMecanico = $arrExplode[1];
                $intConteo = $arrExplode[2];
                $strAccion = isset($_POST["hdnItem_{$intMecanico}_{$intConteo}"]) ? trim($_POST["hdnItem_{$intMecanico}_{$intConteo}"]) : '';
                if ($strAccion == "A") {
                    //insertar
                    $intHerramienta = isset($_POST["sltHerramienta_{$intMecanico}_{$intConteo}"]) ? intval($_POST["sltHerramienta_{$intMecanico}_{$intConteo}"]) : 0;
                    $this->objModel->insertAsignacion($intMecanico, $intHerramienta, $intUser);
                }elseif ($strAccion == "D") {
                    //desasignar
                    $intAsignacion = isset($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) ? intval($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) : 0;
                    $this->objModel->desasignarHerramienta($intAsignacion);
                }elseif ($strAccion == "O") {
                    //obsoleto
                    $intAsignacion = isset($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) ? intval($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) : 0;
                    $this->objModel->obseletoHerramienta($intAsignacion);
                }elseif ($strAccion == "R") {
                    //reciclar
                    $intAsignacion = isset($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) ? intval($_POST["hdnItemAsig_{$intMecanico}_{$intConteo}"]) : 0;
                    $this->objModel->reciclarHerramienta($intAsignacion);
                }
            }
        }
    }

}

class asignaciones_model{

    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->arrRolUser = $arrRolUser;
    }

    public function getHerramientasUser($strNameUser){
        $conn = getConexion();
        $arrListado = array();
        if( $strNameUser !='' ){
            $strQuery = "SELECT asignaciones.id asignacion_id, 
                                herramientas.id herramienta_id,
                                herramientas.codigo herramienta_codigo,
                                herramientas.nombre herramienta_nombre 
                           FROM usuarios
                                INNER JOIN asignaciones ON asignaciones.usuario = usuarios.id
                                INNER JOIN herramientas ON asignaciones.herramienta = herramientas.id 
                          WHERE usuarios.nickname = '{$strNameUser}'
                            AND herramientas.obseleto = 0
                            AND herramientas.reciclado = 0
                       ORDER BY herramienta_codigo ASC";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    
                    $arrListado[$row["asignacion_id"]]["CODIGO"]= $row["herramienta_codigo"];
                    $arrListado[$row["asignacion_id"]]["NOMBRE"]= $row["herramienta_nombre"];
                    
                }
            }
            return $arrListado;
        }
        
    }

    public function getListado(){
        $conn = getConexion();
        $arrListado = array();
        $strNameUser = trim($this->arrRolUser["NAME"]);
        if( $this->arrRolUser["ROL"] == "admin" ){
            $strQuery = "SELECT usuarios.id usuario_id, 
                                usuarios.nickname usuario_nickname
                           FROM usuarios
                          WHERE usuarios.tipo = 2
                       ORDER BY usuarios.nickname ASC";
        }

        if( $this->arrRolUser["ROL"] == "mecanico" ){
            $strQuery = "SELECT usuarios.id usuario_id, 
                                usuarios.nickname usuario_nickname
                           FROM usuarios
                          WHERE usuarios.tipo = 2
                            AND usuarios.nickname = '{$strNameUser}'
                       ORDER BY usuarios.nickname ASC";
        }
        
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrListado[$row["usuario_nickname"]]["ID_MECANICO"] = $row["usuario_id"];
                $arrListado[$row["usuario_nickname"]]["HERRAMIENTAS"] = $this->getHerramientasUser($row["usuario_nickname"]);
            }
        }
        return $arrListado;
    }

    public function getHerramientas(){
        $conn = getConexion();
        $arrHerramientas = array();
        $strQuery = "SELECT id, 
                            codigo,
                            nombre
                       FROM herramientas
                      WHERE id NOT IN (SELECT DISTINCT herramienta FROM asignaciones)
                        AND obseleto = 0
                        AND reciclado = 0
                      ORDER BY nombre ASC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrHerramientas[$row["id"]]["CODIGO"] = $row["codigo"];
                $arrHerramientas[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }
        return $arrHerramientas;
    }

    public function insertAsignacion($intMecanico, $intHerramienta, $intUser){
        if ( $intMecanico > 0 && $intHerramienta > 0 &&  $intUser > 0) {
            $conn = getConexion();
            $strQuery = "INSERT INTO asignaciones (herramienta, usuario, add_fecha, add_user) 
                                       VALUES ( {$intHerramienta}, {$intMecanico}, now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
    }

    public function desasignarHerramienta($intAsignacion){
        if ($intAsignacion > 0) {
            $conn = getConexion();
            $strQuery = "DELETE FROM asignaciones WHERE id = {$intAsignacion}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function getIdHerramientaAsig($intAsignacion){
        if( $intAsignacion > 0 ){
            $intIDHerramienta = 0;
            $conn = getConexion();
            $strQuery = "SELECT herramienta FROM asignaciones WHERE id = {$intAsignacion}";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $intIDHerramienta = $row["herramienta"];
                }
            }
            return $intIDHerramienta;
        }
    }

    public function obseletoHerramienta($intAsignacion){
        if ($intAsignacion > 0) {
            $conn = getConexion();
            $intIDHerramienta = $this->getIdHerramientaAsig($intAsignacion);
            $strQuery = "UPDATE herramientas 
                            SET obseleto = 1
                          WHERE id = {$intIDHerramienta}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function reciclarHerramienta($intAsignacion){
        if ($intAsignacion > 0) {
            $conn = getConexion();
            $intIDHerramienta = $this->getIdHerramientaAsig($intAsignacion);
            $strQuery = "UPDATE herramientas 
                            SET obseleto = 1,
                                reciclado = 1
                          WHERE id = {$intIDHerramienta}";
            mysqli_query($conn, $strQuery);
        }
    }



}

class asignaciones_view{
    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser){
        $this->objModel = new asignaciones_model($arrRolUser);
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
                        <div class="nav-item text-nowrap">
                            <h6 style="color:#fff;"><?php print "Usuario: ".$this->arrRolUser["NAME"]; ?></h6>
                        </div>
                    </div>
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
                                draMenu("Asignaciones", $this->arrRolUser["ROL"]);
                                ?>
                            </ul>            
                        </div>
                        </nav>
                        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <div class="row">
                                <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8">
                                    <h1 class="h2">Asignaciones</h1>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                    <button class="btn btn-success" onclick="checkForm()" title="Guardar"><i class="far fa-save"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table-responsive" id="divContent">
                            <?php 
                            $arrListado = $this->objModel->getListado();
                            if( count($arrListado)>0 ){
                                ?>
                                <div class="accordion mt-4 mb-4" id="accordionExample">
                                    <?php
                                    reset($arrListado);
                                    while( $rTMP = each($arrListado) ){
                                        
                                        $strMecanicoNombre = $rTMP["key"];
                                        $intIDMecanico = $rTMP["value"]["ID_MECANICO"];
                                        ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="<?php print "accordion_".$intIDMecanico; ?>">
                                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?php print "collapse_".$intIDMecanico; ?>" aria-expanded="true" aria-controls="<?php print "collapse_".$intIDMecanico; ?>">
                                                        <?php print $strMecanicoNombre; ?>
                                                    </button>
                                                </h2>
                                                <div id="<?php print "collapse_".$intIDMecanico; ?>" class="accordion-collapse collapse show" aria-labelledby="<?php print "accordion_".$intIDMecanico; ?>" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <ul class="list-group" id="list_<?php print $intIDMecanico; ?>">
                                                        <?php
                                                        $intConteo = 0;
                                                        while( $rTMP2 = each($rTMP["value"]["HERRAMIENTAS"]) ){
                                                            $intConteo++;
                                                            $intAsignacion =  $rTMP2["key"];
                                                            $strCodigo = $rTMP2["value"]["CODIGO"];
                                                            $strNombre = $rTMP2["value"]["NOMBRE"];
                                                            ?>
                                                            <li class="list-group-item" id="item_<?php print $intIDMecanico; ?>_<?php print $intConteo; ?>">
                                                                <input type="hidden" id="hdnItem_<?php print $intIDMecanico; ?>_<?php print $intConteo; ?>" name="hdnItem_<?php print $intIDMecanico; ?>_<?php print $intConteo; ?>">
                                                                <input type="hidden" id="hdnItemAsig_<?php print $intIDMecanico; ?>_<?php print $intConteo; ?>" name="hdnItemAsig_<?php print $intIDMecanico; ?>_<?php print $intConteo; ?>" value="<?php print $intAsignacion; ?>">
                                                                <?php print $strCodigo." - ".$strNombre; ?>
                                                                <button class="btn btn-danger btn-block" title="Desasignar" onclick="desasignar('<?php print $intIDMecanico; ?>', '<?php print $intConteo; ?>')">
                                                                    <i class="fas fa-backspace"></i>
                                                                </button>
                                                                <button class="btn btn-warning btn-block" title="Obsoleto" onclick="obsoleto('<?php print $intIDMecanico; ?>', '<?php print $intConteo; ?>')">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                                <button class="btn btn-success btn-block" title="Reciclar" onclick="reciclar('<?php print $intIDMecanico; ?>', '<?php print $intConteo; ?>')">
                                                                    <i class="fas fa-recycle"></i>
                                                                </button>
                                                            </li>
                                                            <?php
                                                        }
                                                        ?>
                                                        </ul>
                                                        <?php
                                                        if( $this->arrRolUser["ROL"] == "admin" ){
                                                            ?>
                                                            <button class="btn btn-primary mt-4" onclick="asignar('<?php print $intIDMecanico; ?>')">
                                                                <i class="fas fa-check-circle"></i>
                                                                Asignar Herramienta
                                                            </button>
                                                            <?php
                                                        }
                                                        ?>
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <br><br><br><br>
                                <?php
                            }else{
                                ?>
                                No hay asignaciones para mostrar.
                                <?php
                            }
                            ?>
                        </div>
                        <!-- Fin Contenido -->
                        </main>
                    </div>
                </div>
                <script src="js/bootstrap.min.js"></script>
                <script src="js/jquery.min.js"></script>
                <script>

                    function destroSession() {
                        if (confirm("¿Desea salir de la aplicación?")) {
                            $.ajax({
                                url: "asignaciones.php",
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

                    function desasignar(idmecanico, conteo) {
                        $("#item_" + idmecanico +"_"+conteo).css('background-color', '#F4C5C9');
                        $("#hdnItem_" + + idmecanico +"_"+conteo).val("D");
                    }

                    function obsoleto(idmecanico, conteo) {
                        $("#item_" + idmecanico +"_"+conteo).css('background-color', '#FDEAB2');
                        $("#hdnItem_" + idmecanico +"_"+conteo).val("O");
                    }

                    function reciclar(idmecanico, conteo) {
                        $("#item_" + idmecanico +"_"+conteo).css('background-color', '#AADBC4');
                        $("#hdnItem_" + idmecanico +"_"+conteo).val("R");
                    }

                    function fntGetMaxItemList(id) {
                        var intCount = 0;
                        $("li[id*='item_"+id+"']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function asignar(id) {
                        max = fntGetMaxItemList(id);
                        max = max + 1;
                        var $ul = $("#list_"+id);
                        var $li = $('<li class="list-group-item" id="item_'+id+'_'+max+'">'+
                                    '<input type="hidden" id="hdnItem_'+id+'_'+max+'" name="hdnItem_'+id+'_'+max+'" value="A">'+
                                    '<select class="form-control" id="sltHerramienta_'+id+'_'+max+'" name="sltHerramienta_'+id+'_'+max+'" style="text-align: center;"><?php $arrHerramientas = $this->objModel->getHerramientas(); reset($arrHerramientas); while ($rTMP = each($arrHerramientas)) { ?><option value="<?php print $rTMP["key"]; ?>"><?php print $rTMP["value"]["CODIGO"]." - ".$rTMP["value"]["NOMBRE"]; ?></option><?php } ?></select>'+
                                    '</li>')
                        $ul.append($li);
                    }

                    function checkForm() {
                        var response = confirm("Desea confirmar los cambios?");
                        if (response == true) {
                            var objSerialized = $("#divContent").find("select, input").serialize();
                            $.ajax({
                                url: "asignaciones.php",
                                data: objSerialized,
                                type: "POST",
                                beforeSend: function() {
                                    $("#divShowLoadingGeneralBig").show();
                                },
                                success: function(data) {
                                    $("#divShowLoadingGeneralBig").hide();
                                    location.href = "asignaciones.php";
                                }
                            });
                        }
                    }

                </script>
            </body>
        </html>
        <?php
    }
}
?>