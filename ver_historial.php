<?php
require_once "includes/conexion.php";

// Parámetros de Búsqueda
$filtro_sucursal = isset($_GET['sucursal_id']) ? $_GET['sucursal_id'] : '';
$filtro_tipo     = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_buscar   = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construir Consulta Dinámica
$sql = "SELECT m.*, s.nombre as sucursal_nombre, e.nombre as equipo_nombre 
        FROM mantenimientos m
        JOIN sucursales s ON m.sucursal_id = s.id
        JOIN equipos e ON m.equipo_id = e.id
        WHERE 1=1 ";

$params = [];

if (!empty($filtro_sucursal)) {
    $sql .= " AND m.sucursal_id = ? ";
    $params[] = $filtro_sucursal;
}

if (!empty($filtro_tipo)) {
    $sql .= " AND m.tipo_mantenimiento = ? ";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_buscar)) {
    $sql .= " AND (m.tecnico_encargado LIKE ? OR m.recibido_por LIKE ? OR m.descripcion_trabajo LIKE ? OR e.nombre LIKE ?) ";
    $params[] = "%$filtro_buscar%";
    $params[] = "%$filtro_buscar%";
    $params[] = "%$filtro_buscar%";
    $params[] = "%$filtro_buscar%";
}

$sql .= " ORDER BY m.fecha DESC, m.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucursales = $conexion->query("SELECT id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Historial de Mantenimientos</h2>
        <small class="text-muted">Consulta trabajos anteriores, evidencias fotográficas y firmas de entrega</small>
    </div>
    <a href="mantenimientos.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Registrar Mantenimiento</a>
</div>

<!-- BARRA DE BÚSQUEDA Y FILTROS -->
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body">
        <form action="ver_historial.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Buscar por palabra clave / Técnico / Equipo:</label>
                <input type="text" name="buscar" class="form-control" placeholder="Ej: Carlos Pérez, Impresora, Formateo..." value="<?php echo htmlspecialchars($filtro_buscar); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filtrar por Sucursal:</label>
                <select name="sucursal_id" class="form-select">
                    <option value="">-- Todas las sucursales --</option>
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($filtro_sucursal == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tipo de Trabajo:</label>
                <select name="tipo" class="form-select">
                    <option value="">-- Todos los tipos --</option>
                    <option value="Preventivo" <?php echo ($filtro_tipo == 'Preventivo') ? 'selected' : ''; ?>>Preventivo</option>
                    <option value="Correctivo" <?php echo ($filtro_tipo == 'Correctivo') ? 'selected' : ''; ?>>Correctivo</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                <a href="ver_historial.php" class="btn btn-outline-secondary" title="Limpiar"><i class="fa-solid fa-rotate"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- TABLA DE HISTORIAL -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Sucursal</th>
                        <th>Equipo</th>
                        <th>Tipo</th>
                        <th>Técnico</th>
                        <th>Recibido Por</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($mantenimientos) > 0): ?>
                        <?php foreach ($mantenimientos as $m): ?>
                            <tr>
                                <td class="fw-bold"><?php echo date("d/m/Y", strtotime($m['fecha'])); ?></td>
                                <td><i class="fa-solid fa-building me-1 text-muted"></i><?php echo htmlspecialchars($m['sucursal_nombre']); ?></td>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($m['equipo_nombre']); ?></td>
                                <td>
                                    <span class="badge <?php echo $m['tipo_mantenimiento'] == 'Correctivo' ? 'bg-danger' : 'bg-info text-dark'; ?>">
                                        <?php echo $m['tipo_mantenimiento']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($m['tecnico_encargado']); ?></td>
                                <td><?php echo htmlspecialchars($m['recibido_por']); ?></td>
                                <td class="text-end">
                                    <a href="detalle_mantenimiento.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info text-white">
                                        <i class="fa-solid fa-eye me-1"></i> Ver Ficha
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No se encontraron registros de mantenimiento con los criterios seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>