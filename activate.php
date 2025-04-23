<?php
require_once 'db.php';

$valid_url = false;

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];
    
    // Check if the email and token are valid
    $stmt = $conn->prepare("SELECT * FROM account WHERE email = ? AND activate_token = ? AND activated = 0");
    $stmt->execute([$email, $token]);
    
    if ($stmt->rowCount() > 0) {
        // Activate the account
        $update_stmt = $conn->prepare("UPDATE account SET activated = 1, activate_token = NULL WHERE email = ?");
        $result = $update_stmt->execute([$email]);
        
        if ($result) {
            $valid_url = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Kích hoạt tài khoản</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link
      rel="stylesheet"
      href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
    />
    <link
      rel="stylesheet"
      href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
      integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU"
      crossorigin="anonymous"
    />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <?php if ($valid_url): ?>
        <div class="col-md-6 mt-5 mx-auto p-3 border rounded">
            <h4>Account Activation</h4>
            <p class="text-success">Congratulations! Your account has been activated.</p>
            <p>Click <a href="login.php">here</a> to login and manage your account information.</p>
            <a class="btn btn-success px-5" href="login.php">Login</a>
        </div>
        <?php else: ?>
        <div class="col-md-6 mt-5 mx-auto p-3 border rounded">
            <h4>Account Activation</h4>
            <p class="text-danger">This is not a valid url or it has been expired.</p>
            <p>Click <a href="login.php">here</a> to login.</p>
            <a class="btn btn-success px-5" href="login.php">Login</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>