<?php
require(__DIR__ . '/conexion.php');

// Script para configurar alarma en una llave específica
$codigo_principal = $_GET['codigo'] ?? '04271';
$codigo_alarma = $_GET['alarma'] ?? '1234';

echo "<h2>Configurar alarma para la llave</h2>";

// Verificar si la llave existe
$sql = "SELECT codigo_principal, direccion FROM llaves WHERE codigo_principal = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $codigo_principal);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $llave = $result->fetch_assoc();
    echo "<p><strong>Llave encontrada:</strong> " . htmlspecialchars($llave['codigo_principal']) . "</p>";
    echo "<p><strong>Dirección:</strong> " . htmlspecialchars($llave['direccion']) . "</p>";
    
    // Actualizar la llave con alarma
    $sql_update = "UPDATE llaves SET tiene_alarma = 1, codigo_alarma = ? WHERE codigo_principal = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param('ss', $codigo_alarma, $codigo_principal);
    
    if ($stmt_update->execute()) {
        echo "<p style='color: green;'>✅ <strong>Alarma configurada correctamente!</strong></p>";
        echo "<p>Código de alarma: <strong>" . htmlspecialchars($codigo_alarma) . "</strong></p>";
        echo "<p>Ahora puedes generar la etiqueta y debería aparecer el código de alarma.</p>";
        
        // Enlace para generar la etiqueta
        echo "<p><a href='generar_etiqueta.php?codigo_principal=" . urlencode($codigo_principal) . "&id_propietario=1' target='_blank' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔖 Generar Etiqueta</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Error al configurar la alarma: " . $conexion->error . "</p>";
    }
    
    $stmt_update->close();
} else {
    echo "<p style='color: red;'>❌ No se encontró la llave con código: " . htmlspecialchars($codigo_principal) . "</p>";
}

$stmt->close();
$conexion->close();
?>

<h3>Configurar otra llave:</h3>
<form method="GET">
    <p>
        <label>Código de la llave:</label><br>
        <input type="text" name="codigo" value="<?= htmlspecialchars($codigo_principal) ?>" required>
    </p>
    <p>
        <label>Código de alarma:</label><br>
        <input type="text" name="alarma" value="<?= htmlspecialchars($codigo_alarma) ?>" required>
    </p>
    <p>
        <button type="submit">Configurar Alarma</button>
    </p>
</form>
