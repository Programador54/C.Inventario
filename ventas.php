<?php
session_start();
include 'db.php';

// Procesar venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vender'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Obtener el stock actual del producto
    $sql_get_stock = "SELECT stock FROM products WHERE id = '$product_id'";
    $result_get_stock = $conn->query($sql_get_stock);

    if ($result_get_stock->num_rows > 0) {
        $row = $result_get_stock->fetch_assoc();
        $current_stock = $row['stock'];

        if ($quantity <= $current_stock) {
            $new_stock = $current_stock - $quantity;

            // Actualizar el stock del producto
            $sql_update_stock = "UPDATE products SET stock = '$new_stock' WHERE id = '$product_id'";
            if ($conn->query($sql_update_stock) === TRUE) {
                echo "<div class='alert success'>Venta realizada exitosamente. Stock actualizado.</div>";
            } else {
                echo "<div class='alert error'>Error al actualizar el stock: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert error'>No hay suficiente stock para realizar la venta.</div>";
        }
    } else {
        echo "<div class='alert error'>Producto no encontrado.</div>";
    }
}

// Obtener productos
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Obtener categorías
$sql_categories = "SELECT * FROM categorias";
$result_categories = $conn->query($sql_categories);

// Redirigir a ventas.php
if (isset($_POST['salir'])) {
    header("Location: productos.php");
    exit();
}

// Cerrar sesión
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php"); // Redirige a la página de inicio de sesión o a la página de inicio.
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="ventascss.css" rel="stylesheet" type="text/css">
    <title>Ventas</title>
</head>
<body>
    <div class="container">
        <h1>Ventas</h1>
        <!-- Botones para ir a ventas.php y cerrar sesión -->
        <form method="post" action="productos.php" style="display:inline;">
                <button type="submit" name="salir">Ir a Gestión de productos</button>
            </form>
            <form method="post" action="productos.php" style="display:inline;">
                <button type="submit" name="logout">Cerrar Sesión</button>
            </form>

        <!-- Listado de Productos por Categoría -->
        <h2>Listado de Productos</h2>
        <?php
        if ($result_categories->num_rows > 0) {
            $result_categories->data_seek(0);
            while($category = $result_categories->fetch_assoc()) {
                echo "<h3>" . $category["name"] . "</h3>";
                echo "<table>";
                echo "<tr>";
                echo "<th>ID</th>";
                echo "<th>Nombre</th>";
                echo "<th>Descripción</th>";
                echo "<th>Precio</th>";
                echo "<th>Stock</th>";
                echo "<th>Acción</th>";
                echo "</tr>";

                $category_name = $category["name"];
                $sql_products = "SELECT * FROM products WHERE category='$category_name'";
                $result_products = $conn->query($sql_products);

                if ($result_products->num_rows > 0) {
                    while($row = $result_products->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["id"]. "</td>";
                        echo "<td>" . $row["name"]. "</td>";
                        echo "<td>" . $row["descripcion"]. "</td>";
                        echo "<td>" . $row["precio"]. "</td>";
                        echo "<td>" . $row["stock"]. "</td>";
                        echo "<td>
                            <form method='post' action='ventas.php'>
                                <input type='hidden' name='product_id' value='" . $row["id"] . "'>
                                <input type='number' name='quantity' min='1' max='" . $row["stock"] . "' required>
                                <input type='submit' name='vender' value='Vender'>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No hay productos en esta categoría</td></tr>";
                }

                echo "</table>";
            }
        } else {
            echo "<p>No hay categorías disponibles.</p>";
        }
        ?>
    </div>
</body>
</html>
