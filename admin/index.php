<?php
include "../includes/db.php";
session_name("admin_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
    exit();
}
// Handle action submissions
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $action = $_POST['action'];

    switch ($action) {
        case 'confirm':
            $updateQuery = "UPDATE bookings SET status = 'Confirmed' WHERE id = $bookingId";
            break;
        case 'cancel':
            $updateQuery = "UPDATE bookings SET status = 'Cancelled' WHERE id = $bookingId";
            break;
        case 'finish':
            $updateQuery = "UPDATE bookings SET status = 'Finished' WHERE id = $bookingId";
            break;
        case 'delete':
            $updateQuery = "DELETE FROM bookings WHERE id = $bookingId";
            break;
        default:
            $updateQuery = "";
    }

    if (!empty($updateQuery)) {
        mysqli_query($conn, $updateQuery);
    }
}

// Fetch bookings
$query = "
    SELECT 
        b.id AS booking_id,
        u.username AS customer_name,
        b.mobile,
        b.date AS booking_date,
        s.start_time,
        s.end_time,
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN slots s ON b.slot_id = s.id
    ORDER BY b.date ASC, s.start_time ASC
";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Add slot
if (isset($_POST['add_slot'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $check = "SELECT COUNT(*) AS total FROM slots";
    $countResult = mysqli_query($conn, $check);
    $row = mysqli_fetch_assoc($countResult);

    if ($row['total'] < 5) {
        $insert = "INSERT INTO slots (start_time, end_time) VALUES ('$start_time', '$end_time')";
        mysqli_query($conn, $insert);
         // ✅ Redirect to clear POST data
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<script>alert('Maximum 5 slots are allowed!');</script>";
    }
}

// Delete slot
if (isset($_POST['delete_slot'])) {
    $slot_id = intval($_POST['slot_id']);
    $delete = "DELETE FROM slots WHERE id = $slot_id";
    mysqli_query($conn, $delete);
}

// Fetch slots
$slotsQuery = "SELECT * FROM slots ORDER BY start_time";
$slotsResult = mysqli_query($conn, $slotsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>Admin-Salon</title>
    <style>
        .admin-heading {
            font-size: 3rem;
            text-align: center;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            margin-top: 30px;
            margin-bottom: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        table {
            border-radius: 8px;
            overflow: hidden;
            font-size: 1.1rem;
        }
        thead th {
            background-color: #343a40;
            color: white;
            text-align: center;
        }
        td, th {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body class="bg-light">
<div class="mx-3 d-flex justify-content-start align-items-center gap-5">
    <!-- Sidebar -->
   <!-- Sidebar Button -->
<div class="sidebar-panel d-inline">
    <button class="btn btn-dark px-3 py-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
        <i class="bi bi-list fs-2"></i>
    </button>
</div>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel"><img src="../images/logo-black.png" alt="logo" height="100px" ></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column">
            <a class="nav-link text-dark fs-4" href="">Home</a>
            <a class="nav-link text-dark fs-4" href="#bookings">Bookings</a>
            <a class="nav-link text-dark fs-4" href="#slots">Slots</a>
            <a class="nav-link text-danger fs-4" href="logout.php">Logout</a>
        </nav>
    </div>
</div>

    <!-- Heading -->
    <div class="heading d-inline text-center flex-end" style="margin-left:450px;">
        <h1 class="admin-heading">Admin Dashboard</h1>
    </div>
</div>

<div class="mt-5" id="bookings">
    <h1 class="text-center border-bottom p-2">Manage Bookings</h1>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Upcoming Bookings</h2>
                <table class="table table-striped table-hover shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $bookingId = $row['booking_id'];
                                $status = $row['status'];
                                $timeSlot = htmlspecialchars($row['start_time']) . " - " . htmlspecialchars($row['end_time']);
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($bookingId) . "</td>";
                                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
                                echo "<td>" . $timeSlot . "</td>";
                                echo "<td>" . htmlspecialchars($status) . "</td>";

                                echo "<td>";
                                
                                if ($status === 'booked') {
                                    // Finish
                                    echo "<form method='POST' style='display:inline-block;'>
                                            <input type='hidden' name='booking_id' value='$bookingId'>
                                            <input type='hidden' name='action' value='finish'>
                                            <button type='submit' class='btn btn-primary btn-sm me-1'>Finish</button>
                                          </form>";
                                    // Cancel
                                    echo "<form method='POST' style='display:inline-block;'>
                                            <input type='hidden' name='booking_id' value='$bookingId'>
                                            <input type='hidden' name='action' value='cancel'>
                                            <button type='submit' class='btn btn-warning btn-sm me-1'>Cancel</button>
                                          </form>";
                                    // Delete
                                    echo "<form method='POST' style='display:inline-block;'>
                                            <input type='hidden' name='booking_id' value='$bookingId'>
                                            <input type='hidden' name='action' value='delete'>
                                            <button type='submit' class='btn btn-danger btn-sm'>Delete</button>
                                          </form>";
                                } elseif ($status === 'Cancelled' || $status === 'Finished') {
                                    echo "<form method='POST' style='display:inline-block;'>
                                            <input type='hidden' name='booking_id' value='$bookingId'>
                                            <input type='hidden' name='action' value='delete'>
                                            <button type='submit' class='btn btn-danger btn-sm'>Delete</button>
                                          </form>";
                                }

                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No bookings found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<hr>


<div class="mt-5" id="slots">
        <h1 class="text-center border-bottom p-2">Manage Slots</h1>
<div class="container mt-5">
    <h2 class="text-center">Manage Time Slots</h2>

    <!-- Slot Table -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h4 class="text-center">Existing Slots</h4>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (mysqli_num_rows($slotsResult) > 0) {
                    mysqli_data_seek($slotsResult, 0); // reset result pointer
                    while ($slot = mysqli_fetch_assoc($slotsResult)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($slot['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($slot['start_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($slot['end_time']) . "</td>";
                        echo "<td>
                                <form method='POST' style='display:inline-block;'>
                                    <input type='hidden' name='slot_id' value='{$slot['id']}'>
                                    <button type='submit' name='delete_slot' class='btn btn-danger btn-sm'>Delete</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center'>No slots available</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Slot Form -->
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <h4 class="text-center">Add New Slot</h4>
            <form method="POST">
                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" name="add_slot" class="btn btn-primary">Add Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<hr>
</body>
</html>
