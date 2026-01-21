<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'otde') {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
if ($conexion->connect_error) die("Conexión fallida: " . $conexion->connect_error);

// Obtener listado de escuelas
$escuelas = $conexion->query("SELECT * FROM datos_escuelas ORDER BY nombre_ct ASC")->fetch_all(MYSQLI_ASSOC);
$totalEscuelas = count($escuelas);
$totalCFE = count(array_filter($escuelas, fn($e) => strtolower(trim($e['internet_cfe']))==='beneficiado'));
$totalUNETE = count(array_filter($escuelas, fn($e) => !empty(trim($e['programa_unete']))));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel OTDE | SEIEM</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<style>
body{font-family:'Poppins',sans-serif;background:#f5f5f5;color:#333;padding:20px;}
header{background:#7E0808;color:white;padding:15px;display:flex;justify-content:space-between;align-items:center;}
header h1{font-size:24px;}
header a{text-decoration:none;background:#B88A4F;color:white;padding:8px 16px;border-radius:8px;font-weight:600;}
.dashboard{display:flex;gap:20px;margin:30px 0;flex-wrap:wrap;}
.dashboard .card{flex:1;min-width:200px;background:linear-gradient(135deg,#7E0808,#B88A4F);padding:25px;border-radius:15px;color:white;text-align:center;}
table.dataTable{border-radius:12px;overflow:hidden;}
table.dataTable thead th{background:#f7f4f0;color:#5e5e5e;font-weight:600;}
.filter-select {margin-bottom:15px;padding:8px;border-radius:8px;border:1px solid #d6c3b2;}
</style>
</head>
<body>

<header>
<h1>Sistema de validación de Programas Tecnológicos - OTDE</h1>
<a href="login.php">Cerrar sesión</a>
</header>

<div class="dashboard">
<div class="card"><h3>Total Escuelas</h3><p><?= $totalEscuelas ?></p></div>
<div class="card"><h3>Beneficiarios Internet CFE</h3><p><?= $totalCFE ?></p></div>
<div class="card"><h3>Beneficiarios UNETE</h3><p><?= $totalUNETE ?></p></div>
</div>

<h2>Listado General de Escuelas</h2>
<label>Filtrar por programa:</label>
<select id="filtroPrograma" class="filter-select">
<option value="">Todos</option>
<option value="Internet CFE">Internet CFE</option>
<option value="UNETE">UNETE</option>
<option value="General">General</option>
</select>

<table id="tablaEscuelas" class="display">
<thead>
<tr>
<th>CCT</th>
<th>Nombre CT</th>
<th>Sector</th>
<th>Zona</th>
<th>Programas</th>
<th>Estado Formularios</th>
</tr>
</thead>
<tbody>
<?php foreach($escuelas as $escuela):
    $programas = ['General']; // Todas tienen general
    if(strtolower(trim($escuela['internet_cfe']))==='beneficiado') $programas[] = 'CFE';
    if(!empty(trim($escuela['programa_unete']))) $programas[] = 'UNETE';

    $cct = $escuela['cct'];
    $estados = [];
    foreach($programas as $form){
        $formDB = strtolower($form)=='cfe' ? 'cfe' : (strtolower($form)=='unete' ? 'unete' : 'general');
        $contestada = $conexion->query("SELECT COUNT(*) as cnt FROM respuestas WHERE cct='$cct' AND formulario='$formDB'")->fetch_assoc()['cnt'] > 0;
        $estados[] = "$form: ".($contestada ? '✔' : '❌');
    }
?>
<tr>
<td><?= $escuela['cct'] ?></td>
<td><?= $escuela['nombre_ct'] ?></td>
<td><?= $escuela['sector_educativo'] ?></td>
<td><?= $escuela['zona_escolar'] ?></td>
<td><?= implode(', ', $programas) ?></td>
<td><?= implode(' | ', $estados) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
$(document).ready(function(){
    var table = $('#tablaEscuelas').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50]
    });

    $('#filtroPrograma').on('change', function(){
        var val = $(this).val();
        if(val === ''){
            table.column(4).search('').draw();
        } else {
            table.column(4).search(val).draw();
        }
    });
});
</script>

</body>
</html>
