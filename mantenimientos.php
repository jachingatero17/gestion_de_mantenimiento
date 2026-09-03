<?php
require_once "includes/conexion.php";

$mensaje = "";
$tipo_alerta = "";

// PROCESAR EL REGISTRO DE MANTENIMIENTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_mantenimiento'])) {
    $sucursal_id         = $_POST['sucursal_id'];
    $equipo_id           = $_POST['equipo_id'];
    $fecha               = $_POST['fecha'];
    $tecnico_encargado   = trim($_POST['tecnico_encargado']);
    $recibido_por        = trim($_POST['recibido_por']);
    $tipo_mantenimiento  = $_POST['tipo_mantenimiento'];
    $descripcion_trabajo = trim($_POST['descripcion_trabajo']);
    $observaciones       = trim($_POST['observaciones']);
    $nuevo_estado_equipo = $_POST['nuevo_estado_equipo'];

    // Materiales opcionales
    $material_id         = !empty($_POST['material_id']) ? $_POST['material_id'] : null;
    $cantidad_usada      = !empty($_POST['cantidad_usada']) ? intval($_POST['cantidad_usada']) : 0;

    try {
        // INICIAR TRANSACCIÓN (Seguridad: Si algo falla, no se descuenta nada)
        $conexion->beginTransaction();

        // 1. Insertar el Mantenimiento
        $sql_mant = "INSERT INTO mantenimientos (sucursal_id, equipo_id, fecha, tecnico_encargado, recibido_por, tipo_mantenimiento, descripcion_trabajo, observaciones) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_mant = $conexion->prepare($sql_mant);
        $stmt_mant->execute([$sucursal_id, $equipo_id, $fecha, $tecnico_encargado, $recibido_por, $tipo_mantenimiento, $descripcion_trabajo, $observaciones]);
        $mantenimiento_id = $conexion->lastInsertId();

        // 2. Actualizar el estado del equipo (ej: dejarlo Operativo)
        $stmt_eq = $conexion->prepare("UPDATE equipos SET estado = ? WHERE id = ?");
        $stmt_eq->execute([$nuevo_estado_equipo, $equipo_id]);

        // 3. Procesar consumo de material (si se seleccionó uno)
        if ($material_id && $cantidad_usada > 0) {
            // Validar stock disponible
            $stmt_check = $conexion->prepare("SELECT cantidad FROM inventario WHERE id = ?");
            $stmt_check->execute([$material_id]);
            $stock_actual = $stmt_check->fetchColumn();

            if ($stock_actual < $cantidad_usada) {
                throw new Exception("No hay suficiente stock en inventario para este material.");
            }

            // Descontar del inventario
            $stmt_desc = $conexion->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?");
            $stmt_desc->execute([$cantidad_usada, $material_id]);

            // Registrar en la tabla intermedia
            $stmt_mat = $conexion->prepare("INSERT INTO mantenimiento_materiales (mantenimiento_id, inventario_id, cantidad_usada) VALUES (?, ?, ?)");
            $stmt_mat->execute([$mantenimiento_id, $material_id, $cantidad_usada]);
        }

        // 4. Procesar Evidencia Fotográfica
        if (isset($_FILES['foto_evidencia']) && $_FILES['foto_evidencia']['error'] == 0) {
            $extension = strtolower(pathinfo($_FILES['foto_evidencia']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extension, $permitidas)) {
                $nombre_foto = "evidencia_" . time() . "_" . uniqid() . "." . $extension;
                $destino = "uploads/" . $nombre_foto;

                if (move_uploaded_file($_FILES['foto_evidencia']['tmp_name'], $destino)) {
                    $stmt_foto = $conexion->prepare("INSERT INTO evidencias (mantenimiento_id, ruta_foto, descripcion) VALUES (?, ?, ?)");
                    $stmt_foto->execute([$mantenimiento_id, $destino, "Evidencia de mantenimiento realizado"]);
                }
            }
        }

        // Confirmar todos los cambios
        $conexion->commit();
        $mensaje = "¡Mantenimiento registrado con éxito y stock actualizado!";
        $tipo_alerta = "success";

    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje = "Error al procesar: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}

// Obtener datos para los menús desplegables
$sucursales = $conexion->query("SELECT * FROM sucursales ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$equipos    = $conexion->query("SELECT e.id, e.nombre, e.marca, s.nombre as sucursal FROM equipos e JOIN sucursales s ON e.sucursal_id = s.id ORDER BY s.nombre, e.nombre")->fetchAll(PDO::FETCH_ASSOC);
$materiales = $conexion->query("SELECT i.id, i.nombre_material, i.cantidad, i.unidad_medida, s.nombre as sucursal FROM inventario i JOIN sucursales s ON i.sucursal_id = s.id WHERE i.cantidad > 0 ORDER BY s.nombre, i.nombre_material")->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="row justify-content-center mb-5">
    <div class="col-12 col-lg-10">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i> Registrar Mantenimiento</h2>
            <a href="ver_historial.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-clock-rotate-left me-1"></i> Ver Historial</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="mantenimientos.php" method="POST" enctype="multipart/form-data">
                    
                    <!-- SECCIÓN 1: LUGAR Y EQUIPO -->
                    <h5 class="text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-location-dot me-1"></i> 1. Lugar y Equipo</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sucursal / Puesto *</label>
                            <select name="sucursal_id" class="form-select" required>
                                <option value="">Seleccione la sucursal...</option>
                                <?php foreach ($sucursales as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Equipo a Intervenir *</label>
                            <select name="equipo_id" class="form-select" required>
                                <option value="">Seleccione el equipo...</option>
                                <?php foreach ($equipos as $eq): ?>
                                    <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['sucursal'] . " - " . $eq['nombre'] . " (" . $eq['marca'] . ")"); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: DATOS DEL TRABAJO -->
                    <h5 class="text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-clipboard-list me-1"></i> 2. Detalles del Trabajo</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha del Mantenimiento *</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Técnico Encargado *</label>
                            <input type="text" name="tecnico_encargado" class="form-control" placeholder="Nombre del técnico" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Persona que Recibió *</label>
                            <input type="text" name="recibido_por" class="form-control" placeholder="Nombre de quien recibe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Mantenimiento *</label>
                            <select name="tipo_mantenimiento" class="form-select" required>
                                <option value="Preventivo">Preventivo (Limpieza, revisión rutinaria)</option>
                                <option value="Correctivo">Correctivo (Reparación de fallas o cambio de piezas)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estado final del Equipo *</label>
                            <select name="nuevo_estado_equipo" class="form-select" required>
                                <option value="Operativo">Operativo (Solucionado / En perfecto estado)</option>
                                <option value="En Mantenimiento">Sigue En Mantenimiento</option>
                                <option value="En Falla">Sigue En Falla (Requiere otro repuesto)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Trabajo Realizado (Descripción detallada) *</label>
                            <textarea name="descripcion_trabajo" class="form-control" rows="3" placeholder="Describe qué se hizo: cambio de pasta térmica, formateo, cambio de fuente, etc." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Recomendaciones para el usuario o notas para el próximo mantenimiento..."></textarea>
                        </div>
                    </div>

                    <!-- SECCIÓN 3: CONSUMO DE INVENTARIO Y EVIDENCIAS -->
                    <h5 class="text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-boxes-stacked me-1"></i> 3. Materiales Utilizados y Evidencia</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Material / Repuesto Utilizado (Opcional)</label>
                            <select name="material_id" class="form-select">
                                <option value="">-- Ningún material utilizado --</option>
                                <?php foreach ($materiales as $mat): ?>
                                    <option value="<?php echo $mat['id']; ?>"><?php echo htmlspecialchars($mat['sucursal'] . " - " . $mat['nombre_material'] . " (Disp: " . $mat['cantidad'] . " " . $mat['unidad_medida'] . ")"); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Si seleccionas un material, se descontará automáticamente del stock.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cantidad Usada</label>
                            <input type="number" name="cantidad_usada" class="form-control" min="1" value="1">
                        </div>

                        <!-- Cargar Fotografía -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><i class="fa-solid fa-camera me-1"></i> Evidencia Fotográfica (Foto del trabajo)</label>
                            <input type="file" name="foto_evidencia" class="form-control" accept="image/*">
                            <small class="text-muted">Formatos admitidos: JPG, PNG, WEBP.</small>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="guardar_mantenimiento" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Mantenimiento
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once "includes/footer.php"; ?>