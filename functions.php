<?php
// Database connection function
function getDbConnection() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "test";
    
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Σφάλμα σύνδεσης με βάση δεδομένων: " . $conn->connect_error);
    }
    return $conn;
}

// Create the board if user is logged in
function initializeBoard() {
    if (isset($_SESSION['user_id']) && !isset($_SESSION['board'])) {
        $_SESSION['board'] = array_fill(0, 20, array_fill(0, 20, '-'));
    }
}

// Handle user registration
function registerUser($conn) {
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
}

// Handle user login
function loginUser($conn) {
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
}

// Handle user logout
function logoutUser() {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

// Display the game board
function displayBoard() {
    echo "<table border='1' style='border-collapse: collapse; text-align: center;'>";
    for ($i = 0; $i < 20; $i++) {
        echo "<tr>";
        for ($j = 0; $j < 20; $j++) {
            $cell = $_SESSION['board'][$i][$j];
            echo "<td style='width: 20px; height: 20px;'>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Function to place the piece on the board
function placePiece($piece, $x, $y) {
    // Get the dimensions of the piece
    $pieceHeight = count($piece);
    $pieceWidth = count($piece[0]);

    // Check if the piece fits within the board dimensions
    if ($x + $pieceWidth > 20 || $y + $pieceHeight > 20) {
        echo "<p style='color:red;'>Το κομμάτι δεν χωράει στο ταμπλό στις καθορισμένες θέσεις.</p>";
        return;
    }

    // Check if the cells are empty
    for ($i = 0; $i < $pieceHeight; $i++) {
        for ($j = 0; $j < $pieceWidth; $j++) {
            if ($piece[$i][$j] != '-' && $_SESSION['board'][$y + $i][$x + $j] != '-') {
                echo "<p style='color:red;'>Η θέση είναι κατειλημμένη. Προσπαθήστε σε άλλη θέση.</p>";
                return;
            }
        }
    }

    // Place the piece on the board
    for ($i = 0; $i < $pieceHeight; $i++) {
        for ($j = 0; $j < $pieceWidth; $j++) {
            if ($piece[$i][$j] != '-') {
                $_SESSION['board'][$y + $i][$x + $j] = $piece[$i][$j];
            }
        }
    }

    echo "<p style='color:green;'>Το κομμάτι τοποθετήθηκε επιτυχώς!</p>";
}

// Function to update piece position in the database
function updatePieceInDb($conn, $user_id, $x, $y, $pieceType) {
    // Prepare the query to insert or update the piece position
    $query = "INSERT INTO GameState (user_id, piece_type, x, y) 
              VALUES (?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE x = ?, y = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    $stmt->bind_param("isiiii", $user_id, $pieceType, $x, $y, $x, $y);

    // Execute the query
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Η βάση δεδομένων ενημερώθηκε με επιτυχία!</p>";
    } else {
        echo "<p style='color:red;'>Σφάλμα κατά την ενημέρωση της βάσης δεδομένων: " . $stmt->error . "</p>";
    }
}

function rotatePiece($piece) {
    $rotatedPiece = [];

    // Get the dimensions of the original piece
    $rows = count($piece);
    $cols = count($piece[0]);

    // Rotate the piece by transposing the matrix and reversing rows
    for ($i = 0; $i < $cols; $i++) {
        $rotatedPiece[$i] = [];
        for ($j = $rows - 1; $j >= 0; $j--) {
            $rotatedPiece[$i][$rows - 1 - $j] = $piece[$j][$i];
        }
    }

    return $rotatedPiece;
}
?>
