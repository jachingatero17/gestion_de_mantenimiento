<?php 
require_once "includes/conexion.php"; 
require_once "includes/header.php"; 

// Contar estadísticas rápidas
$total_sucursales = $conexion->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
$total_mantenimientos = $conexion->query("SELECT COUNT(*) FROM mantenimientos")->fetchColumn();
?>

<div class="p-5 mb-4 bg-white rounded-3 shadow-sm border">
    <div class="container-fluid py-2">
        <h1 class="display-6 fw-bold text-dark"><i class="fa-solid fa-gears text-primary"></i> Panel de Control</h1>
        <p class="col-md-8 fs-6 text-muted">Bienvenido al sistema de administración de sucursales, equipos, inventario y control de mantenimientos con evidencia fotográfica.</p>
    </div>
</div>

<!-- Tarjetas con Contadores (Ajustadas a 2 columnas) -->
<div class="row g-4 mb-4">
    <!-- Tarjeta 1: Sucursales -->
    <div class="col-12 col-md-6">
        <div class="card text-white bg-primary shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title text-uppercase">Sucursales Activas</h6>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $total_sucursales; ?></h2>
                </div>
                <i class="fa-solid fa-building fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="sucursales.php" class="text-white text-decoration-none small">Ver sucursales <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Mantenimientos -->
    <div class="col-12 col-md-6">
        <div class="card text-white bg-warning shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center text-dark">
                <div>
                    <h6 class="card-title text-uppercase">Mantenimientos Realizados</h6>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $total_mantenimientos; ?></h2>
                </div>
                <i class="fa-solid fa-screwdriver-wrench fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="ver_historial.php" class="text-dark text-decoration-none small">Consultar historial <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>