<?php
session_start();

// Include functions file
include 'functions.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "test";
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle registration
if (isset($_POST['register'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO Users (firstname, lastname, email, username, password, score) VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $username, $password);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Η εγγραφή ολοκληρώθηκε με επιτυχία!</p>";
    } else {
        echo "<p style='color:red;'>Σφάλμα κατά την εγγραφή: " . $stmt->error . "</p>";
    }
}

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, password FROM Users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            echo "<p style='color:green;'>Συνδεθήκατε με επιτυχία!</p>";
        } else {
            echo "<p style='color:red;'>Λάθος κωδικός πρόσβασης.</p>";
        }
    } else {
        echo "<p style='color:red;'>Το όνομα χρήστη δεν υπάρχει.</p>";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Initialize board if logged in and no board is set
if (isset($_SESSION['user_id']) && !isset($_SESSION['board'])) {
    $_SESSION['board'] = array_fill(0, 20, array_fill(0, 20, '-'));
}

// Handle piece placement and rotation
if (isset($_POST['place_piece'])) {
    $pieceType = $_POST['piece'];  // Piece selected (L, I, O)
    $x = $_POST['x'];  // X coordinate
    $y = $_POST['y'];  // Y coordinate
    $rotate = isset($_POST['rotate']) ? true : false;  // Whether to rotate the piece

    // Define pieces as 2D arrays (example for L, I, O)
    $pieces = [
        'L' => [['-', '-', 'L'], ['L', 'L', 'L']],
        'I' => [['I'], ['I'], ['I'], ['I']],
        'O' => [['O', 'O'], ['O', 'O']]
    ];

    // Get the piece matrix
    $piece = $pieces[$pieceType];

    // Rotate the piece if needed
    if ($rotate) {
        $piece = rotatePiece($piece);
    }

    // Place the piece on the board
    placePiece($piece, $x, $y);

    // Optionally, update the database with the new position
    updatePieceInDb($conn, $_SESSION['user_id'], $x, $y, $pieceType);

    // Redirect to reload the page and display updated board
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Blokus Game</title>
</head>
<body>
    <h1>Blokus - Παίξε το παιχνίδι!</h1>
    <?php
    if (isset($_SESSION['user_id'])) {
        displayBoard();
    }
    ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <h2>Τοποθέτησε Κομμάτι</h2>
        <form method="POST">
            <label for="piece">Κομμάτι:</label>
            <select name="piece" id="piece" required>
                <option value="L">L</option>
                <option value="I">I</option>
                <option value="O">O</option>
            </select><br>
            <label for="x">Συντεταγμένη X:</label>
            <input type="number" name="x" id="x" min="0" max="19" required><br>
            <label for="y">Συντεταγμένη Y:</label>
            <input type="number" name="y" id="y" min="0" max="19" required><br>
            <label for="rotate">Γύρισε το κομμάτι 90°:</label>
            <input type="checkbox" name="rotate" id="rotate"><br>
            <button type="submit" name="place_piece">Τοποθέτησε Κομμάτι</button>
        </form>

        <h2>Αποσύνδεση</h2>
        <form method="POST">
            <button type="submit" name="logout">Αποσύνδεση</button>
        </form>
    <?php else: ?>

        <h2>Εγγραφή</h2>
        <form method="POST">
            <input type="text" name="firstname" placeholder="Όνομα" required><br>
            <input type="text" name="lastname" placeholder="Επώνυμο" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="text" name="username" placeholder="Όνομα χρήστη" required><br>
            <input type="password" name="password" placeholder="Κωδικός" required><br>
            <button type="submit" name="register">Εγγραφή</button>
        </form>

        <h2>Σύνδεση</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Όνομα χρήστη" required><br>
            <input type="password" name="password" placeholder="Κωδικός" required><br>
            <button type="submit" name="login">Σύνδεση</button>
        </form>

    <?php endif; ?>

</body>
</html>