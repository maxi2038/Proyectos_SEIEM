<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
if ($conexion->connect_error) die("Conexión fallida: " . $conexion->connect_error);

// FUNCIONES
function obtenerPreguntas($conexion, $formulario, $pagina = 1, $porPagina = 5) {
    $offset = ($pagina - 1) * $porPagina;
    $stmt = $conexion->prepare("SELECT * FROM preguntas WHERE formulario = ? ORDER BY id LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $formulario, $porPagina, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function contarPreguntas($conexion, $formulario) {
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM preguntas WHERE formulario = ?");
    $stmt->bind_param("s", $formulario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['total'] ?? 0;
}

function obtenerRespuestas($conexion, $pregunta_id) {
    $stmt = $conexion->prepare("SELECT respuesta, COUNT(*) as total FROM respuestas WHERE pregunta_id = ? GROUP BY respuesta");
    $stmt->bind_param("i", $pregunta_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function contarEscuelasQueRespondieron($conexion, $formulario) {
    $stmt = $conexion->prepare("
        SELECT COUNT(DISTINCT de.cct) AS total
        FROM datos_escuelas de
        JOIN respuestas r ON r.cct = de.cct
        JOIN preguntas p ON p.id = r.pregunta_id
        WHERE p.formulario = ?
    ");
    $stmt->bind_param("s", $formulario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['total'] ?? 0;
}

// Contar total de escuelas registradas
$totalEscuelas = $conexion->query("SELECT COUNT(*) as total FROM datos_escuelas")->fetch_assoc()['total'] ?? 1;

// POST: agregar/editar/eliminar
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['guardar_edicion'])) {
        $stmt = $conexion->prepare("UPDATE preguntas SET texto=?, tipo=?, opciones=?, depende_de=?, valor_dependencia=? WHERE id=?");
        $depende = $_POST['editar_depende'] ?: NULL;
        $stmt->bind_param("sssisi", $_POST['editar_texto'], $_POST['editar_tipo'], $_POST['editar_opciones'], $depende, $_POST['editar_valor'], $_POST['editar_id']);
        $stmt->execute();
    } elseif (isset($_POST['eliminar_pregunta'])) {
        $stmt = $conexion->prepare("DELETE FROM respuestas WHERE pregunta_id=?");
        $stmt->bind_param("i", $_POST['eliminar_id']);
        $stmt->execute();
        $stmt = $conexion->prepare("DELETE FROM preguntas WHERE id=?");
        $stmt->bind_param("i", $_POST['eliminar_id']);
        $stmt->execute();
    } elseif (isset($_POST['agregar_pregunta'])) {
        $stmt = $conexion->prepare("INSERT INTO preguntas (formulario, texto, tipo, opciones, depende_de, valor_dependencia) VALUES (?, ?, ?, ?, ?, ?)");
        $depende = $_POST['depende'] ?: NULL;
        $stmt->bind_param("ssssss", $_POST['formulario'], $_POST['texto'], $_POST['tipo'], $_POST['opciones'], $depende, $_POST['valor_depende']);
        $stmt->execute();
    }
    header("Location: admin.php");
    exit();
}

$porPagina = 5;
$formularios = ['cfe', 'unete', 'general'];
$datos = [];
foreach ($formularios as $form) {
    $pagina = isset($_GET["pagina_$form"]) ? max(1, intval($_GET["pagina_$form"])) : 1;
    $datos[$form] = [
        'preguntas' => obtenerPreguntas($conexion, $form, $pagina, $porPagina),
        'total' => contarPreguntas($conexion, $form),
        'pagina' => $pagina
    ];
}

$preguntasTotales = $conexion->query("SELECT * FROM preguntas")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración | SEIEM</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f6f0eb;color:#333;padding:0 30px 50px 30px;}
header{position:sticky;top:0;background:#fff;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:30px;border-radius:0 0 12px 12px;z-index:1000;}
header h1{font-size:22px;color:#7E0808;}
header a{text-decoration:none;background:#7E0808;color:white;padding:8px 15px;border-radius:8px;font-weight:600;transition:0.3s;}
header a:hover{background:#a00000;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:25px;}
section{background:#fff;padding:20px;border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.1);border:1px solid #e0c9a6;animation:fadeIn 0.8s ease;}
@keyframes fadeIn{0%{opacity:0;transform:translateY(-15px);}100%{opacity:1;transform:translateY(0);}}
h2{color:#7E0808;margin-bottom:15px;}
form input, form select, form textarea, form button{width:100%;padding:10px 12px;margin:8px 0;font-size:14px;border-radius:8px;border:1px solid #d6c3b2;transition:all 0.3s ease;}
form input:focus, form select:focus, form textarea:focus{border-color:#7E0808;box-shadow:0 0 8px rgba(126,8,8,0.2);outline:none;}
form button{background:#7E0808;color:white;border:none;cursor:pointer;font-weight:600;margin-top:10px;transition:all 0.3s ease;}
form button:hover{background:#5c0505;transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,0,0,0.2);}
.pregunta-card{background:#fafafa;padding:15px;border-radius:12px;margin-bottom:15px;border:1px solid #e0c9a6;}
.chart-container{position:relative;width:100%;height:250px;margin-top:15px;}
.btn-exportar{display:inline-block;background:#066f42;color:white;padding:6px 12px;font-size:13px;border-radius:8px;text-decoration:none;margin-left:10px;transition:0.3s;}
.btn-exportar:hover{background-color:#098a53;}
.pagination{margin-top:10px;}
.pagination a{margin:0 5px;text-decoration:none;color:#7E0808;}
.pagination a[style*="font-weight:bold"]{font-weight:bold;}
.dashboard{display:flex;gap:20px;margin-bottom:30px;flex-wrap:wrap;}
.dashboard .card{flex:1;background:#fff;padding:20px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);border:1px solid #e0c9a6;text-align:center;}
.dashboard .card h3{color:#7E0808;margin-bottom:10px;}
.dashboard .card p{font-size:18px;font-weight:600;color:#333;}
</style>
</head>
<body>

<header>
<h1>Panel de Administración SEIEM</h1>
<a href="login.php">Cerrar sesión</a>
</header>

<!-- DASHBOARD -->
<div class="dashboard">
<?php foreach($formularios as $form): 
$pregTotal = contarPreguntas($conexion,$form);
$escuelasResp = contarEscuelasQueRespondieron($conexion,$form);
$porcentaje = round(($escuelasResp/$totalEscuelas)*100,1);
?>
<div class="card">
<h3><?= strtoupper($form) ?></h3>
<p>Preguntas: <?= $pregTotal ?></p>
<p>Escuelas que respondieron: <?= $escuelasResp ?></p>
<p>Completado: <?= $porcentaje ?>%</p>
</div>
<?php endforeach; ?>
</div>

<!-- AGREGAR PREGUNTA -->
<section>
<h2>Agregar Nueva Pregunta</h2>
<form method="post">
<select name="formulario" required>
<option value="">Selecciona formulario</option>
<option value="cfe">Internet CFE</option>
<option value="unete">Programa UNETE</option>
<option value="general">General</option>
</select>
<textarea name="texto" rows="2" required placeholder="Pregunta"></textarea>
<select name="tipo" required>
<option value="">Selecciona tipo</option>
<option value="radio">Radio</option>
<option value="select">Select</option>
<option value="text">Texto</option>
<option value="date">Fecha</option>
</select>
<textarea name="opciones" placeholder="Opciones separadas por coma"></textarea>
<select name="depende">
<option value="">Pregunta dependiente de...</option>
<?php foreach($preguntasTotales as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['texto'] ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="valor_depende" placeholder="Valor que activa esta pregunta">
<button type="submit" name="agregar_pregunta">Agregar Pregunta</button>
</form>
</section>

<div class="grid">
<?php foreach ($datos as $formulario => $info): 
$escuelasRespondieron = contarEscuelasQueRespondieron($conexion, $formulario); ?>
<section>
<h2><?= strtoupper($formulario) ?> - Escuelas que respondieron: <?= $escuelasRespondieron ?> 
<a class="btn-exportar" href="exportar_csv.php?formulario=<?= $formulario ?>">Exportar CSV</a>
</h2>

<?php foreach ($info['preguntas'] as $pregunta): 
$id = $pregunta['id'];
$respuestas = obtenerRespuestas($conexion, $id); ?>
<div class="pregunta-card">
<h4><?= $pregunta['texto'] ?></h4>

<form method="post">
<input type="hidden" name="editar_id" value="<?= $id ?>">
<textarea name="editar_texto"><?= $pregunta['texto'] ?></textarea>
<select name="editar_tipo">
<option value="radio" <?= $pregunta['tipo']==='radio'?'selected':'' ?>>Radio</option>
<option value="select" <?= $pregunta['tipo']==='select'?'selected':'' ?>>Select</option>
<option value="text" <?= $pregunta['tipo']==='text'?'selected':'' ?>>Texto</option>
<option value="date" <?= $pregunta['tipo']==='date'?'selected':'' ?>>Fecha</option>
</select>
<textarea name="editar_opciones"><?= $pregunta['opciones'] ?></textarea>
<select name="editar_depende">
<option value="">Pregunta dependiente de...</option>
<?php foreach($preguntasTotales as $p): ?>
<option value="<?= $p['id'] ?>" <?= $pregunta['depende_de']==$p['id']?'selected':'' ?>><?= $p['texto'] ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="editar_valor" value="<?= $pregunta['valor_dependencia'] ?>">
<button name="guardar_edicion">Guardar</button>
</form>

<form method="post">
<input type="hidden" name="eliminar_id" value="<?= $id ?>">
<button name="eliminar_pregunta">Eliminar</button>
</form>

<?php if($respuestas): 
$labels = json_encode(array_map(fn($r)=>$r['respuesta']?:'Sin respuesta',$respuestas));
$data = json_encode(array_map(fn($r)=>(int)$r['total'],$respuestas));
$colors = json_encode(array_map(fn()=>sprintf('#%06X', mt_rand(0,0xFFFFFF)), $respuestas));
$canvasId = "chart_{$formulario}_{$id}"; ?>
<div class="chart-container"><canvas id="<?= $canvasId ?>"></canvas></div>
<script>
new Chart(document.getElementById('<?= $canvasId ?>'),{
type:'bar',
data:{labels: <?= $labels ?>, datasets:[{label:'Respuestas', data: <?= $data ?>, backgroundColor: <?= $colors ?>}]},
options:{responsive:true, maintainAspectRatio:false,
plugins:{legend:{display:false}, datalabels:{anchor:'end',align:'end',formatter:(value,ctx)=>{let sum=ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);return ((value/sum)*100).toFixed(1)+'%';}, color:'#000', font:{weight:'bold'}}},
scales:{y:{beginAtZero:true}}}, plugins:[ChartDataLabels]});
</script>
<?php endif; ?>
</div>
<?php endforeach; ?>

<?php
$totalPaginas = ceil($info['total']/$porPagina);
if($totalPaginas>1){
echo "<div class='pagination'>";
for($i=1;$i<=$totalPaginas;$i++){
$clase=$i===$info['pagina']?'style=\"font-weight:bold;\"':'';
echo "<a href='?pagina_{$formulario}={$i}' {$clase}>{$i}</a> ";
}
echo "</div>";
}
?>
</section>
<?php endforeach; ?>
</div>
</body>
</html>
