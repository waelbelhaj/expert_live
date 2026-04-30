<!DOCTYPE html>
<html lang="en">


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("clients.php");


$users['EI'] = array('98342821', '*', '*', '', '*'); //Compte super admin

if (isset($_POST["user"]) && isset($users[$_POST["user"]]) && $users[$_POST["user"]][0] == $_POST["pwd"]) {
    $_SESSION["user"] = $users[$_POST["user"]];
}
if (isset($_GET['logout'])) {
    $_SESSION["user"] = "";
    unset($_SESSION["user"]);
    session_destroy();
}
if (!isset($_SESSION["user"])) {
    ?>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                font-family: 'Outfit', sans-serif;
            }

            body {
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: url('assets/images/bg_abstract.png') center/cover no-repeat;
                background-color: #0b458b;
            }

            .login-wrapper {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                padding: 40px;
                width: 360px;
                color: #fff;
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            }

            .login-wrapper h2 {
                text-align: center;
                margin-bottom: 30px;
                font-size: 24px;
                font-weight: 600;
            }

            .login-wrapper h3 {
                margin-bottom: 20px;
                font-size: 20px;
                font-weight: 500;
                text-align: left;
            }

            .form-group {
                margin-bottom: 15px;
                text-align: left;
            }

            .form-group label {
                display: block;
                font-size: 12px;
                margin-bottom: 6px;
                font-weight: 400;
            }

            .form-group input {
                width: 100%;
                padding: 10px 15px;
                border: none;
                border-radius: 8px;
                outline: none;
                font-size: 14px;
                font-family: 'Outfit', sans-serif;
            }

            .form-group input[type="text"],
            .form-group input[type="password"] {
                background: #fff;
                color: #333;
            }

            .form-group.pwd-group {
                margin-bottom: 8px;
            }

            .forgot-pwd {
                text-align: left;
                font-size: 11px;
                margin-bottom: 25px;
            }

            .forgot-pwd a {
                color: #fff;
                text-decoration: none;
            }

            .btn-submit {
                width: 100%;
                background-color: #0e376a;
                color: #fff;
                padding: 12px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s;
                font-family: 'Outfit', sans-serif;
            }

            .btn-submit:hover {
                background-color: #09264c;
            }

            .btn-Logout {
                width: 100%;
                background-color: #4d0e6aff;
                color: #fff;
                padding: 12px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s;
                font-family: 'Outfit', sans-serif;
                margin-top: 10px;
            }

            .btn-Logout:hover {
                background-color: #09264c;
            }

            .continue-with {
                text-align: center;
                margin: 20px 0 15px 0;
                font-size: 11px;
                position: relative;
            }

            .social-btns {
                display: flex;
                justify-content: space-between;
                gap: 15px;
                margin-bottom: 25px;
            }

            .social-btn {
                background: #fff;
                flex: 1;
                padding: 8px 0;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
            }

            .social-btn:hover {
                background: #f0f0f0;
            }

            .social-btn img,
            .social-btn svg {
                height: 18px;
            }

            .register-text {
                text-align: center;
                font-size: 11px;
            }

            .register-text a {
                color: #fff;
                font-weight: 600;
                text-decoration: none;
            }
        </style>
    </head>

    <body>
        <div class="login-wrapper">
            <h2>EXPERT GESTION PRO V3</h2>
            <h3>Login</h3>
            <form action="./login2.php" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input name="user" type="text" placeholder="username">
                </div>
                <div class="form-group pwd-group">
                    <label>Password</label>
                    <input name="pwd" type="password" placeholder="Password">
                </div>
                <div class="forgot-pwd">
                    <a
                        href="mailto:contact@einfo.tn?subject=Mot%20de%20passe%20oublier&body=Bonjour%2C%0A%0AJ'ai%20oublié%20mon%20mot%20de%20passe%20pour%20l'application%20Expert%20Gestion.%0A%0AVeuillez%20me%20contacter%20pour%20réinitialiser%20mon%20mot%20de%20passe.">Forgot
                        Password?</a>
                </div>
                <button type="submit" class="btn-submit">Sign in</button>
            </form>
            <?php if (1 == 2) { ?>
                <div class="continue-with">or continue with</div>
                <div class="social-btns">
                    <!-- Google -->
                    <a href="#" class="social-btn">
                        <svg width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335"
                                d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                            <path fill="#4285F4"
                                d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                            <path fill="#FBBC05"
                                d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                            <path fill="#34A853"
                                d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                        </svg>
                    </a>
                    <!-- Github -->
                    <a href="#" class="social-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#333">
                            <path
                                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                        </svg>
                    </a>
                    <!-- Facebook -->
                    <a href="#" class="social-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                </div>

                <div class="register-text">
                    Don't have an account yet? <a href="#">Register for free</a>
                </div>
            <?php } ?>
        </div>
        <?php
        die();
} else {
    if (!isset($_GET['idClient']) && $_SESSION["user"][4] <> "*") {
        header('location: ./dashboard.php?idClient=' . $_SESSION["user"][4]);
    }
    if ($_SESSION["user"][1] == "*" && $_SESSION["user"][4] == "*") {
        header('location: ./superadmin.php');
        exit();
    }
    if ($_SESSION["user"][4] == "*") {
        ?>
            <div class="login-wrapper">
                <h2>EXPERT GESTION</h2>
                <h3>Login</h3>
                <form action="./dashboard.php" method="get">
                    <div class="form-group">
                        <label>User id (idClient)</label>
                        <input name="idClient" type="text" placeholder="User id">
                        <button type="submit" class="btn-submit">GO</button>
                        <br>
                        <a href="./login2.php?logout">
                            <button type="button" class="btn-Logout">Logout</button>
                        </a>
                    </div>
                </form>
            </div>
            <?php
    }
    if (!isset($_SESSION["user"][4])) {
        $_SESSION["user"] = "";
        unset($_SESSION["user"]);
        session_destroy();
        header('location: ./login2.php');
    }
}
?>
</body>

</html>