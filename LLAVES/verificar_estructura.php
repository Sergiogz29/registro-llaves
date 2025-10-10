<?php
require(__DIR__ . '/conexion.php');

echo "<h2>Estructura de la tabla 'llaves'</h2>";

// Verificar estructura de la tabla
$sql = "DESCRIBE llaves";
$result = $conexion->query($sql);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = '';
        if (strpos($row['Field'], 'alarma') !== false) {
            $highlight = 'style="background-color: #90EE90;"';
        }
        
        echo "<tr $highlight>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar específicamente los campos de alarma
    $campos_alarma = ['tiene_alarma', 'codigo_alarma'];
    $campos_existentes = [];
    
    $result->data_seek(0); // Resetear el puntero
    while ($row = $result->fetch_assoc()) {
        $campos_existentes[] = $row['Field'];
    }
    
    echo "<h3>Verificación de campos de alarma:</h3>";
    foreach ($campos_alarma as $campo) {
        if (in_array($campo, $campos_existentes)) {
            echo "<p style='color: green;'>✅ Campo '$campo' existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Campo '$campo' NO existe</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>Error al obtener la estructura de la tabla: " . $conexion->error . "</p>";
}

$conexion->close();
?>
