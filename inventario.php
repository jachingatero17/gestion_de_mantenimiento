<?php
require_once "includes/conexion.php";

$mensaje = "";
$tipo_alerta = "";

// 1. REGISTRAR NUEVO MATERIAL EN UNA SUCURSAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_material'])) {
    $sucursal_id    = $_POST['sucursal_id'];
    $nombre_material = trim($_POST['nombre_material']);
    $cantidad       = intval($_POST['cantidad']);
    $unidad_medida  = trim($_POST['unidad_medida']);

    if (!empty($sucursal_id) && !empty($nombre_material) && $cantidad >= 0) {
        $stmt = $conexion->prepare("INSERT INTO inventario (sucursal_id, nombre_material, cantidad, unidad_medida) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sucursal_id, $nombre_material, $cantidad, $unidad_medida]);
        $mensaje = "¡Material agregado al inventario con éxito!";
        $tipo_alerta = "success";
    } else {
        $mensaje = "Por favor completa todos los campos requeridos.";
        $tipo_alerta = "warning";
    }
}

// 2. SUMAR STOCK A UN MATERIAL EXISTENTE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sumar_stock'])) {
    $id_material     = $_POST['id_material'];
    $cantidad_sumar  = intval($_POST['cantidad_sumar']);

    if ($cantidad_sumar > 0) {
        $stmt = $conexion->prepare("UPDATE inventario SET cantidad = cantidad + ? WHERE id = ?");
        $stmt->execute([$cantidad_sumar, $id_material]);
        $mensaje = "¡Stock actualizado correctamente!";
        $tipo_alerta = "info";
    }
}

// 3. ELIMINAR MATERIAL
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM inventario WHERE id = ?");
    $stmt->execute([$id_eliminar]);
    $mensaje = "Material eliminado del inventario.";
    $tipo_alerta = "warning";
}

// 4. CONSULTAR INVENTARIO (CON FILTRO POR SUCURSAL)
$filtro_sucursal = isset($_GET['filtro_sucursal']) ? $_GET['filtro_sucursal'] : '';

$sql = "SELECT i.*, s.nombre as sucursal_nombre 
        FROM inventario i 
        JOIN sucursales s ON i.sucursal_id = s.id ";

if (!empty($filtro_sucursal)) {
    $sql .= " WHERE i.sucursal_id = :sucursal_id ";
}
$sql .= " ORDER BY s.nombre ASC, i.nombre_material ASC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_sucursal)) {
    $stmt->execute([':sucursal_id' => $filtro_sucursal]);
} else {
    $stmt->execute();
}
$materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de sucursales para los filtros y selects
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Inventario de Materiales</h2>
        <small class="text-muted">Control de repuestos, insumos y existencias por cada sucursal</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMaterial">
        <i class="fa-solid fa-plus me-1"></i> Agregar Material
    </button>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Barra de Filtrar por Sucursal -->
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body">
        <form action="inventario.php" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="fw-bold"><i class="fa-solid fa-filter me-1"></i>Filtrar por Sucursal:</label>
            </div>
            <div class="col-auto">
                <select name="filtro_sucursal" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todas las Sucursales --</option>
                    <?php foreach ($sucursales as $suc): ?>
                        <option value="<?php echo $suc['id']; ?>" <?php echo ($filtro_sucursal == $suc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($filtro_sucursal)): ?>
                <div class="col-auto">
                    <a href="inventario.php" class="btn btn-sm btn-outline-secondary">Quitar Filtro</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla de Materiales -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Material / Repuesto</th>
                        <th>Sucursal Asignada</th>
                        <th class="text-center">Stock Disponible</th>
                        <th class="text-center">Estado del Stock</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($materiales) > 0): ?>
                        <?php foreach ($materiales as $mat): ?>
                            <tr>
                                <td class="fw-bold fs-6"><?php echo htmlspecialchars($mat['nombre_material']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($mat['sucursal_nombre']); ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold fs-5">
                                    <?php echo $mat['cantidad']; ?> <small class="fs-6 text-muted"><?php echo htmlspecialchars($mat['unidad_medida']); ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($mat['cantidad'] <= 0): ?>
                                        <span class="badge bg-danger p-2"><i class="fa-solid fa-xmark me-1"></i> Agotado (0)</span>
                                    <?php elseif ($mat['cantidad'] <= 3): ?>
                                        <span class="badge bg-warning text-dark p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Stock Bajo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success p-2"><i class="fa-solid fa-check me-1"></i> Suficiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- Botón para añadir más stock -->
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalStock<?php echo $mat['id']; ?>" title="Agregar existencias">
                                        <i class="fa-solid fa-plus"></i> Añadir Stock
                                    </button>
                                    <!-- Botón para eliminar -->
                                    <a href="inventario.php?eliminar=<?php echo $mat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar este material?');" title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- MODAL PARA AÑADIR MÁS STOCK -->
                            <div class="modal fade" id="modalStock<?php echo $mat['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <form action="inventario.php" method="POST">
                                            <input type="hidden" name="id_material" value="<?php echo $mat['id']; ?>">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Añadir Stock</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-2 small">Material: <strong><?php echo htmlspecialchars($mat['nombre_material']); ?></strong></p>
                                                <p class="mb-3 small">Stock actual: <strong><?php echo $mat['cantidad']; ?></strong></p>
                                                <label class="form-label">¿Cuántas unidades vas a sumar?</label>
                                                <input type="number" name="cantidad_sumar" class="form-control" min="1" value="1" required>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="sumar_stock" class="btn btn-sm btn-success">Sumar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No se encontraron materiales registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR NUEVO MATERIAL -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="inventario.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-boxes-stacked me-1"></i> Registrar Nuevo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">¿A qué Sucursal pertenece este inventario? *</label>
                        <select name="sucursal_id" class="form-select" required>
                            <option value="">Selecciona una sucursal...</option>
                            <?php foreach ($sucursales as $suc): ?>
                                <option value="<?php echo $suc['id']; ?>"><?php echo htmlspecialchars($suc['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Material / Repuesto *</label>
                        <input type="text" name="nombre_material" class="form-control" placeholder="Ej: Cable UTP Cat 6, Pasta Térmica, Disco SSD 500GB" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Cantidad Inicial *</label>
                            <input type="number" name="cantidad" class="form-control" min="0" value="10" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Unidad de Medida</label>
                            <select name="unidad_medida" class="form-select">
                                <option value="unidades">Unidades / Piezas</option>
                                <option value="metros">Metros</option>
                                <option value="litros">Litros</option>
                                <option value="cajas">Cajas</option>
                                <option value="paquetes">Paquetes</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_material" class="btn btn-primary">Guardar Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>