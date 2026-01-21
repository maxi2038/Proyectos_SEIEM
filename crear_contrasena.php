<?php
session_start();
$mensaje = "";

if (!isset($_SESSION['nuevo_usuario_otde']) && !isset($_SESSION['nuevo_usuario_cct'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nueva = $_POST["nueva"];
    $confirmar = $_POST["confirmar"];

    if (strlen($nueva) < 4) {
        $mensaje = "La contraseña debe tener al menos 4 caracteres.";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $conexion = new mysqli("localhost", "root", "", "123");

        // Si es escuela
        if (isset($_SESSION['nuevo_usuario_cct'])) {
            $cct = $_SESSION['nuevo_usuario_cct'];
            $stmt = $conexion->prepare("UPDATE datos_escuelas SET contrasena = ? WHERE cct = ?");
            $stmt->bind_param("ss", $hash, $cct);
            $stmt->execute();

            unset($_SESSION['nuevo_usuario_cct']);
            $_SESSION['usuario'] = 'escuela';
            $_SESSION['cct'] = $cct;
            header("Location: perfil.php");
            exit();
        }

        // Si es OTDE
        if (isset($_SESSION['nuevo_usuario_otde'])) {
            $usuario = $_SESSION['nuevo_usuario_otde'];
            $stmt = $conexion->prepare("UPDATE usuarios_otde SET contrasena = ? WHERE usuario = ?");
            $stmt->bind_param("ss", $hash, $usuario);
            $stmt->execute();

            unset($_SESSION['nuevo_usuario_otde']);
            $_SESSION['usuario'] = 'otde';
            $_SESSION['otde'] = $usuario;
            header("Location: panel_otde.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
        gap: 15px;
        padding: 20px 10px;
    }
    .top-bar img {
        height: 80px;
        width: auto;
    }

    /* Contenedor centrador */
    .centrador {
        display: flex;
        justify-content: center;  /* Centra horizontalmente */
        align-items: center;      /* Centra verticalmente */
        flex: 1;                  /* Ocupa todo el espacio disponible */
    }

    .container {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid #e0c9a6;
        width: 100%;
        max-width: 400px;
    }

    h2 {
        color: #7E0808;
        text-align: center;
        margin-bottom: 20px;
    }

    input {
        width: 100%;
        padding: 12px;
        margin-top: 12px;
        border: 1.5px solid #d6c3b2;
        border-radius: 10px;
        background: #fef9f4;
        font-size: 15px;
    }

    button {
        width: 100%;
        padding: 12px;
        margin-top: 20px;
        background: linear-gradient(to right, #7E0808, #B88A4F);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
    }

    .mensaje {
        margin-top: 15px;
        color: #c0392b;
        text-align: center;
        font-weight: 600;
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
</header>

<!-- Contenedor centrador -->
<div class="centrador">
    <div class="container">
        <h2>Crear Nueva Contraseña</h2>
        <form method="post">
            <input type="password" name="nueva" placeholder="Nueva contraseña" required>
            <input type="password" name="confirmar" placeholder="Confirmar contraseña" required>
            <button type="submit">Guardar contraseña</button>
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
            <h4>Enlaces de interes</h4>
            <a href="https://red.dee.edu.mx/" target="_blank" rel="noopener noreferrer">https://red.dee.edu.mx/</a>
        </div>
    </div>
</footer>

</body>
</html>
