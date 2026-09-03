<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Mantenimiento</title>
    <!-- Bootstrap 5 CSS (Multiplataforma) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos de FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<!-- Barra de Navegación Responsiva -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fa-solid fa-wrench text-warning me-2"></i>MantenimientoApp
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fa-solid fa-house me-1"></i> Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sucursales.php"><i class="fa-solid fa-building me-1"></i> Sucursales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="equipos.php"><i class="fa-solid fa-laptop me-1"></i> Equipos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="inventario.php"><i class="fa-solid fa-boxes-stacked me-1"></i> Inventario</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mantenimientos.php"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Mantenimientos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ver_historial.php"><i class="fa-solid fa-clock-rotate-left me-1"></i> Historial</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">