<?php
session_start();
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $password = $_POST["password"];
    $conexion = new mysqli("localhost", "root", "", "123");

    if ($usuario === "admin" && $password === "admin") {
        $_SESSION['usuario'] = 'admin';
        header("Location: admin.php");
        exit();
    }

    $stmt_otde = $conexion->prepare("SELECT contrasena FROM usuarios_otde WHERE usuario = ?");
    $stmt_otde->bind_param("s", $usuario);
    $stmt_otde->execute();
    $res_otde = $stmt_otde->get_result();

    if ($res_otde->num_rows === 1) {
        $datos = $res_otde->fetch_assoc();
        $hash = $datos['contrasena'];
        if (empty($hash)) {
            $_SESSION['nuevo_usuario_otde'] = $usuario;
            header("Location: crear_contrasena.php");
            exit();
        } elseif (password_verify($password, $hash)) {
            $_SESSION['usuario'] = 'otde';
            $_SESSION['otde'] = $usuario;
            header("Location: panel_otde.php");
            exit();
        } else {
            $mensaje = "❌ Contraseña incorrecta.";
        }
    }

    $stmt = $conexion->prepare("SELECT contrasena FROM datos_escuelas WHERE cct = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $datos = $res->fetch_assoc();
        $hash = $datos['contrasena'];
        if (empty($hash)) {
            $_SESSION['nuevo_usuario_cct'] = $usuario;
            header("Location: crear_contrasena.php");
            exit();
        } elseif (password_verify($password, $hash)) {
            $_SESSION['usuario'] = 'escuela';
            $_SESSION['cct'] = $usuario;
            header("Location: perfil.php");
            exit();
        } else {
            $mensaje = "❌ Contraseña incorrecta.";
        }
    }

    if (!$mensaje) {
        $mensaje = "❌ Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Sesión | SEIEM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f6f0eb 0%, #f5f0ee 100%);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        color: #333;
    }

    /* Header */
    header {
        background-color: white;
        border-bottom: 2px solid #7E0808;
    }
    .top-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 10px;
    }
    .top-bar img {
        height: 80px;
        width: auto;
    }
    header hr {
        border: 0;
        border-top: 1px solid #7E0808;
        width: 60%;
        margin: 10px auto;
    }

    /* Login container */
    .login-container {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
    }
    .login-box {
        background: white;
        padding: 30px 40px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        width: 350px;
        text-align: center;
        animation: fadeIn 1s ease;
        position: relative;
    }
    @keyframes fadeIn {
        0% {opacity: 0; transform: translateY(-20px);}
        100% {opacity: 1; transform: translateY(0);}
    }
    .logo-login {
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }
    .logo-login img {
        height: 70px;
        width: auto;
    }
    .bienvenidos {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }
    .titulo-login {
        margin-bottom: 25px;
        font-size: 18px;
        color: #333;
        font-weight: 500;
        line-height: 1.4;
    }
    .titulo-login span {
        display: block;
        font-size: 22px;
        font-weight: 700;
        color: #7E0808;
        margin-top: 5px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .login-box input {
        width: 70%;
        padding: 10px 14px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid rgba(200, 200, 200, 1);
        font-size: 15px;
        transition: all 0.3s ease;
    }
    .login-box input:focus {
        border-color: #7E0808;
        box-shadow: 0 0 8px rgba(126,8,8,0.3);
        outline: none;
    }
    .login-box .input-group {
        position: relative;
    }
    .login-box .toggle-pass {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 16px;
        color: #7E0808;
        transition: transform 0.2s ease;
    }
    .login-box .toggle-pass:hover {
        transform: translateY(-50%) scale(1.2);
    }
    .login-box button {
        width: 100%;
        padding: 12px;
        background-color: #7E0808;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 15px;
        transition: all 0.3s ease;
    }
    .login-box button:hover {
        background-color: #5c0505;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .mensaje {
        margin-top: 15px;
        color: red;
        font-size: 14px;
        animation: shake 0.4s;
    }
    @keyframes shake {
        0% { transform: translateX(0); }
        25% { transform: translateX(-5px);}
        50% { transform: translateX(5px);}
        75% { transform: translateX(-5px);}
        100% { transform: translateX(0);}
    }

    /* Footer */
    footer {
        background-color: #7E0808;
        color: white;
        padding: 20px;
        text-align: center;
    }
    .footer-content {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        max-width: 900px;
        margin: auto;
    }
    .footer-section {
        flex: 1;
        padding: 10px;
        min-width: 200px;
    }
    .footer-section h4 {
        margin-bottom: 10px;
        font-size: 15px;
        font-weight: 600;
    }
    .footer-section p, .footer-section a {
        font-size: 14px;
        color: white;
        text-decoration: none;
        line-height: 1.5;
    }
    .footer-section a:hover {
        text-decoration: underline;
    }
    .footer-logo img {
        height: 100px;
        margin-top: 40px;
    }
    
</style>
</head>
<body>

<header>
    <div class="top-bar">
        <img src="encabezado.png" alt="Logo SEIEM">
    </div>
    <hr>
</header>

<div class="login-container">
    <div class="login-box">
       
        <h3 class="bienvenidos">Bienvenidos</h3>
        <h2 class="titulo-login">
            Sistema de Validación de Escuelas <br>
            y Programas Tecnológicos
            <span></span>
        </h2>

        <form method="post">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                <span class="toggle-pass" onclick="togglePassword()">👁</span>
            </div>
            <button type="submit">Entrar</button>
            <?php if ($mensaje): ?>
                <div class="mensaje"><?= $mensaje ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h4>Contacto</h4>
            <p>Departamento de Computación Electrónica en Educación Elemental</p>
            <p>Correo electronico: adg0118p.administracion@dee.edu.mx adg0118p@dee.edu.mx</p>
            <p>Tel: 722 265 1200 Exts:  8163, 8164</p>
        </div>
        <div class="footer-section">
            <h4>Ubicación</h4>
            <p>Blvrd Toluca - Metepec Num. 505, Col. La Purisima, C.P. 52169, Metepec, Méx.</p>
        </div>
         <div class="footer-section">
            <h4>Enlaces de interés</h4>
            <a href="https://red.dee.edu.mx/" target="_blank" rel="noopener noreferrer">https://red.dee.edu.mx/</a>
        </div>

       
    </div>
</footer>

<script>
function togglePassword() {
    const passInput = document.getElementById('password');
    passInput.type = passInput.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
