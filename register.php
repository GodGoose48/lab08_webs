<?php
require_once 'db.php';
require_once 'mail_helper.php';

$error = '';
$first_name = '';
$last_name = '';
$email = '';
$user = '';
$pass = '';
$pass_confirm = '';
$success = false;

if (isset($_POST['first']) && isset($_POST['last']) && isset($_POST['email'])
&& isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['pass-confirm']))
{
    $first_name = $_POST['first'];
    $last_name = $_POST['last'];
    $email = $_POST['email'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $pass_confirm = $_POST['pass-confirm'];

    if (empty($first_name)) {
        $error = 'Please enter your first name';
    }
    else if (empty($last_name)) {
        $error = 'Please enter your last name';
    }
    else if (empty($email)) {
        $error = 'Please enter your email';
    }
    else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
        $error = 'This is not a valid email address';
    }
    else if (empty($user)) {
        $error = 'Please enter your username';
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
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM account WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Email already exists';
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT * FROM account WHERE username = ?");
            $stmt->execute([$user]);
            if ($stmt->rowCount() > 0) {
                $error = 'Username already exists';
            } else {
                // Create activation token
                $activate_token = bin2hex(random_bytes(16));
                
                // Hash password
                $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                
                // Insert new account
                $stmt = $conn->prepare("INSERT INTO account (username, firstname, lastname, email, password, activated, activate_token) VALUES (?, ?, ?, ?, ?, 0, ?)");
                $result = $stmt->execute([$user, $first_name, $last_name, $email, $hashed_password, $activate_token]);
                
                if ($result) {
                    $activation_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/activate.php?email=$email&token=$activate_token";
                    $subject = "Account Activation";
                    $body = "
                    <h2>Account Activation</h2>
                    <p>Hello $first_name $last_name,</p>
                    <p>Thank you for registering. Please click the link below to activate your account:</p>
                    <p><a href='$activation_link'>Activate Account</a></p>
                    <p>If the link doesn't work, copy and paste this URL into your browser:</p>
                    <p>$activation_link</p>";
                    
                    $mail_result = sendMail($email, $subject, $body);
                    
                    if ($mail_result === true) {
                        $success = true;
                    } else {
                        $error = "Registration successful but couldn't send activation email: $mail_result";
                    }
                } else {
                    $error = "Couldn't register account. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Register an account</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <style>
        .bg {
            background: #eceb7b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8 border my-5 p-4 rounded mx-3">
                <h3 class="text-center text-secondary mt-2 mb-3 mb-3">Create a new account</h3>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    Registration successful! Please check your email to activate your account.
                </div>
                <div class="form-group">
                    <p>Already have an account? <a href="login.php">Login</a> now.</p>
                </div>
                <?php else: ?>
                
                <form method="post" action="" novalidate>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="firstname">First name</label>
                            <input value="<?= $first_name?>" name="first" required class="form-control" type="text" placeholder="First name" id="firstname">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="lastname">Last name</label>
                            <input value="<?= $last_name?>" name="last" required class="form-control" type="text" placeholder="Last name" id="lastname">
                            <div class="invalid-tooltip">Last name is required</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input value="<?= $email?>" name="email" required class="form-control" type="email" placeholder="Email" id="email">
                    </div>
                    <div class="form-group">
                        <label for="user">Username</label>
                        <input value="<?= $user?>" name="user" required class="form-control" type="text" placeholder="Username" id="user">
                        <div class="invalid-feedback">Please enter your username</div>
                    </div>
                    <div class="form-group">
                        <label for="pass">Password</label>
                        <input  value="<?= $pass?>" name="pass" required class="form-control" type="password" placeholder="Password" id="pass">
                        <div class="invalid-feedback">Password is not valid.</div>
                    </div>
                    <div class="form-group">
                        <label for="pass2">Confirm Password</label>
                        <input value="<?= $pass_confirm?>" name="pass-confirm" required class="form-control" type="password" placeholder="Confirm Password" id="pass2">
                        <div class="invalid-feedback">Password is not valid.</div>
                    </div>

                    <div class="form-group">
                        <?php
                            if (!empty($error)) {
                                echo "<div class='alert alert-danger'>$error</div>";
                            }
                        ?>
                        <button type="submit" class="btn btn-success px-5 mt-3 mr-2">Register</button>
                        <button type="reset" class="btn btn-outline-success px-5 mt-3">Reset</button>
                    </div>
                    <div class="form-group">
                        <p>Already have an account? <a href="login.php">Login</a> now.</p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>