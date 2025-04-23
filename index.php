<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit();
    }

    require_once 'db.php';

    // Handle product deletion
    if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        
        // First, get the image filename to delete it
        $stmt = $conn->prepare("SELECT image FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && $product['image']) {
            $image_path = 'images/' . $product['image'];
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the image file
            }
        }
        
        // Delete the product record
        $stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
        $result = $stmt->execute([$product_id]);
        
        if ($result) {
            // Redirect to refresh the page
            header('Location: index.php?deleted=success');
            exit();
        }
    }

    // Handle product update
    if (isset($_POST['update_product']) && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $desc = $_POST['desc'];
        
        // Validate input
        if (empty($name)) {
            $error = 'Please enter product name';
        }
        else if (intval($price) <= 0) {
            $error = 'Invalid product price';
        }
        else if (intval($price) < 1000000 || intval($price) % 10000 != 0) {
            $error = 'Price must be over 1,000,000 VND and a multiple of 10,000 VND';
        }
        else if (empty($desc)) {
            $error = 'Please enter product description';
        }
        else {
            // Update product
            $stmt = $conn->prepare("UPDATE product SET name = ?, price = ?, description = ? WHERE id = ?");
            $result = $stmt->execute([$name, $price, $desc, $product_id]);
            
            if ($result) {
                // Redirect to refresh the page
                header('Location: index.php?updated=success');
                exit();
            }
        }
    }

    // Fetch all products
    $stmt = $conn->prepare("SELECT * FROM product ORDER BY id DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_products = count($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" integrity="sha512-YWzhKL2whUzgiheMoBFwW8CKV4qpHQAEuvilg9FAn5VJUDwKZZxkJNuGM4XkWuk94WCrrwslk8yWNGmY1EduTA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        td {
            vertical-align: middle;
        }
        img {
            max-height: 100px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col col-md-10">
            <h3 class="my-4 text-center">Product List</h3>
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Product deleted successfully.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Product updated successfully.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
                <a class="btn btn-sm btn-success mb-4" href="add_product.php">Add Product</a>
                <div>
                    <span>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="logout.php" class="ml-2">Logout</a>
                </div>
            </div>
            <table class="table-bordered table table-hover text-center">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_products > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr data-id="<?= $product['id'] ?>">
                                <td class="align-middle">
                                    <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($product['name']) ?></td>
                                <td class="align-middle">
                                    <?= number_format($product['price'], 0, ',', ',') ?> VND
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($product['description']) ?></td>
                                <td class="align-middle">
                                    <button class="btn btn-sm btn-primary mr-1 edit-btn" 
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>"
                                            data-price="<?= $product['price'] ?>"
                                            data-desc="<?= htmlspecialchars($product['description']) ?>">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn"
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No products found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="text-right">Total products: <strong><?= $total_products ?></strong></p>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete-product-name"></strong>?</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="product_id" id="delete-product-id">
                    <input type="hidden" name="delete_product" value="1">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update product: <span id="edit-product-title"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="product_id" id="edit-product-id">
                    <input type="hidden" name="update_product" value="1">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input name="name" id="edit-name" type="text" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (VND)</label>
                        <input name="price" id="edit-price" type="number" class="form-control" required>
                        <small class="form-text text-muted">Price must be over 1,000,000 VND and a multiple of 10,000 VND</small>
                    </div>
                    <div class="form-group">
                        <label for="desc">Description</label>
                        <textarea name="desc" id="edit-desc" class="form-control" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Show delete confirm modal
        $(".delete-btn").click(function() {
            var productId = $(this).data('id');
            var productName = $(this).data('name');
            
            $('#delete-product-id').val(productId);
            $('#delete-product-name').text(productName);
            
            $('#deleteModal').modal({
                backdrop: 'static',
                keyboard: false
            });
        });

        // Show edit modal
        $(".edit-btn").click(function() {
            var productId = $(this).data('id');
            var productName = $(this).data('name');
            var productPrice = $(this).data('price');
            var productDesc = $(this).data('desc');
            
            $('#edit-product-id').val(productId);
            $('#edit-product-title').text(productName);
            $('#edit-name').val(productName);
            $('#edit-price').val(productPrice);
            $('#edit-desc').val(productDesc);
            
            $('#editModal').modal({
                backdrop: 'static',
                keyboard: false
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $(".alert").alert('close');
        }, 5000);
    });
</script>

</body>
</html>