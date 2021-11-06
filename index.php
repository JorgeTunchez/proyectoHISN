<?php
require_once("core/core.php");
$objController = new index_controller();
$objController->runAjax();
$objController->drawContentController();

class index_controller{
    private $objModel;
    private $objView;

    public function __construct()
    {
        $this->objModel = new index_model();
        $this->objView = new index_view();
    }

    public function drawContentController()
    {
        $this->objView->drawContent();
    }

    public function runAjax()
    {
        $this->ajaxAuthUser();
    }

    public function ajaxAuthUser()
    {
        if (isset($_POST['txtUsuario'])) {
            $strUser = isset($_POST['txtUsuario']) ? trim($_POST['txtUsuario']) : "";
            $strPassword = isset($_POST['txtPassword']) ? trim($_POST['txtPassword']) : "";
            $arrReturn = array();
            $boolRedirect = $this->objModel->redirect_dashboard($strUser, $strPassword);
            if ($boolRedirect) {
                $arrReturn["boolAuthRedirect"] = "Y";
            } else {
                $arrReturn["boolAuthRedirect"] = "N";
            }
            print json_encode($arrReturn);
            exit();
        }
    }

}

class index_model{

    public function redirect_dashboard($username, $password)
    {
        $boolRedirect = auth_user($username, $password);
        return $boolRedirect;
    }
    
}

class index_view{

    private $objModel;

    public function __construct(){
        $this->objModel = new index_model();
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
                    html,
                    body {
                    height: 100%;
                    }

                    body {
                    display: flex;
                    align-items: center;
                    padding-top: 40px;
                    padding-bottom: 40px;
                    background-color: #f5f5f5;
                    }

                    .form-signin {
                    width: 100%;
                    max-width: 330px;
                    padding: 15px;
                    margin: auto;
                    }

                    .form-signin .checkbox {
                    font-weight: 400;
                    }

                    .form-signin .form-floating:focus-within {
                    z-index: 2;
                    }

                    .form-signin input[type="email"] {
                    margin-bottom: -1px;
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                    }

                    .form-signin input[type="password"] {
                    margin-bottom: 10px;
                    border-top-left-radius: 0;
                    border-top-right-radius: 0;
                    }
                </style>
            </head>
            <body class="container text-center">
                <main class="form-signin">
                    <form method="POST" action="javascript:void(0);" id="frmLogin">
                        <img class="mb-4" src="images/tools.png" alt="" width="150" height="150">
                        <h1 class="h3 mb-3 fw-normal">Inventario Herramientas</h1>
                        <div class="form-floating mt-4">
                            <input type="text" class="form-control" id="txtUsuario" name="txtUsuario" placeholder="Usuario">
                            <label for="floatingInput">Usuario</label>
                        </div>
                        <div class="form-floating mt-4">
                            <input type="password" class="form-control" id="txtPassword" name="txtPassword" placeholder="Contraseña">
                            <label for="floatingPassword">Contraseña</label>
                        </div>
                        <div class="form-floating mt-4">
                            <button class="w-100 btn btn-lg btn-primary" type="button" onclick="checkForm()">Iniciar Session</button>
                        </div>
                    </form>
                </main>
                <script src="js/jquery.min.js"></script>
                <script>
                    $("#txtPassword").keypress(function(e) {
                        if (e.which == 13) {
                            checkForm();
                        }
                    });

                    function checkForm() {
                        var boolError = false;
                        if ($("#txtUsuario").val() == '') {
                            $("#txtUsuario").css('background-color', '#f4d0de');
                            boolError = true;
                        } else {
                            $("#txtUsuario").css('background-color', '');
                        }

                        if ($("#txtPassword").val() == '') {
                            $("#txtPassword").css('background-color', '#f4d0de');
                            boolError = true;
                        } else {
                            $("#txtPassword").css('background-color', '');
                        }

                        if (boolError == false) {
                            var objSerialized = $("#frmLogin").find("select, input").serialize();
                            $.ajax({
                                url: "index.php",
                                data: objSerialized,
                                type: "post",
                                dataType: "json",
                                beforeSend: function() {
                                    $("#btnInicioSession").prop('disabled', true);
                                    $("#divShowLoadingGeneralBig").css("z-index", 1050);
                                    $("#divShowLoadingGeneralBig").show();
                                },
                                success: function(data) {
                                    $("#btnInicioSession").prop('disabled', false);
                                    if (data.boolAuthRedirect == "Y") {
                                        $("#divShowLoadingGeneralBig").hide();
                                        location.href = "menu.php";
                                    } else {
                                        alert("Datos incorrectos y/o usuario inactivo");
                                        $("#divShowLoadingGeneralBig").hide();
                                        $("#loginUsername").val('');
                                        $("#loginPassword").val('');
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