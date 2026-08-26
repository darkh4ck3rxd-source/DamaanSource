<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location:index.php?msg=unauthorized");
    exit;
}

include("conn.php");

// Keep the gift-code table compatible with the optional wager metadata.
$wagerColumn = mysqli_query($conn, "SHOW COLUMNS FROM hodike_nirvahaka LIKE 'wager_amount'");
if ($wagerColumn && mysqli_num_rows($wagerColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE hodike_nirvahaka ADD COLUMN wager_amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER prix");
}

	function generateRandomSerial($length = 32) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}



// Handling form submission to generate codes
// Handling form submission to generate codes
if (isset($_POST['maxserials']) && isset($_POST['maxusers']) && isset($_POST['price']) && isset($_POST['wager_amount'])) {
    $maxserials = max(1, (int)$_POST['maxserials']);
    $maxusers = max(1, (int)$_POST['maxusers']);
    $price = trim((string)$_POST['price']);
    $wagerAmount = trim((string)$_POST['wager_amount']);
    $remark = trim(substr((string)($_POST['remark'] ?? ''), 0, 255));

    if (!is_numeric($price) || (float)$price < 0 || !is_numeric($wagerAmount) || (float)$wagerAmount < 0 || $remark === '') {
        echo '<script>alert("Enter valid price, wager amount and remark.");</script>';
        exit;
    }
    $price = number_format((float)$price, 2, '.', '');
    $wagerAmount = number_format((float)$wagerAmount, 2, '.', '');

    // Check if maxserials is greater than 50
    if ($maxserials > 50) {
        echo '<script>alert("You can only generate a maximum of 50 serial numbers.");</script>';
        exit;  // Stop further execution if the limit is exceeded
    }

    $totalusers = 0;
    $createdate = date("Y-m-d H:i");
    $status = 1;
    $generatedSerials = [];

    // Generate the requested number of random serials
    for ($i = 0; $i < $maxserials; $i++) {
        $newSerial = generateRandomSerial(); // Generate a new random serial
        $generatedSerials[] = $newSerial;

        // Insert the generated serial into the database
        $insertRandomSerial = "INSERT INTO hodike_nirvahaka (enserie, utilisateurmax, prix, wager_amount, nombredutilisateurs, creerunrendezvous, shonu, remark)
                               VALUES ('" . mysqli_real_escape_string($conn, $newSerial) . "', '" . (int)$maxusers . "', '" . mysqli_real_escape_string($conn, $price) . "', '" . mysqli_real_escape_string($conn, $wagerAmount) . "', '" . $totalusers . "', '" . mysqli_real_escape_string($conn, $createdate) . "', '" . $status . "', '" . mysqli_real_escape_string($conn, $remark) . "')";
        mysqli_query($conn, $insertRandomSerial);
    }

    // Convert the generated serials to JSON format to pass to JavaScript
    $generatedSerialsJson = json_encode($generatedSerials);

}

// Pagination Setup
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch history of generated codes from the database
$historyQuery = "SELECT * FROM hodike_nirvahaka ORDER BY creerunrendezvous DESC LIMIT $limit OFFSET $offset";
$historyResult = mysqli_query($conn, $historyQuery);

// Count total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM hodike_nirvahaka";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Handle deletion of serials
if (isset($_POST['delete_serial'])) {
    $serialToDelete = mysqli_real_escape_string($conn, $_POST['delete_serial']);
    $deleteQuery = "DELETE FROM hodike_nirvahaka WHERE enserie = '$serialToDelete'";
    mysqli_query($conn, $deleteQuery);
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh the page after deletion
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Serial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.10/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Generate Gift Codes</h2>
        <form action="#" id="redform" method="post" autocomplete="off">
            <div class="mb-3">
                <input name="maxserials" type="number" placeholder="Enter Number of  number to genrate bulk code " required class="form-control" style="height: 40px;" />
            </div>
            <div class="mb-3">
                <input name="maxusers" type="number" placeholder="Enter Maximum Users" required class="form-control" style="height: 40px;" />
            </div>
            <div class="mb-3">
                <input name="price" type="number" min="0" step="0.01" placeholder="Enter Price" required class="form-control" style="height: 40px;" />
            </div>
            <div class="mb-3">
                <input name="wager_amount" type="number" min="0" step="0.01" value="0" placeholder="Wager amount for this gift code" required class="form-control" style="height: 40px;" />
            </div>
            <div class="mb-3">
                <input name="remark" type="text" maxlength="255" placeholder="Add remark" required class="form-control" style="height: 40px;" />
            </div>
            <button type="submit" class="btn btn-primary">Generate Gift Codes</button>
        </form>
    </div>

    <!-- Custom Modal for Generated Serial Numbers -->
    <div class="modal fade" id="generatedSerialsModal" tabindex="-1" aria-labelledby="generatedSerialsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generatedSerialsModalLabel">Generated Gift Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>The following gift code generated:</p>
                    <ul id="generatedSerialList"></ul>
                    <button id="copyButton" class="btn btn-success mt-3">Copy All to Clipboard</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Display History -->
    <div class="container mt-5">
        <h2>Generated Codes History</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Serial Code</th>
                    <th>Max Users</th>
                    <th>Price</th>
                    <th>Wager</th>
                    <th>Remark</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($historyResult)): ?>
                    <tr>
                        <td><?php echo $row['enserie']; ?></td>
                        <td><?php echo $row['utilisateurmax']; ?></td>
                        <td><?php echo htmlspecialchars((string)$row['prix'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['wager_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['creerunrendezvous'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="delete_serial" value="<?php echo $row['enserie']; ?>" />
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.10/dist/sweetalert2.min.js"></script>

    <script>
        <?php
        // If we have generated serials, show the modal with the list of serials
        if (isset($generatedSerialsJson)) {
            echo "let generatedSerials = " . $generatedSerialsJson . ";";
            echo "let serialList = document.getElementById('generatedSerialList');";
            echo "generatedSerials.forEach(function(serial) {
                    let listItem = document.createElement('li');
                    listItem.textContent = serial;
                    serialList.appendChild(listItem);
                  });
                  var myModal = new bootstrap.Modal(document.getElementById('generatedSerialsModal'));
                  myModal.show();";
        }
        ?>

        // Copy All Serial Numbers to Clipboard
        document.getElementById("copyButton").addEventListener("click", function() {
            let serialsText = '';
            // Get all the serials in the list and combine them into a string
            document.querySelectorAll('#generatedSerialList li').forEach(function(li) {
                serialsText += li.textContent + '\n';  // Add each serial with a new line
            });

            // Use the Clipboard API to copy to the clipboard
            navigator.clipboard.writeText(serialsText).then(function() {
                // Show SweetAlert2 with success message
                Swal.fire({
                    icon: 'success',
                    title: 'Serials copied to clipboard!',
                    showConfirmButton: false,
                    timer: 1500  // Auto-close after 1.5 seconds
                });
            }, function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to copy serials!',
                    text: err,
                    showConfirmButton: false,
                    timer: 1500  // Auto-close after 1.5 seconds
                });
            });
        });
    </script>
</body>
</html>
