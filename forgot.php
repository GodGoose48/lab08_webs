<?php
require_once 'db.php';
require_once 'mail_helper.php';

$error = '';
$email = '';
$success = false;

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    if (empty($email)) {
        $error = 'Please enter your email';
    }
    else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
        $error = 'This is not a valid email address';
    }
    else {
       
        $stmt = $conn->prepare("SELECT * FROM account WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            
            $token = bin2hex(random_bytes(16));
            $expire_on = time() + 3600;
            $check_stmt = $conn->prepare("SELECT * FROM reset_token WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->rowCount() > 0) {
                $update_stmt = $conn->prepare("UPDATE reset_token SET token = ?, expire_on = ? WHERE email = ?");
                $update_stmt->execute([$token, $expire_on, $email]);
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO reset_token (email, token, expire_on) VALUES (?, ?, ?)");
                $insert_stmt->execute([$email, $token, $expire_on]);
            }
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?email=$email&token=$token";
            $subject = "Password Reset";
            $body = "
            <h2>Password Reset</h2>
            <p>You have requested to reset your password. Please click the link below to reset your password:</p>
            <p><a href='$reset_link'>Reset Password</a></p>
            <p>If the link doesn't work, copy and paste this URL into your browser:</p>
            <p>$reset_link</p>
            <p>This link will expire in 1 hour.</p>";
            
            $mail_result = sendMail($email, $subject, $body);
            
            if ($mail_result === true) {
                $success = true;
            } else {
                $error = "Couldn't send reset email: $mail_result";
            }
        } else {
            $success = true;
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
                <p>If your email exists in the database, you will receive an email containing the reset password instructions.</p>
                <p>Please check your email and follow the instructions to reset your password.</p>
                <p><a href="login.php">Back to login</a></p>
            </div>
            <?php else: ?>
            <form method="post" action="" class="border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input name="email" id="email" type="text" class="form-control" placeholder="Email address" value="<?= $email ?>">
                </div>
                <div class="form-group">
                    <p>If your email exists in the database, you will receive an email containing the reset password instructions.</p>
                </div>
                <div class="form-group">
                    <?php
                        if (!empty($error)) {
                            echo "<div class='alert alert-danger'>$error</div>";
                        }
                    ?>
                    <button class="btn btn-success px-5">Reset password</button>
                </div>
                <div class="form-group">
                    <p><a href="login.php">Back to login</a></p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>