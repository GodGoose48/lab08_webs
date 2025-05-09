<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$user = '';
$pass = '';

if (isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if (empty($user)) {
        $error = 'Please enter your username';
    }
    else if (empty($pass)) {
        $error = 'Please enter your password';
    }
    else if (strlen($pass) < 6) {
        $error = 'Password must have at least 6 characters';
    }
    else {
        // Check if user exists and is activated
        $stmt = $conn->prepare("SELECT * FROM account WHERE username = ?");
        $stmt->execute([$user]);
        
        if ($stmt->rowCount() > 0) {
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account is activated
            if (!$account['activated']) {
                $error = 'Account not activated. Please check your email for activation link.';
            } 
            // Verify password
            else if (password_verify($pass, $account['password'])) {
                // Set session
                $_SESSION['user'] = $account['username'];
                $_SESSION['name'] = $account['firstname'] . ' ' . $account['lastname'];
                
                // Remember login if checkbox is checked
                if (isset($_POST['remember'])) {
                    setcookie('user', $account['username'], time() + (120), "/"); // 2 minutes
                }
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Username does not exist';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h3 class="text-center text-secondary mt-5 mb-3">User Login</h3>
            <form method="post" action="" class="border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input value="<?= $user ?>" name="user" id="user" type="text" class="form-control" placeholder="Username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input name="pass" value="<?= $pass ?>" id="password" type="password" class="form-control" placeholder="Password">
                </div>
                <div class="form-group custom-control custom-checkbox">
                    <input <?= isset($_POST['remember']) ? 'checked' : '' ?> name="remember" type="checkbox" class="custom-control-input" id="remember">
                    <label class="custom-control-label" for="remember">Remember login</label>
                </div>
                <div class="form-group">
                    <?php
                        if (!empty($error)) {
                            echo "<div class='alert alert-danger'>$error</div>";
                        }
                    ?>
                    <button class="btn btn-success px-5">Login</button>
                </div>
                <div class="form-group">
                    <p>Don't have an account yet? <a href="register.php">Register now</a>.</p>
                    <p>Forgot your password? <a href="forgot.php">Reset your password</a>.</p>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>