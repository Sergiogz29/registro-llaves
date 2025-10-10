<?php
require(__DIR__ . '/conexion.php');

echo "<h2>Buscar llaves en la base de datos</h2>";

// Buscar llaves que contengan "04271"
$sql = "SELECT codigo_principal, direccion, tiene_alarma, codigo_alarma 
        FROM llaves 
        WHERE codigo_principal LIKE '%04271%' 
        OR codigo_principal LIKE '%4271%'
        LIMIT 10";

$result = $conexion->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Llaves encontradas que contienen '04271':</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Código Principal</th><th>Dirección</th><th>Tiene Alarma</th><th>Código Alarma</th><th>Acción</th></tr>";
    
    while ($llave = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($llave['codigo_principal']) . "</td>";
        echo "<td>" . htmlspecialchars($llave['direccion']) . "</td>";
        echo "<td>" . ($llave['tiene_alarma'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($llave['codigo_alarma'] ?? 'NULL') . "</td>";
        echo "<td><a href='configurar_alarma.php?codigo=" . urlencode($llave['codigo_principal']) . "&alarma=1234'>Configurar Alarma</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No se encontraron llaves con código que contenga '04271'</p>";
}

// Mostrar algunas llaves de ejemplo
echo "<h3>Primeras 10 llaves en la base de datos:</h3>";
$sql_ejemplo = "SELECT codigo_principal, direccion, tiene_alarma, codigo_alarma 
                FROM llaves 
                LIMIT 10";

$result_ejemplo = $conexion->query($sql_ejemplo);

if ($result_ejemplo && $result_ejemplo->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Código Principal</th><th>Dirección</th><th>Tiene Alarma</th><th>Código Alarma</th><th>Acción</th></tr>";
    
    while ($llave = $result_ejemplo->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($llave['codigo_principal']) . "</td>";
        echo "<td>" . htmlspecialchars($llave['direccion']) . "</td>";
        echo "<td>" . ($llave['tiene_alarma'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($llave['codigo_alarma'] ?? 'NULL') . "</td>";
        echo "<td><a href='configurar_alarma.php?codigo=" . urlencode($llave['codigo_principal']) . "&alarma=1234'>Configurar Alarma</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conexion->close();
?>
