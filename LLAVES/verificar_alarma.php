<?php
require(__DIR__ . '/conexion.php');

// Verificar si los campos de alarma existen en la tabla llaves
$sql = "SHOW COLUMNS FROM llaves LIKE '%alarma%'";
$result = $conexion->query($sql);

echo "<h2>Verificación de campos de alarma en la base de datos</h2>";

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Los campos de alarma existen:</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . $row['Field'] . "</strong> - Tipo: " . $row['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Los campos de alarma NO existen en la base de datos.</p>";
    echo "<p>Necesitas ejecutar el script de actualización:</p>";
    echo "<pre>";
    echo "ALTER TABLE llaves ADD COLUMN tiene_alarma tinyint(1) NOT NULL DEFAULT 0 AFTER observaciones;\n";
    echo "ALTER TABLE llaves ADD COLUMN codigo_alarma varchar(100) DEFAULT NULL AFTER tiene_alarma;";
    echo "</pre>";
}

// Verificar datos de una llave específica (código 04271)
echo "<h3>Verificación de datos para la llave 04271:</h3>";

$sql = "SELECT codigo_principal, direccion, tiene_alarma, codigo_alarma 
        FROM llaves 
        WHERE codigo_principal = '04271' 
        LIMIT 1";

$result = $conexion->query($sql);

if ($result && $result->num_rows > 0) {
    $llave = $result->fetch_assoc();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>Código Principal</td><td>" . htmlspecialchars($llave['codigo_principal']) . "</td></tr>";
    echo "<tr><td>Dirección</td><td>" . htmlspecialchars($llave['direccion']) . "</td></tr>";
    echo "<tr><td>Tiene Alarma</td><td>" . ($llave['tiene_alarma'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>Código Alarma</td><td>" . htmlspecialchars($llave['codigo_alarma'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    
    if (isset($llave['tiene_alarma']) && $llave['tiene_alarma'] == 1 && !empty($llave['codigo_alarma'])) {
        echo "<p style='color: green;'>✅ Esta llave tiene alarma configurada correctamente.</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Esta llave NO tiene alarma configurada o el código está vacío.</p>";
        echo "<p>Para que aparezca en la etiqueta, necesitas:</p>";
        echo "<ul>";
        echo "<li>Establecer <code>tiene_alarma = 1</code></li>";
        echo "<li>Agregar un valor en <code>codigo_alarma</code> (ej: '1234')</li>";
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ No se encontró la llave con código '04271'</p>";
}

$conexion->close();
?>
