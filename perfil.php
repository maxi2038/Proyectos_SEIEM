<?php
session_start();
if (!isset($_SESSION['cct'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
$cct = $_SESSION['cct'];
$mensaje = "";

// Inicializamos la variable de formularios contestados si no existe
if (!isset($_SESSION['formularios_contestados'])) {
    $_SESSION['formularios_contestados'] = false;
}

$stmt = $conexion->prepare("SELECT * FROM datos_escuelas WHERE cct = ?");
$stmt->bind_param("s", $cct);
$stmt->execute();
$resultado = $stmt->get_result();
$escuela = $resultado->fetch_assoc();

if (isset($_POST['guardar'])) {
    $nombre_director = $_POST['nombre_director'];
    $telefono_director = $_POST['telefono_director'];

    $stmt = $conexion->prepare("UPDATE datos_escuelas SET nombre_director = ?, telefono_director = ? WHERE cct = ?");
    $stmt->bind_param("sss", $nombre_director, $telefono_director, $cct);
    $stmt->execute();

    $mensaje = "✅ Datos actualizados correctamente.";

    // Cuando se guarda, marcamos que al menos un formulario fue contestado
    $_SESSION['formularios_contestados'] = true;

    $stmt = $conexion->prepare("SELECT * FROM datos_escuelas WHERE cct = ?");
    $stmt->bind_param("s", $cct);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $escuela = $resultado->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de la Escuela | SEIEM</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* --- estilos (los tuyos originales, no toqué nada) --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        * {box-sizing: border-box;margin: 0;padding: 0;}
        body {font-family: 'Poppins', sans-serif;background: linear-gradient(to right, #fdfbf9, #f3ede8);color: #333;line-height: 1.6;}
        header {background: #7E0808;color: white;padding: 20px 40px;display: flex;justify-content: space-between;align-items: center;border-bottom: 5px solid #B88A4F;box-shadow: 0 8px 20px rgba(0,0,0,0.1);border-radius: 0 0 12px 12px;}
        header h1 {font-size: 24px;font-weight: 600;}
        .logout {background: #B88A4F;color: white;padding: 10px 20px;border-radius: 8px;font-weight: bold;text-decoration: none;transition: 0.3s;}
        .logout:hover {background: #5e3614;}
        .main {max-width: 1200px;margin: 50px auto;padding: 0 20px;}
        .row {display: flex;gap: 30px;flex-wrap: wrap;}
        .card {background: rgba(255, 255, 255, 0.95);backdrop-filter: blur(8px);border-radius: 20px;box-shadow: 0 16px 40px rgba(0,0,0,0.08);padding: 30px;flex: 1;min-width: 340px;border: 2px solid #e9d7bf;transition: transform 0.3s, box-shadow 0.3s;}
        .card:hover {transform: translateY(-6px);box-shadow: 0 20px 50px rgba(0,0,0,0.12);}
        h2 {font-size: 22px;color: #7E0808;margin-bottom: 20px;border-left: 6px solid #B88A4F;padding-left: 12px;}
        .alert {background: #fff8e1;padding: 15px;border-left: 5px solid #F4C542;border-radius: 10px;margin-bottom: 20px;font-weight: 600;color: #6a4c00;}
        .success {background: #e7f9ed;border-left: 6px solid #a89c32ff;padding: 14px 18px;margin-top: 20px;border-radius: 8px;color: #20723e;font-weight: 600;}
        table {width: 100%;border-collapse: collapse;margin-top: 15px;}
        th {text-align: left;padding: 12px;background-color: #fdf6ec;color: #5a5a5a;font-weight: 600;width: 35%;}
        td {padding: 12px;background: #fffdfb;border-bottom: 1px solid #eee;}
        input, select {width: 100%;padding: 10px;font-size: 15px;border-radius: 10px;border: 1.5px solid #d6c3b2;margin-top: 6px;background-color: #fef9f4;transition: all 0.2s ease-in-out;}
        input:focus, select:focus {outline: none;border-color: #B88A4F;background-color: #fff;}
        .resaltado {background-color: #fff8e6;border: 2px solid #B88A4F;font-weight: bold;}
        .verifica-label {font-size: 13px;color: #C74A25;font-weight: bold;margin-bottom: 6px;}
        .btn {background: linear-gradient(to right, #7E0808, #B88A4F);color: white;border: none;padding: 14px 30px;font-size: 16px;border-radius: 10px;font-weight: bold;cursor: pointer;margin-top: 25px;box-shadow: 0 8px 15px rgba(0,0,0,0.1);transition: all 0.3s ease;}
        .btn:hover {transform: translateY(-2px);opacity: 0.9;}
        .form-buttons {display: flex;flex-direction: column;gap: 14px;margin-top: 30px;}
        .form-buttons a {text-align: center;padding: 14px;background: #7E0808;color: white;text-decoration: none;border-radius: 10px;font-weight: bold;transition: all 0.3s ease;}
        .form-buttons a.red {background: #B88A4F;}
        .form-buttons a:hover {transform: translateY(-1px);opacity: 0.9;}
        #map {height: 480px;border-radius: 14px;margin-top: 20px;border: 2px solid #B88A4F;}
        @media (max-width: 900px) {.row {flex-direction: column;}}
        footer {background-color: #7E0808;color: white;padding: 20px;text-align: center;}
        .footer-content {display: flex;justify-content: space-between;flex-wrap: wrap;max-width: 900px;margin: auto;}
        .footer-section {flex: 1;padding: 10px;min-width: 200px;}
        .footer-section h4 {margin-bottom: 10px;font-size: 15px;font-weight: 600;}
        .footer-section p, .footer-section a {font-size: 14px;color: white;text-decoration: none;line-height: 1.5;}
        .footer-section a:hover {text-decoration: underline;}
        .footer-logo img {height: 100px;margin-top: 40px;}
    </style>
</head>
<body>

<header>
    <center><h1>Sistema de Validación de Escuelas y Programas Tecnológicos</h1></center>
    <a href="login.php" class="logout">Cerrar sesión</a>
</header>

<div class="main">
    <div class="row">
        <div class="card">
            <h2>Datos del Centro del Trabajo</h2>

            <?php if (strtolower(trim($escuela['internet_cfe'])) === 'beneficiado'): ?>
                <div class="alert beneficiario">✅ Escuela Beneficiada por el Proyecto <strong>"Internet para Todos - CFE 4G"</strong>.</div>
            <?php endif; ?>

            <?php if (!empty(trim($escuela['programa_unete']))): ?>
                <div class="alert beneficiario">✅ Escuela Beneficiada por el Programa <strong>"UNETE"</strong>.</div>
            <?php endif; ?>

            <form method="post">
                <table>
                    <tr><th>C.C.T</th><td><?= $escuela['cct'] ?></td></tr>
                    <tr><th>Subdirección o Departamento</th><td><?= $escuela['nivel_educativo'] ?></td></tr>
                    <tr><th>Nombre del C.T.</th><td><?= $escuela['nombre_ct'] ?></td></tr>
                    <tr><th>Domicilio</th><td><?= $escuela['domicilio'] ?></td></tr>
                    <tr><th>Sector Educativo</th><td><?= $escuela['sector_educativo'] ?></td></tr>
                    <tr><th>Zona Escolar</th><td><?= $escuela['zona_escolar'] ?></td></tr>
                    <tr><th>Turno</th><td><?= $escuela['turno'] ?></td></tr>
                    <tr><th>Georreferencia</th><td><?= $escuela['coordenadas'] ?></td></tr>
                    <tr>
                        <th>Nombre del Director</th>
                        <td><div class="verifica-label">⚠️ Verifica si la informacion es valida</div>
                            <input type="text" name="nombre_director" id="nombre_director" value="<?= $escuela['nombre_director'] ?>" class="resaltado" disabled>
                        </td>
                    </tr>
                    <tr>
                        <th>Teléfono del Director</th>
                        <td><div class="verifica-label">⚠️ Verifica si la informacion es valida</div>
                            <input type="text" name="telefono_director" id="telefono_director" value="<?= $escuela['telefono_director'] ?>" class="resaltado" disabled>
                        </td>
                    </tr>
                    <tr><th>¿Los datos del director son correctos?</th>
                        <td>
                            <select id="verificar_datos" onchange="toggleCampos()">
                                <option value="si">Sí</option>
                                <option value="no">No</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th>Correo Institucional</th><td><?= $escuela['correo_institucional'] ?></td></tr>

                    <?php if (strtolower(trim($escuela['internet_cfe'])) === 'beneficiado'): ?>
         
                    <?php endif; ?>

                    <?php if (!empty(trim($escuela['programa_unete']))): ?>
                 
                    <?php endif; ?>
                </table>

                <button type="submit" name="guardar" class="btn">Guardar Cambios</button>
            </form>

            <?php if ($mensaje): ?>
                <p class="success"><?= $mensaje ?></p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Ubicación Geográfica</h2>
            <div id="map"></div>
            <div class="form-buttons">
                <?php if (!$_SESSION['formularios_contestados']): ?>
                    <p style="font-weight:bold; color:#7E0808; text-align:center;">⚠️ Favor de contestar este formulario</p>
                <?php else: ?>
                    <p style="font-weight:bold; color:green; text-align:center;">Favor de contestar los siguientes formularios<br></p>
                <?php endif; ?>

                <a href="formulario_general.php">📝 Datos Generales</a>
                
                <?php if (strtolower(trim($escuela['internet_cfe'])) === 'beneficiado'): ?>
                    <a href="formulario_cfe.php" class="red">🌐 Proyecto "Internet para Todos - CFE 4G"</a>
                <?php endif; ?>

                <?php if (!empty(trim($escuela['programa_unete']))): ?>
                    <a href="formulario_unete.php" class="red">🤝 Programa "UNETE"</a>
                <?php endif; ?>
            </div>
        </div>
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

<script>
function toggleCampos() {
    const opcion = document.getElementById("verificar_datos").value;
    document.getElementById("nombre_director").disabled = (opcion !== "no");
    document.getElementById("telefono_director").disabled = (opcion !== "no");
}

window.onload = function () {
    const lat = <?= is_numeric($escuela['latitud']) ? $escuela['latitud'] : '19.4326' ?>;
    const lon = <?= is_numeric($escuela['longitud']) ? $escuela['longitud'] : '-99.1332' ?>;

    const map = L.map('map').setView([lat, lon], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
    }).addTo(map);

    L.marker([lat, lon]).addTo(map)
        .bindPopup("Ubicación de la escuela")
        .openPopup();
};
</script>

</body>
</html>
