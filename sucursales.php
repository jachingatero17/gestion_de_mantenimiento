<?php
require_once "includes/conexion.php";

$mensaje = "";
$tipo_alerta = "";

// 1. REGISTRAR SUCURSAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_sucursal'])) {
    $nombre    = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $telefono  = trim($_POST['telefono']);
    $encargado = trim($_POST['encargado']);

    if (!empty($nombre) && !empty($direccion)) {
        $stmt = $conexion->prepare("INSERT INTO sucursales (nombre, direccion, telefono, encargado) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $direccion, $telefono, $encargado]);
        $mensaje = "¡Sucursal registrada con éxito!";
        $tipo_alerta = "success";
    }
}

// 2. EDITAR SUCURSAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_sucursal'])) {
    $id        = $_POST['id'];
    $nombre    = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $telefono  = trim($_POST['telefono']);
    $encargado = trim($_POST['encargado']);

    $stmt = $conexion->prepare("UPDATE sucursales SET nombre = ?, direccion = ?, telefono = ?, encargado = ? WHERE id = ?");
    $stmt->execute([$nombre, $direccion, $telefono, $encargado, $id]);
    $mensaje = "¡Sucursal actualizada correctamente!";
    $tipo_alerta = "info";
}

// 3. ELIMINAR SUCURSAL
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM sucursales WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = "Sucursal eliminada.";
    $tipo_alerta = "warning";
}

// 4. CONSULTA AVANZADA: Trae sucursales + total de equipos + total de equipos dañados
$sql = "SELECT s.*, 
        COUNT(e.id) as total_equipos,
        SUM(CASE WHEN e.estado = 'En Falla' THEN 1 ELSE 0 END) as equipos_fallando
        FROM sucursales s
        LEFT JOIN equipos e ON s.id = e.sucursal_id
        GROUP BY s.id
        ORDER BY s.id DESC";
$sucursales = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fa-solid fa-building text-primary me-2"></i> Gestión de Sucursales</h2>
        <small class="text-muted">Control de sedes, puestos de trabajo y estado de su infraestructura</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSucursal">
        <i class="fa-solid fa-plus me-1"></i> Nueva Sucursal
    </button>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabla de Sucursales -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Sucursal / Puesto</th>
                        <th>Dirección</th>
                        <th>Encargado</th>
                        <th class="text-center">Total Equipos</th>
                        <th class="text-center">Estado Crítico</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sucursales) > 0): ?>
                        <?php foreach ($sucursales as $s): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold fs-6 text-dark"><?php echo htmlspecialchars($s['nombre']); ?></span><br>
                                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($s['telefono'] ?: 'Sin teléfono'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($s['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($s['encargado'] ?: 'No asignado'); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary fs-6"><?php echo $s['total_equipos']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['equipos_fallando'] > 0): ?>
                                        <span class="badge bg-danger fs-6"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $s['equipos_fallando']; ?> con falla</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Todo Operativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- Botón para ver la Ficha completa de la Sucursal -->
                                    <a href="ver_sucursal.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-info text-white" title="Ver detalle completo">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </a>
                                    <!-- Botón para editar -->
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar<?php echo $s['id']; ?>" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <!-- Botón para eliminar -->
                                    <a href="sucursales.php?eliminar=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta sucursal? Se borrarán sus equipos e historial.');" title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- MODAL DE EDICIÓN PARA CADA SUCURSAL -->
                            <div class="modal fade" id="modalEditar<?php echo $s['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="sucursales.php" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar Sucursal: <?php echo htmlspecialchars($s['nombre']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre</label>
                                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($s['nombre']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Dirección</label>
                                                    <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($s['direccion']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Teléfono</label>
                                                    <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($s['telefono']); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Encargado</label>
                                                    <input type="text" name="encargado" class="form-control" value="<?php echo htmlspecialchars($s['encargado']); ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="editar_sucursal" class="btn btn-warning">Actualizar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No hay sucursales registradas aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR SUCURSAL -->
<div class="modal fade" id="modalSucursal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="sucursales.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-plus me-1"></i> Registrar Nueva Sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Sucursal  *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: sala 03110" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección *</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Ej: Calle 40 Sur #12-34" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: 3001234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persona Encargada</label>
                        <input type="text" name="encargado" class="form-control" placeholder="Ej: Dr. Roberto Gómez">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_sucursal" class="btn btn-primary">Guardar Sucursal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>