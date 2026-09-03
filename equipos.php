<?php
require_once "includes/conexion.php";

$mensaje = "";
$tipo_alerta = "";

// 1. REGISTRAR EQUIPO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_equipo'])) {
    $sucursal_id = $_POST['sucursal_id'];
    $nombre      = trim($_POST['nombre']);
    $marca       = trim($_POST['marca']);
    $modelo      = trim($_POST['modelo']);
    $serie       = trim($_POST['numero_serie']);
    $estado      = $_POST['estado'];

    if (!empty($sucursal_id) && !empty($nombre)) {
        $stmt = $conexion->prepare("INSERT INTO equipos (sucursal_id, nombre, marca, modelo, numero_serie, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sucursal_id, $nombre, $marca, $modelo, $serie, $estado]);
        $mensaje = "¡Equipo registrado exitosamente!";
        $tipo_alerta = "success";
    }
}

// 2. CAMBIAR ESTADO RÁPIDO DE UN EQUIPO (ej: marcar como En Falla)
if (isset($_GET['cambiar_estado']) && isset($_GET['id'])) {
    $nuevo_estado = $_GET['cambiar_estado'];
    $id_equipo    = $_GET['id'];
    $stmt = $conexion->prepare("UPDATE equipos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id_equipo]);
    $mensaje = "Estado del equipo actualizado a: " . $nuevo_estado;
    $tipo_alerta = "info";
}

// 3. ELIMINAR EQUIPO
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    $stmt = $conexion->prepare("DELETE FROM equipos WHERE id = ?");
    $stmt->execute([$id_eliminar]);
    $mensaje = "Equipo eliminado correctamente.";
    $tipo_alerta = "warning";
}

// 4. CONSULTAR EQUIPOS CON FILTRO
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';

$sql = "SELECT e.*, s.nombre as sucursal_nombre 
        FROM equipos e 
        JOIN sucursales s ON e.sucursal_id = s.id ";

if (!empty($filtro_estado)) {
    $sql .= " WHERE e.estado = :estado ";
}
$sql .= " ORDER BY e.id DESC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_estado)) {
    $stmt->execute([':estado' => $filtro_estado]);
} else {
    $stmt->execute();
}
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar lista de sucursales para el select
$sucursales = $conexion->query("SELECT id, nombre FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fa-solid fa-laptop text-primary me-2"></i> Gestión de Equipos</h2>
        <small class="text-muted">Control de PCs, servidores, maquinaria y reporte de fallas</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEquipo">
        <i class="fa-solid fa-plus me-1"></i> Registrar Nuevo Equipo
    </button>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Barra de Filtros Rápidos -->
<div class="card mb-4 bg-white border-0 shadow-sm">
    <div class="card-body d-flex gap-2 align-items-center flex-wrap">
        <span class="fw-bold me-2"><i class="fa-solid fa-filter me-1"></i>Filtrar por Estado:</span>
        <a href="equipos.php" class="btn btn-sm <?php echo empty($filtro_estado) ? 'btn-dark' : 'btn-outline-dark'; ?>">Todos</a>
        <a href="equipos.php?filtro_estado=En Falla" class="btn btn-sm <?php echo $filtro_estado == 'En Falla' ? 'btn-danger' : 'btn-outline-danger'; ?>">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Solo Dañados / En Falla
        </a>
        <a href="equipos.php?filtro_estado=En Mantenimiento" class="btn btn-sm <?php echo $filtro_estado == 'En Mantenimiento' ? 'btn-warning' : 'btn-outline-warning'; ?>">En Mantenimiento</a>
        <a href="equipos.php?filtro_estado=Operativo" class="btn btn-sm <?php echo $filtro_estado == 'Operativo' ? 'btn-success' : 'btn-outline-success'; ?>">Operativos</a>
    </div>
</div>

<!-- Tabla de Equipos -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Equipo</th>
                        <th>Sucursal / Ubicación</th>
                        <th>Marca / Modelo</th>
                        <th>Serial</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($equipos) > 0): ?>
                        <?php foreach ($equipos as $eq): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($eq['nombre']); ?></td>
                                <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($eq['sucursal_nombre']); ?></span></td>
                                <td><?php echo htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']); ?></td>
                                <td><code><?php echo htmlspecialchars($eq['numero_serie'] ?: 'S/N'); ?></code></td>
                                <td>
                                    <?php if ($eq['estado'] == 'Operativo'): ?>
                                        <span class="badge bg-success p-2"><i class="fa-solid fa-check me-1"></i>Operativo</span>
                                    <?php elseif ($eq['estado'] == 'En Falla'): ?>
                                        <span class="badge bg-danger p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>En Falla (Dañado)</span>
                                    <?php elseif ($eq['estado'] == 'En Mantenimiento'): ?>
                                        <span class="badge bg-warning text-dark p-2"><i class="fa-solid fa-screwdriver-wrench me-1"></i>En Mantenimiento</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary p-2">De Baja</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Cambiar Estado
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item text-success" href="equipos.php?id=<?php echo $eq['id']; ?>&cambiar_estado=Operativo"><i class="fa-solid fa-check me-2"></i>Operativo</a></li>
                                            <li><a class="dropdown-item text-danger" href="equipos.php?id=<?php echo $eq['id']; ?>&cambiar_estado=En Falla"><i class="fa-solid fa-triangle-exclamation me-2"></i>Marcar como En Falla</a></li>
                                            <li><a class="dropdown-item text-warning" href="equipos.php?id=<?php echo $eq['id']; ?>&cambiar_estado=En Mantenimiento"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Poner En Mantenimiento</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-muted" href="equipos.php?id=<?php echo $eq['id']; ?>&cambiar_estado=De Baja"><i class="fa-solid fa-ban me-2"></i>Dar De Baja</a></li>
                                        </ul>
                                    </div>
                                    <a href="equipos.php?eliminar=<?php echo $eq['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar este equipo?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No se encontraron equipos bajo este filtro.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR EQUIPO -->
<div class="modal fade" id="modalEquipo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="equipos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-laptop me-1"></i> Registrar Equipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">¿En qué Sucursal está instalado? *</label>
                        <select name="sucursal_id" class="form-select" required>
                            <option value="">Selecciona una sucursal...</option>
                            <?php foreach ($sucursales as $suc): ?>
                                <option value="<?php echo $suc['id']; ?>"><?php echo htmlspecialchars($suc['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Equipo / Descripción *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Computador Recepción, Servidor, Impresora..." required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" class="form-control" placeholder="Ej: HP, Dell, Lenovo">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" class="form-control" placeholder="Ej: ProDesk 400">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de Serie</label>
                        <input type="text" name="numero_serie" class="form-control" placeholder="Ej: SN-4958302">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado Inicial</label>
                        <select name="estado" class="form-select">
                            <option value="Operativo">Operativo (Funcionando)</option>
                            <option value="En Falla">En Falla (Dañado / Con problemas)</option>
                            <option value="En Mantenimiento">En Mantenimiento</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_equipo" class="btn btn-primary">Guardar Equipo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>