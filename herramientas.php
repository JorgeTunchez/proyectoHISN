<?php
require_once("core/core.php");
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

class menu_model{}

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

                    /*
                    * Sidebar
                    */

                    .sidebar {
                    position: fixed;
                    top: 0;
                    /* rtl:raw:
                    right: 0;
                    */
                    bottom: 0;
                    /* rtl:remove */
                    left: 0;
                    z-index: 100; /* Behind the navbar */
                    padding: 48px 0 0; /* Height of navbar */
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
                    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
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

                    /*
                    * Navbar
                    */

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
                            <h1 class="h2">Herramientas</h1>
                        </div>
                        <!-- Inicio Contenido -->
                        <div class="table-responsive">
                        Herramientas
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
                </script>
            </body>
        </html>
        <?php
    }
}
?>