<?php
session_start();
require_once 'db.php';

// Generate CAPTCHA if not already set
if (empty($_SESSION['captcha_answer'])) {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "$num1 + $num2";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_input = trim($_POST['captcha'] ?? '');

    // Validate CAPTCHA first
    if (!isset($_SESSION['captcha_answer']) || (int)$captcha_input !== (int)$_SESSION['captcha_answer']) {
        $error = "Incorrect CAPTCHA answer.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $hashed_password = $user['password'];
                if (password_verify($password, $hashed_password)) {
                    // Authentication successful
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['name'];

                    // Reset CAPTCHA after successful login
                    unset($_SESSION['captcha_answer'], $_SESSION['captcha_question']);
                    session_write_close();

                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "User not found.";
            }
        } catch (PDOException $e) {
            // Optionally log $e->getMessage()
            $error = "An unexpected error occurred. Please try again later.";
        }
    }

    // Regenerate CAPTCHA after every submission
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_question'] = "$num1 + $num2";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mobile Store</title>
    <link rel="stylesheet" href="css/login-register.css">
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required 
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="input-group">
                <label for="captcha">CAPTCHA: What is <?php echo htmlspecialchars($_SESSION['captcha_question']); ?>?</label>
                <input type="text" name="captcha" id="captcha" required>
            </div>

            <button type="submit" class="btn">Login</button>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
        <p><a href="index.php" class="btn-secondary">Back to index</a></p>
    </div>
</body>
</html>
