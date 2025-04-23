<?php
require_once 'db.php';

$error = '';
$email = '';
$pass = '';
$pass_confirm = '';
$token = '';
$valid_token = false;
$success = false;

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];
    
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT * FROM reset_token WHERE email = ? AND token = ? AND expire_on > ?");
    $stmt->execute([$email, $token, time()]);
    
    if ($stmt->rowCount() > 0) {
        $valid_token = true;
    }
}

if (isset($_POST['email']) && isset($_POST['pass']) && isset($_POST['pass-confirm']) && isset($_POST['token'])) {
    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $pass_confirm = $_POST['pass-confirm'];
    $token = $_POST['token'];
    
    // Validate token again
    $stmt = $conn->prepare("SELECT * FROM reset_token WHERE email = ? AND token = ? AND expire_on > ?");
    $stmt->execute([$email, $token, time()]);
    
    if ($stmt->rowCount() == 0) {
        $error = 'Invalid or expired reset link';
    }
    else if (empty($pass)) {
        $error = 'Please enter your password';
    }
    else if (strlen($pass) < 6) {
        $error = 'Password must have at least 6 characters';
    }
    else if ($pass != $pass_confirm) {
        $error = 'Password does not match';
    }
    else {
        // Update password
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE account SET password = ? WHERE email = ?");
        $result = $update_stmt->execute([$hashed_password, $email]);
        
        if ($result) {
            // Invalidate token
            $delete_stmt = $conn->prepare("DELETE FROM reset_token WHERE email = ?");
            $delete_stmt->execute([$email]);
            
            $success = true;
        } else {
            $error = "Couldn't update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset user password</title>
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
            <h3 class="text-center text-secondary mt-5 mb-3">Reset Password</h3>
            
            <?php if ($success): ?>
            <div class="alert alert-success border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <p>Your password has been successfully reset.</p>
                <p>You can now <a href="login.php">login</a> with your new password.</p>
            </div>
            <?php elseif (!$valid_token && !isset($_POST['email'])): ?>
            <div class="alert alert-danger border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <p>Invalid or expired reset link.</p>
                <p>Please request a new password reset <a href="forgot.php">here</a>.</p>
            </div>
            <?php else: ?>
            <form novalidate method="post" action="" class="border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input readonly value="<?= $email ?>" name="email" id="email" type="text" class="form-control" placeholder="Email address">
                    <input type="hidden" name="token" value="<?= $token ?>">
                </div>
                <div class="form-group">
                    <label for="pass">Password</label>
                    <input value="<?= $pass ?>" name="pass" required class="form-control" type="password" placeholder="Password" id="pass">
                    <div class="invalid-feedback">Password is not valid.</div>
                </div>
                <div class="form-group">
                    <label for="pass2">Confirm Password</label>
                    <input value="<?= $pass_confirm ?>" name="pass-confirm" required class="form-control" type="password" placeholder="Confirm Password" id="pass2">
                    <div class="invalid-feedback">Password is not valid.</div>
                </div>
                <div class="form-group">
                    <?php
                        if (!empty($error)) {
                            echo "<div class='alert alert-danger'>$error</div>";
                        }
                    ?>
                    <button class="btn btn-success px-5">Change password</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>