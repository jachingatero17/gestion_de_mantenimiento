<?php
require_once "includes/conexion.php";

if (!isset($_GET['id'])) {
    header("Location: sucursales.php");
    exit();
}

$id_sucursal = $_GET['id'];

// Obtener datos de la sucursal
$stmt = $conexion->prepare("SELECT * FROM sucursales WHERE id = ?");
$stmt->execute([$id_sucursal]);
$sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sucursal) {
    die("Sucursal no encontrada.");
}

// Obtener los equipos de ESTA sucursal
$stmt_equipos = $conexion->prepare("SELECT * FROM equipos WHERE sucursal_id = ? ORDER BY id DESC");
$stmt_equipos->execute([$id_sucursal]);
$equipos = $stmt_equipos->fetchAll(PDO::FETCH_ASSOC);

// Obtener el inventario de ESTA sucursal
$stmt_inv = $conexion->prepare("SELECT * FROM inventario WHERE sucursal_id = ?");
$stmt_inv->execute([$id_sucursal]);
$inventario = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de mantenimientos de ESTA sucursal
$stmt_mant = $conexion->prepare("SELECT m.*, e.nombre as equipo_nombre 
                                FROM mantenimientos m 
                                JOIN equipos e ON m.equipo_id = e.id 
                                WHERE m.sucursal_id = ? ORDER BY m.fecha DESC");
$stmt_mant->execute([$id_sucursal]);
$mantenimientos = $stmt_mant->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="mb-4">
    <a href="sucursales.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Volver a Sucursales</a>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="fa-solid fa-building text-primary me-2"></i><?php echo htmlspecialchars($sucursal['nombre']); ?></h1>
            <p class="text-muted mb-0"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($sucursal['direccion']); ?> | <i class="fa-solid fa-user-tie me-1"></i>Encargado: <strong><?php echo htmlspecialchars($sucursal['encargado'] ?: 'No asignado'); ?></strong></p>
        </div>
        <a href="equipos.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Agregar Equipo</a>
    </div>
</div>

<div class="row g-4">
    <!-- COLUMNA 1: EQUIPOS E HISTORIAL -->
    <div class="col-12 col-lg-8">
        
        <!-- TABLA DE EQUIPOS EN ESTA SUCURSAL -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fa-solid fa-laptop me-2"></i>Equipos Instalados en esta Sede (<?php echo count($equipos); ?>)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Equipo / Máquina</th>
                                <th>Marca / Modelo</th>
                                <th>Serial</th>
                                <th>Estado Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($equipos) > 0): ?>
                                <?php foreach ($equipos as $eq): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($eq['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']); ?></td>
                                        <td><code><?php echo htmlspecialchars($eq['numero_serie'] ?: 'S/N'); ?></code></td>
                                        <td>
                                            <?php if ($eq['estado'] == 'Operativo'): ?>
                                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Operativo</span>
                                            <?php elseif ($eq['estado'] == 'En Falla'): ?>
                                                <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>En Falla (Dañado)</span>
                                            <?php elseif ($eq['estado'] == 'En Mantenimiento'): ?>
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-screwdriver-wrench me-1"></i>En Mantenimiento</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">De Baja</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay equipos registrados en esta sucursal todavía.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- HISTORIAL DE MANTENIMIENTOS EN ESTA SUCURSAL -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white fw-bold">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de Mantenimientos Realizados Aquí
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Equipo</th>
                                <th>Técnico</th>
                                <th>Tipo</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($mantenimientos) > 0): ?>
                                <?php foreach ($mantenimientos as $m): ?>
                                    <tr>
                                        <td><?php echo date("d/m/Y", strtotime($m['fecha'])); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($m['equipo_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($m['tecnico_encargado']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $m['tipo_mantenimiento'] == 'Correctivo' ? 'bg-danger' : 'bg-info text-dark'; ?>">
                                                <?php echo $m['tipo_mantenimiento']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="detalle_mantenimiento.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-dark">
                                                Ver Ficha
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No se han registrado mantenimientos en esta sucursal aún.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- COLUMNA 2: STOCK DE MATERIALES DE LA SEDE -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fa-solid fa-boxes-stacked me-2"></i>Stock de Materiales en Sede
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (count($inventario) > 0): ?>
                        <?php foreach ($inventario as $inv): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($inv['nombre_material']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $inv['cantidad'] . ' ' . $inv['unidad_medida']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center py-3">No hay materiales registrados para esta sucursal.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer bg-white">
                <a href="inventario.php" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-plus me-1"></i> Gestionar Materiales</a>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>