<?php
session_start();
include 'db.php';

// Redirigir a ventas.php
if (isset($_POST['go_to_sales'])) {
    header("Location: ventas.php");
    exit();
}

// Cerrar sesión
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php"); // Redirige a la página de inicio de sesión o a la página de inicio.
    exit();
}

// Eliminar producto
if (isset($_POST['delete_product'])) {
    $id = $_POST['product_id'];
    $sql_delete = "DELETE FROM products WHERE id=$id";
    if ($conn->query($sql_delete) === TRUE) {
        echo "Producto eliminado exitosamente.";
        // Reconstruir IDs de productos
        $sql_reconstruct_ids = "SET @num := 0; UPDATE products SET id = @num := (@num+1); ALTER TABLE products AUTO_INCREMENT = 1;";
        if ($conn->multi_query($sql_reconstruct_ids) === TRUE) {
            echo "IDs de productos reconstruidos correctamente.";
            // Procesar los resultados de multi_query
            do {
                if ($res = $conn->store_result()) {
                    $res->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            echo "Error al reconstruir los IDs de productos: " . $conn->error;
        }
    } else {
        echo "Error al eliminar el producto: " . $conn->error;
    }
}

// Agregar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    $sql = "INSERT INTO products (name, descripcion, precio, stock, category) VALUES ('$name', '$description', '$price', '$stock', '$category')";
    if ($conn->query($sql) === TRUE) {
        echo "Producto agregado exitosamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Agregar categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $sql = "INSERT INTO categorias (name) VALUES ('$category_name')";
    if ($conn->query($sql) === TRUE) {
        echo "Categoría agregada exitosamente.";
        if (!isset($_SESSION['user_categories'])) {
            $_SESSION['user_categories'] = array();
        }
        $_SESSION['user_categories'][] = $category_name;
    } else {
        echo "Error al agregar la categoría: " . $conn->error;
    }
}

// Eliminar categoría
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $sql_get_category_name = "SELECT name FROM categorias WHERE id = '$category_id'";
    $result_category_name = $conn->query($sql_get_category_name);
    if ($result_category_name->num_rows > 0) {
        $row = $result_category_name->fetch_assoc();
        $category_name = $row['name'];
        $sql_delete = "DELETE FROM categorias WHERE id= '$category_id'";
        if ($conn->query($sql_delete) === TRUE) {
            echo "Categoría eliminada exitosamente.";
            if (isset($_SESSION['user_categories']) && in_array($category_name, $_SESSION['user_categories'])) {
                $key = array_search($category_name, $_SESSION['user_categories']);
                unset($_SESSION['user_categories'][$key]);
            }
        } else {
            echo "Error al eliminar la categoría: " . $conn->error;
        }
    }
}

// Obtener productos
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Obtener categorías
$sql_categories = "SELECT * FROM categorias";
$result_categories = $conn->query($sql_categories);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Productos</title>
    <link href="products_css.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="general">
        <div class="divicion">
            <h1>Gestión de Productos</h1>

            <!-- Botones para ir a ventas.php y cerrar sesión -->
            <form method="post" action="productos.php" style="display:inline;">
                <button type="submit" name="go_to_sales">Ir a Ventas</button>
            </form>
            <form method="post" action="productos.php" style="display:inline;">
                <button type="submit" name="logout">Cerrar Sesión</button>
            </form>
            
            <!-- Formulario para agregar categoría -->
            <h2>Agregar Categoría</h2>
            <form method="post" action="productos.php">
                <label>Nombre de la categoría:</label>
                <input type="text" name="category_name" required><br>
                <input type="submit" name="add_category" value="Agregar Categoría">
            </form>

            <!-- Formulario para eliminar categoría -->
            <h2>Eliminar Categoría</h2>
            <form method="post" action="productos.php">
                <label>Categoría a eliminar:</label>
                <select name="category_id">
                    <?php
                    if ($result_categories->num_rows > 0) {
                        $result_categories->data_seek(0);
                        while($row = $result_categories->fetch_assoc()) {
                            echo "<option value='" . $row["id"] . "'>" . $row["name"] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay categorías</option>";
                    }
                    ?>
                </select><br>
                <input type="submit" name="delete_category" value="Eliminar Categoría">
            </form>

            <!-- Formulario para agregar producto -->
            <h2>Agregar Producto</h2>
            <form method="post" action="productos.php">
                <label>Nombre:</label>
                <input type="text" name="name" required><br>
                <label>Descripción:</label>
                <input type="text" name="description" required><br>
                <label>Precio:</label>
                <input type="text" name="price" required><br>
                <label>Stock:</label>
                <input type="number" name="stock" required><br>
                <label>Categoría:</label>
                <select name="category">
                <?php
                $result_categories = $conn->query($sql_categories);
                if ($result_categories->num_rows > 0) {
                    while($row = $result_categories->fetch_assoc()) {
                        echo "<option value='" . $row["name"] . "'>" . $row["name"] . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay categorías</option>";
                }
                ?>
            </select><br>
                <input type="submit" name="add_product" value="Agregar Producto">
            </form>
        </div>
        <div class="divicion2">
            <!-- Listado de Productos por Categoría -->
            <h2>Listado de Productos</h2>
            <?php
            if ($result_categories->num_rows > 0) {
                $result_categories->data_seek(0);
                while($category = $result_categories->fetch_assoc()) {
                    echo "<h3>" . $category["name"] . "</h3>";
                    echo "<table border='1'>";
                    echo "<tr>";
                    echo "<th>ID</th>";
                    echo "<th>Nombre</th>";
                    echo "<th>Descripción</th>";
                    echo "<th>Precio</th>";
                    echo "<th>Stock</th>";
                    echo "<th>Acciones</th>";
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
                                <form method='post' action='productos.php'>
                                    <input type='hidden' name='product_id' value='" . $row["id"] . "'>
                                    <input type='submit' name='delete_product' value='Borrar' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este producto?\");'>
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
    </div>
</body>
</html>

