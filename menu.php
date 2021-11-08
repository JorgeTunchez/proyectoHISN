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

$objController = new menu_controller();
$objController->runAjax();
$objController->drawContentController();

class menu_controller{
    private $objModel;
    private $objView;

    public function __construct()
    {
        $this->objModel = new menu_model();
        $this->objView = new menu_view();
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
}

class menu_model{

    public function getCP_Estados(){
        $conn = getConexion();
        $arrCPEstados = array();
        $strQuery = "SELECT CASE 
                            WHEN estado = 'E' THEN 'Excelente'
                            WHEN estado = 'M' THEN 'Malo'
                            WHEN estado = 'R' THEN 'Regular'
                            ELSE 'Bueno'
                        END AS estado,
                        ROUND(SUM(precio_compra), 2) costo, 
                        ROUND(AVG(precio_compra), 2) promedio
                    FROM herramientas
                    GROUP BY estado
                    ORDER BY costo DESC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrCPEstados[$row["estado"]]["COSTO"] = $row["costo"];
                $arrCPEstados[$row["estado"]]["PROMEDIO"] = $row["promedio"];
            }
        }
        return $arrCPEstados;
    }

    public function getCP_Tipo(){
        $conn = getConexion();
        $arrCPTipo = array();
        $strQuery = "SELECT 'Obsoleto' tipo,
		                    ROUND(SUM(precio_compra), 2) costo, 
                            ROUND(AVG(precio_compra), 2) promedio
                       FROM herramientas
                      WHERE obseleto = 1
                      UNION
                     SELECT 'Reciclado' tipo,
                            ROUND(SUM(precio_compra), 2) costo, 
                            ROUND(AVG(precio_compra), 2) promedio
                       FROM herramientas
                      WHERE reciclado = 1
                      ORDER BY costo DESC";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrCPTipo[$row["tipo"]]["COSTO"] = $row["costo"];
                $arrCPTipo[$row["tipo"]]["PROMEDIO"] = $row["promedio"];
            }
        }
        return $arrCPTipo;
    }

}

class menu_view{
    private $objModel;

    public function __construct(){
        $this->objModel = new menu_model();
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
                                draMenu("Inicio");
                                ?>
                            </ul>            
                        </div>
                        </nav>

                        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <h1 class="h2">Estadisticas</h1>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table">
                            <div class="card">
                                <div class="card-header text-white bg-primary">
                                    <h5 class="card-title">Costos y Promedio por Estado</h5>
                                    <h6 class="card-subtitle mb-2">Costos y promedios catalogados por estado de las herramientas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                        <?php
                                        $arrCPEstados = $this->objModel->getCP_Estados();
                                        reset($arrCPEstados);
                                        while( $rTMP = each($arrCPEstados) ){
                                            $strEstado = $rTMP["key"];
                                            $fltCosto = $rTMP["value"]["COSTO"];
                                            $fltPromedio = $rTMP["value"]["PROMEDIO"];
                                            ?>
                                            <ul>
                                                <li><strong><?php print $strEstado; ?></strong></li>
                                                <ul>
                                                    <li><?php print "Costo Total <h6>Q. ".$fltCosto."</h6>"; ?></li>
                                                    <li><?php print "Promedio <h6>Q. ".$fltPromedio."</h6>"; ?></li>
                                                </ul>
                                            </ul>
                                        <?php
                                        }
                                        ?>
                                        </div>
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                            Aqui vamos a dibujar las graficas
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header text-white bg-primary">
                                    <h5 class="card-title">Costos y Promedio por Tipo</h5>
                                    <h6 class="card-subtitle mb-2">Costos y promedios catalogados por estado de las herramientas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                        <?php
                                        $arrCPTipo = $this->objModel->getCP_Tipo();
                                        reset($arrCPTipo);
                                        while( $rTMP = each($arrCPTipo) ){
                                            $strTipo = $rTMP["key"];
                                            $fltCosto = $rTMP["value"]["COSTO"];
                                            $fltPromedio = $rTMP["value"]["PROMEDIO"];
                                            ?>
                                            <ul>
                                                <li><strong><?php print $strTipo; ?></strong></li>
                                                <ul>
                                                    <li><?php print "Costo Total <h6>Q. ".$fltCosto."</h6>"; ?></li>
                                                    <li><?php print "Promedio <h6>Q. ".$fltPromedio."</h6>"; ?></li>
                                                </ul>
                                            </ul>
                                        <?php
                                        }
                                        ?>
                                        </div>
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                            Aqui vamos a dibujar las graficas
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br><br><br><br>
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
                                url: "menu.php",
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
                </script>
            </body>
        </html>
        <?php
    }
}
?>