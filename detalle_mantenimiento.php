<?php
require_once "includes/conexion.php";

if (!isset($_GET['id'])) {
    header("Location: ver_historial.php");
    exit();
}

$id_mantenimiento = $_GET['id'];

// 1. Obtener datos principales del mantenimiento
$sql = "SELECT m.*, s.nombre as sucursal_nombre, s.direccion as sucursal_dir, s.telefono as sucursal_tel,
               e.nombre as equipo_nombre, e.marca as equipo_marca, e.modelo as equipo_modelo, e.numero_serie as equipo_serial, e.estado as equipo_estado
        FROM mantenimientos m
        JOIN sucursales s ON m.sucursal_id = s.id
        JOIN equipos e ON m.equipo_id = e.id
        WHERE m.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_mantenimiento]);
$mant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mant) {
    die("Mantenimiento no encontrado.");
}

// 2. Obtener materiales consumidos en este trabajo
$stmt_mat = $conexion->prepare("SELECT mm.cantidad_usada, i.nombre_material, i.unidad_medida 
                                FROM mantenimiento_materiales mm
                                JOIN inventario i ON mm.inventario_id = i.id
                                WHERE mm.mantenimiento_id = ?");
$stmt_mat->execute([$id_mantenimiento]);
$materiales_usados = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener evidencias fotográficas
$stmt_fotos = $conexion->prepare("SELECT * FROM evidencias WHERE mantenimiento_id = ?");
$stmt_fotos->execute([$id_mantenimiento]);
$evidencias = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="mb-4 d-flex justify-content-between align-items-center d-print-none">
    <a href="ver_historial.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Volver al Historial</a>
    <button onclick="window.print();" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-print me-1"></i> Imprimir Comprobante</button>
</div>

<!-- FORMATO DE ACTA / DETALLE DE TRABAJO -->
<div class="card shadow border-0 mb-5">
    <div class="card-header bg-dark text-white p-4">
        <div class="row align-items-center">
            <div class="col-8">
                <h3 class="mb-0 fw-bold">Reporte de Mantenimiento #<?php echo str_pad($mant['id'], 5, "0", STR_PAD_LEFT); ?></h3>
                <small class="text-light opacity-75">Fecha de Ejecución: <?php echo date("d/m/Y", strtotime($mant['fecha'])); ?></small>
            </div>
            <div class="col-4 text-end">
                <span class="badge fs-6 <?php echo $mant['tipo_mantenimiento'] == 'Correctivo' ? 'bg-danger' : 'bg-info text-dark'; ?>">
                    <?php echo $mant['tipo_mantenimiento']; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        
        <!-- DATOS DE SUCURSAL Y EQUIPO -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 border-end">
                <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fa-solid fa-building me-1"></i> Datos de la Sucursal</h6>
                <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($mant['sucursal_nombre']); ?></p>
                <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($mant['sucursal_dir']); ?></p>
                <p class="mb-0"><strong>Teléfono:</strong> <?php echo htmlspecialchars($mant['sucursal_tel'] ?: 'N/A'); ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fa-solid fa-laptop me-1"></i> Datos del Equipo</h6>
                <p class="mb-1"><strong>Equipo:</strong> <?php echo htmlspecialchars($mant['equipo_nombre']); ?></p>
                <p class="mb-1"><strong>Marca / Modelo:</strong> <?php echo htmlspecialchars($mant['equipo_marca'] . ' ' . $mant['equipo_modelo']); ?></p>
                <p class="mb-1"><strong>Serial:</strong> <code><?php echo htmlspecialchars($mant['equipo_serial'] ?: 'S/N'); ?></code></p>
                <p class="mb-0"><strong>Estado actual:</strong> <span class="badge bg-success"><?php echo $mant['equipo_estado']; ?></span></p>
            </div>
        </div>

        <hr>

        <!-- DESCRIPCIÓN DEL TRABAJO -->
        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-bold mb-2"><i class="fa-solid fa-wrench me-1"></i> Descripción del Trabajo Realizado</h6>
            <div class="p-3 bg-light rounded border">
                <?php echo nl2br(htmlspecialchars($mant['descripcion_trabajo'])); ?>
            </div>
        </div>

        <?php if (!empty($mant['observaciones'])): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted fw-bold mb-2"><i class="fa-solid fa-note-sticky me-1"></i> Observaciones</h6>
                <div class="p-3 bg-light rounded border">
                    <?php echo nl2br(htmlspecialchars($mant['observaciones'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- MATERIALES UTILIZADOS -->
        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-bold mb-2"><i class="fa-solid fa-boxes-stacked me-1"></i> Repuestos y Materiales Consumidos</h6>
            <?php if (count($materiales_usados) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($materiales_usados as $m_u): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-check text-success me-2"></i><?php echo htmlspecialchars($m_u['nombre_material']); ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo $m_u['cantidad_usada'] . ' ' . $m_u['unidad_medida']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted small">No se consumieron materiales de inventario en este mantenimiento.</p>
            <?php endif; ?>
        </div>

        <!-- EVIDENCIAS FOTOGRÁFICAS -->
        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-bold mb-3"><i class="fa-solid fa-camera me-1"></i> Evidencias Fotográficas</h6>
            <?php if (count($evidencias) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($evidencias as $ev): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border">
                                <a href="<?php echo htmlspecialchars($ev['ruta_foto']); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($ev['ruta_foto']); ?>" class="card-img-top" style="height: 220px; object-fit: cover;" alt="Evidencia">
                                </a>
                                <div class="card-body p-2 text-center small text-muted">
                                    <?php echo htmlspecialchars($ev['descripcion'] ?: 'Foto adjunta'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small">No se adjuntaron fotografías para este registro.</p>
            <?php endif; ?>
        </div>

        <!-- FIRMAS DE CONFORMIDAD -->
        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong><?php echo htmlspecialchars($mant['tecnico_encargado']); ?></strong><br>
                    <small class="text-muted">Técnico Responsable</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong><?php echo htmlspecialchars($mant['recibido_por']); ?></strong><br>
                    <small class="text-muted">Recibido Conforme (Cliente / Encargado)</small>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once "includes/footer.php"; ?>