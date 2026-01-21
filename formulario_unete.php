<?php
session_start();
if (!isset($_SESSION['cct'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$cct = $_SESSION['cct'];

// Verificar si ya contestó el formulario UNETE
$yaContesto = $conexion->query("
    SELECT COUNT(*) as total 
    FROM respuestas 
    WHERE cct = '$cct' 
      AND formulario = 'Unete'
")->fetch_assoc();

$yaContesto = $yaContesto['total'] > 0;

// Si contestó, traemos sus respuestas
if ($yaContesto) {
    $respuestas = $conexion->query("
        SELECT p.texto, r.respuesta
        FROM respuestas r
        INNER JOIN preguntas p ON r.pregunta_id = p.id
        WHERE r.cct = '$cct' 
          AND r.formulario = 'Unete'
        ORDER BY p.id
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    // Traer solo las preguntas del formulario UNETE
    $datosFormulario = $conexion->query("
        SELECT * 
        FROM preguntas 
        WHERE formulario = 'Unete'
        ORDER BY id
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Formulario Unete SEIEM</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
header { background-color: white; border-bottom: 2px solid #7E0808; }
.top-bar { display: flex; align-items: center; justify-content: center; padding: 20px 10px; }
.top-bar img { height: 80px; width: auto; }
header hr { border: 0; border-top: 1px solid #7E0808; width: 60%; margin: 10px auto; }

.form-container { flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 40px 20px; }
.form-box { background: white; padding: 40px 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); width: 650px; text-align: left; animation: fadeIn 1s ease; position: relative; }
@keyframes fadeIn { 0% {opacity: 0; transform: translateY(-20px);} 100% {opacity: 1; transform: translateY(0);} }
.form-box h2 { text-align: center; color: #7E0808; margin-bottom: 20px; font-size: 28px; }

/* Preguntas */
.pregunta {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}
.pregunta label {
    display: block;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 10px;
}
input[type="text"], input[type="date"], select {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #ccc;
    transition: all 0.2s;
}
input[type="text"]:focus, input[type="date"]:focus, select:focus {
    border-color: #7E0808;
    box-shadow: 0 0 6px rgba(126,8,8,0.2);
    outline: none;
}
.radio-group label {
    margin-right: 15px;
    font-weight: normal;
    font-size: 14px;
}
input[type="radio"] {
    transform: scale(1.1);
    margin-right: 5px;
}

/* Botón de enviar arriba */
.btn-enviar {
    background-color: #7E0808;
    color: white;
    padding: 12px 24px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: block;
    margin: 20px auto;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    transition: all 0.3s;
}
.btn-enviar:hover {
    background-color: #a00000;
    transform: translateY(-2px);
}

.mensaje-ok { color:green; font-weight:bold; text-align:center; margin-bottom: 20px; }

footer { background-color: #7E0808; color: white; padding: 20px; text-align: center; }
.footer-content { display: flex; justify-content: space-between; flex-wrap: wrap; max-width: 900px; margin: auto; }
.footer-section { flex: 1; padding: 10px; min-width: 200px; }
.footer-section h4 { margin-bottom: 10px; font-size: 15px; font-weight: 600; }
.footer-section p, .footer-section a { font-size: 14px; color: white; text-decoration: none; line-height: 1.5; }
.footer-section a:hover { text-decoration: underline; }
.footer-logo img { height: 100px; margin-top: 40px; }

/* Botón regresar al perfil */
.btn-perfil {
    background: #B88A4F;
    color: white;
    padding: 10px 20px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 25px;
    transition: all 0.3s;
}
.btn-perfil:hover {
    background-color: #7E0808;
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

<div class="form-container">
    <div class="form-box">
        <button type="button" class="btn-perfil" onclick="window.location.href='perfil.php'">Regresar a Perfil</button>
        <h2>Programa "UNETE"</h2>

        <?php if ($yaContesto): ?>
            <p class="mensaje-ok">✅ Gracias por contestar este formulario.</p>
            <ul>
            <?php foreach ($respuestas as $r): ?>
                <li><b><?= htmlspecialchars($r['texto']) ?>:</b> <?= htmlspecialchars($r['respuesta']) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <form id="formulario_unete" method="post" action="guardar_respuestas.php">
                <input type="hidden" name="formulario" value="Unete">

                <button type="submit" class="btn-enviar">Enviar</button> <!-- Botón arriba -->

                <?php foreach ($datosFormulario as $pregunta): ?>
                <div class="pregunta" 
                     data-id="<?= $pregunta['id'] ?>" 
                     data-depende="<?= $pregunta['depende_de'] ?? '' ?>" 
                     data-valor="<?= htmlspecialchars($pregunta['valor_dependencia'] ?? '', ENT_QUOTES) ?>">
                    <label><?= htmlspecialchars($pregunta['texto'], ENT_QUOTES) ?></label>

                    <?php if ($pregunta['tipo'] === 'radio'): 
                        $opciones = explode(',', $pregunta['opciones']); ?>
                        <div class="radio-group">
                        <?php foreach($opciones as $op): ?>
                            <label><input type="radio" name="pregunta_<?= $pregunta['id'] ?>" value="<?= trim($op) ?>"> <?= trim($op) ?></label>
                        <?php endforeach; ?>
                        </div>
                    <?php elseif ($pregunta['tipo'] === 'select'): 
                        $opciones = explode(',', $pregunta['opciones']); ?>
                        <select name="pregunta_<?= $pregunta['id'] ?>">
                            <option value="">--Selecciona--</option>
                            <?php foreach($opciones as $op): ?>
                                <option value="<?= trim($op) ?>"><?= trim($op) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($pregunta['tipo'] === 'text'): ?>
                        <input type="text" name="pregunta_<?= $pregunta['id'] ?>">
                    <?php elseif ($pregunta['tipo'] === 'date'): ?>
                        <input type="date" name="pregunta_<?= $pregunta['id'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </form>
        <?php endif; ?>
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
$(document).ready(function() {
    function actualizarDependencias() {
        $('.pregunta').each(function() {
            const depende = $(this).data('depende');
            const valor = $(this).data('valor');
            if (depende) {
                const padre = $('[name="pregunta_' + depende + '"]');
                let mostrar = false;

                if (padre.length && padre.attr('type') === 'radio') {
                    mostrar = padre.filter(':checked').val() === valor;
                } else {
                    mostrar = padre.val() === valor;
                }
                $(this).toggle(mostrar);
            }
        });
    }
    actualizarDependencias();
    $('input, select').on('change', actualizarDependencias);
});
</script>
</body>
</html>
