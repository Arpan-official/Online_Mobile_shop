<?php
session_start();
require_once 'db.php';

$error = null;
$success = null;

/**
 * Validate password according to policy.
 * Returns an array of error messages (empty = OK).
 */
function validate_password_policy(string $password): array {
    $errors = [];

    $minLength = 8;   
    $maxLength = 50;

    $len = strlen($password);
    if ($len < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long.";
    }
    if ($len > $maxLength) {
        $errors[] = "Password cannot exceed {$maxLength} characters.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Include at least one lowercase letter.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Include at least one uppercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Include at least one number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Include at least one special character (e.g., !@#$%).";
    }


    // Avoid trivial repeated characters e.g., "aaaaaa" or "111111"
    if (preg_match('/(.)\1\1/', $password)) {
        $errors[] = "Avoid repeating the same character three or more times.";
    }

    // Prevent obvious 'password' substitutions like 'P@ssw0rd' (light check)
    $leets = str_ireplace(['0','@','1','!','3','$','5'], ['o','a','i','i','e','s','s'], $password);
    if (stripos($leets, 'password') !== false) {
        $errors[] = "Avoid obvious words like 'password' even with substitutions.";
    }

    return $errors;
}

/**
 * Validate and sanitize inputs. Returns array: [ sanitized_data_array, errors_array ]
 */
function validate_inputs(array $raw): array {
    $errors = [];
    // sanitize basic inputs
    $name    = trim($raw['name'] ?? '');
    $email   = trim($raw['email'] ?? '');
    $password= $raw['password'] ?? '';
    $address = trim($raw['address'] ?? '');
    $city    = trim($raw['city'] ?? '');
    $phone   = trim($raw['phone'] ?? '');

    // NAME: required, reasonable length, disallow control characters
    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Name is too long.';
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $name)) {
        $errors[] = 'Name contains invalid characters.';
    }

    // EMAIL: required, valid format
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (mb_strlen($email) > 255) {
        $errors[] = 'Email is too long.';
    }

    // PASSWORD: use dedicated validator for complexity & strength
    if ($password === '') {
        $errors[] = 'Password is required.';
    } else {
        $pwErrors = validate_password_policy($password);
        if (!empty($pwErrors)) {
            $errors = array_merge($errors, $pwErrors);
        }
    }

    // ADDRESS: optional, but sanitize and limit length
    if ($address !== '' && mb_strlen($address) > 255) {
        $errors[] = 'Address is too long.';
    }

    // CITY: optional, allow letters, spaces, hyphens; limit length
    if ($city !== '') {
        if (mb_strlen($city) > 100) {
            $errors[] = 'City name is too long.';
        } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $city)) {
            // \p{L} is any unicode letter
            $errors[] = 'City contains invalid characters.';
        }
    }

    // PHONE: optional but if provided validate common phone formats
    if ($phone !== '') {
        // Allow +, digits, spaces, dashes and parentheses, then strip to check digits count
        $clean = preg_replace('/[^\d+]/', '', $phone);
        // count digits only
        $digitsOnly = preg_replace('/\D+/', '', $phone);
        $digitCount = strlen($digitsOnly);

        if ($digitCount < 7 || $digitCount > 15) {
            $errors[] = 'Phone number must contain between 7 and 15 digits.';
        }
        // Basic shape check (starts with +optional then digits/spaces etc.)
        if (!preg_match('/^\+?[0-9\-\s\(\)]+$/', $phone)) {
            $errors[] = 'Phone contains invalid characters.';
        }
        // normalize phone for storage if desired (e.g., store digitsOnly)
        $phone = $digitsOnly;
    }

    // Return sanitized values for further use
    $sanitized = [
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'email' => $email,
        'password' => $password, // raw password still needed to hash/validate
        'address' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
        'city' => htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
        'phone' => $phone
    ];

    return [$sanitized, $errors];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    list($data, $errors) = validate_inputs($_POST);

    if (empty($errors)) {
        try {
            // Check for existing email
            $check = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
            $check->execute([':email' => $data['email']]);
            if ($check->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                // Hash password with secure default algorithm
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO customers (name, email, password, address, city, phone)
                    VALUES (:name, :email, :password, :address, :city, :phone)
                ");
                $stmt->execute([
                    ':name'     => $data['name'],
                    ':email'    => $data['email'],
                    ':password' => $hash,
                    ':address'  => $data['address'],
                    ':city'     => $data['city'],
                    ':phone'    => $data['phone']
                ]);

                // Optionally set a success message or redirect to login
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            // In production log error to file rather than echoing
            // error_log($e->getMessage());
            $errors[] = "An unexpected error occurred. Please try again later.";
        }
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
        // Also preserve the posted fields in variables used in the form
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $address = $data['address'] ?? '';
        $city = $data['city'] ?? '';
        $phone = $data['phone'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mobile Store</title>
    <link rel="stylesheet" href="css/login-register.css">
</head>
<body>
    <div class="form-container">
        <h2>Register</h2>
        <?php if (isset($error) && $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST" novalidate>
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            <div class="input-group">
                <label for="password">Password (min 8 chars):</label>
                <input type="password" name="password" id="password" required>
                <small>Use 10+ characters, with uppercase, lowercase, a number and a symbol.</small>
            </div>
            <div class="input-group">
                <label for="address">Address:</label>
                <input type="text" name="address" id="address" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">
            </div>
            <div class="input-group">
                <label for="city">City:</label>
                <input type="text" name="city" id="city" value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>">
            </div>
            <div class="input-group">
                <label for="phone">Phone:</label>
                <input type="text" name="phone" id="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
        <p><a href="index.php" class="btn-secondary">Back to index</a></p>
    </div>
</body>
</html>
