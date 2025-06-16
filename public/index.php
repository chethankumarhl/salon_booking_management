<?php
include '../includes/db.php';
session_start();

$message = "";
$message_type = "";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialize variables
$booked_slots = [];

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user']) && isset($_POST['mobile']) && isset($_POST['date'])) {
    $mobile = trim($_POST['mobile']);
    $date = $_POST['date'];
    $slot_id = isset($_POST['timeSlot']) ? $_POST['timeSlot'] : '';

    if (empty($mobile) || empty($date) || empty($slot_id)) {
        $_SESSION['message'] = "Please fill in all fields.";
        $_SESSION['message_type'] = "danger";
    } else {
        // Fetch user_id from users table
        $user_sql = "SELECT id FROM users WHERE username = ?";
        $stmt_user = mysqli_prepare($conn, $user_sql);
        mysqli_stmt_bind_param($stmt_user, "s", $username);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);

        if ($row = mysqli_fetch_assoc($result_user)) {
            $user_id = $row['id'];
        } else {
            $_SESSION['message'] = "Error: User not found!";
            $_SESSION['message_type'] = "danger";
            mysqli_stmt_close($stmt_user);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        mysqli_stmt_close($stmt_user);

        // Check if slot is already booked
        $check_sql = "SELECT * FROM bookings WHERE date = ? AND slot_id = ? AND status = 'booked'";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "si", $date, $slot_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['message'] = "Sorry, this slot is already booked. Please choose another one.";
            $_SESSION['message_type'] = "warning";
        } else {
            // Insert booking
            $insert_sql = "INSERT INTO bookings (user_id, mobile, date, slot_id, status) VALUES (?, ?, ?, ?, 'booked')";
            $stmt_insert = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt_insert, "issi", $user_id, $mobile, $date, $slot_id);

            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['message'] = "Appointment booked successfully for $date!";
                echo "<script>alert('Appointment booked successfully for $date!');</script>";

                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: " . mysqli_error($conn);
                $_SESSION['message_type'] = "danger";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt);



        // ✅ Redirect to clear POST data
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ✅ Display message if set
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// If date is selected, fetch booked slots for that date
if (!empty($_POST['date'])) {
    $selected_date = $_POST['date'];
    $slot_sql = "SELECT slot_id FROM bookings WHERE date = ? AND status = 'booked'";
    $stmt_slots = mysqli_prepare($conn, $slot_sql);
    mysqli_stmt_bind_param($stmt_slots, "s", $selected_date);
    mysqli_stmt_execute($stmt_slots);
    $slot_result = mysqli_stmt_get_result($stmt_slots);

    while ($row = mysqli_fetch_assoc($slot_result)) {
        $booked_slots[] = $row['slot_id'];
    }

    mysqli_stmt_close($stmt_slots);
}

// Fetch all slots
$all_slots = [];
$slots_sql = "SELECT * FROM slots";
$slots_result = mysqli_query($conn, $slots_sql);

while ($slot = mysqli_fetch_assoc($slots_result)) {
    $all_slots[] = $slot;
}

// Fetch the upcoming slots of the user_id
$user_slots = [];
$sql = "SELECT slots.id, slots.start_time, slots.end_time, bookings.date FROM bookings
        JOIN slots ON bookings.slot_id = slots.id
        WHERE bookings.user_id = ? AND bookings.status = 'booked'
        ORDER BY bookings.date DESC";
$stmt_user_slots = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt_user_slots, "i", $user_id);
mysqli_stmt_execute($stmt_user_slots);
$result_user_slots = mysqli_stmt_get_result($stmt_user_slots);
while ($row = mysqli_fetch_assoc($result_user_slots)) {
    $user_slots[] = $row;
}

mysqli_stmt_close($stmt_user_slots);
mysqli_free_result($result_user_slots);
mysqli_free_result($slots_result);
// delete slots 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot'])) {
    if (!isset($_SESSION['user_id'])) {
        die("User not logged in.");
    }

    $slot_id_to_delete = $_POST['delete_slot_id'];
    $date_to_delete = $_POST['delete_slot_date']; // Date needs to be passed too
    $current_user_id = $_SESSION['user_id'];

    $delete_sql = "DELETE FROM bookings WHERE slot_id = ? AND user_id = ? AND date = ?";
    $stmt_delete = mysqli_prepare($conn, $delete_sql);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "iis", $slot_id_to_delete, $current_user_id, $date_to_delete);

        if (mysqli_stmt_execute($stmt_delete)) {
            echo "<script>alert('Deleted Slot ID: $slot_id_to_delete successfully');</script>";
        } else {
            echo "<script>alert('Error in deleting appointment: " . mysqli_error($conn) . "');</script>";
        }

        mysqli_stmt_close($stmt_delete);
    } else {
        $_SESSION['message'] = "Prepare failed: " . mysqli_error($conn);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// If a slot was deleted, show a message
// if (isset($_SESSION['message'])) {
// Redirect to the same page to refresh the list of appointments
// header("Location: " . $_SERVER['PHP_SELF']);
// exit();


// }
// Add review
if (isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $review = mysqli_real_escape_string($conn, $_POST['review']);

    if (!empty(trim($review))) {
        $insertReview = "INSERT INTO reviews (user_id, review_text) VALUES ($userId, '$review')";
        mysqli_query($conn, $insertReview);
    }

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete review
if (isset($_POST['delete_review']) && isset($_SESSION['user_id'])) {
    $reviewId = intval($_POST['review_id']);
    $userId = $_SESSION['user_id'];

    // Ensure only the owner can delete their review
    $checkQuery = "SELECT * FROM reviews WHERE id = $reviewId AND user_id = $userId";
    $resultCheck = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($resultCheck) > 0) {
        $deleteQuery = "DELETE FROM reviews WHERE id = $reviewId";
        mysqli_query($conn, $deleteQuery);
    }

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$reviewQuery = "
    SELECT r.id, r.review_text, r.created_at, u.username, r.user_id 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$reviewResult = mysqli_query($conn, $reviewQuery);

// Close the connection
mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <title>Salon</title>

</head>

<body>
    <!-- hero -->
    <nav class="navbar navbar-expand-lg mt-1 navbar-dark bg-transparent position-absolute w-100 " style="z-index: 10;">
        <div class="container">
            <a class="navbar-brand" href="#"><img src="../images/logo.png" alt="logo" height="100px" ></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-2 border-bottom">
                    <li class="nav-item fs-5">
                        <a class="nav-link active fs-4 " href="">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fs-4" href="#book">Book Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fs-4" href="#about">About</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link active fs-4" href="logout.php">Logout</a>
                    </li> -->
                    <li class="nav-item">
                        <div class="btn-group mt-2 text-center">
                            <button type="button" class="btn btn-standard text-light fs-5 border p-1 dropdown-toggle " data-bs-toggle="dropdown" aria-expanded="false">
                                Profile
                            </button>
                            <ul class="dropdown-menu text-center bg-secondary-subtle p-2">
                                <li class="dropdown-item fs-4 fw-semibold border-bottom border-4">Profile</li>
                                <li><a class="dropdown-item ml-1 fst-normal fs-5 overflow-auto" href="" id="myLink"> <?php echo $_SESSION["username"] ?></a></li>
                                <li><a class="dropdown-item fst-normal" href="#upcoming_slots">My Appointments</a></li>
                                <li><a class="dropdown-item ml-1 fst-normal fs-5 text-light bg-danger rounded-5 my-2" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div id="beautyCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000" data-bs-pause="false">
        <div class="carousel-inner">
            <!-- Slide 1 (Left Text) -->
            <div class="carousel-item active">
                <div class="image-darken">
                    <img src="../images/salon1.jpg" class="d-block w-100" alt="Luxury Salon">
                </div>
                <div class="carousel-caption text-white text-start fade-in" style="left: 10%; right: auto; top: 60%; transform: translateY(-50%);">
                    <h1 class="display-4 fw-medium">The Style Studio</h1>
                    <p class="lead fs-3">Indulge in premium hair & skincare</p>
                    <p class="fs-4">Book today for 20% off your first visit.</p>
                </div>
            </div>

            <!-- Slide 2 (Right Text) -->
            <div class="carousel-item">
                <div class="image-darken">
                    <img src="../images/salon4.jpg" class="d-block w-100" alt="Luxury Salon">
                </div>
                <div class="carousel-caption text-white text-end fade-in" style="left: 10%; right: auto; top: 70%; transform: translateY(-50%);">
                    <h1 class="display-4 fw-medium">Expert Stylists</h1>
                    <p class="lead fs-3">Perfect looks for any occasion</p>
                    <p class="fs-4">Bridal, haircuts, and more.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- slots booked  -->
    <div class="pt-0 p-5 m-3 bg-light">
        <h1 class="text-center my-5 border-bottom border-success p-2" id="upcoming_slots">Your Upcoming Appointments</h1>


        <?php if (empty($user_slots)): ?>
            <div class="alert alert-warning text-center fs-5">You have no upcoming appointments. <a href="#book" class="text-danger">Book Now...</a></div>
        <?php else: ?>
            <div class="row justify-content-center overflow-auto" id="slots">
                <?php foreach ($user_slots as $index => $slot): ?>
                    <?php
                    $slot_id = $slot['id'];
                    $start_time = $slot['start_time'];
                    $end_time = $slot['end_time'];
                    $date = $slot['date'];
                    $formatted_date = date("F j, Y", strtotime($date));
                    $delay = 0.2 + ($index * 0.2);
                    ?>
                    <div class="col-6 col-md-3 mb-3 ">
                        <div class="card slot-card shadow-sm text-center p-3 border border-success" style="min-height: 100px; animation-delay: <?php echo $delay; ?>s;">
                            <span class="badge text-bg-success">Scheduled</span>
                            <h6 class="card-title mb-2">Slot ID: <?php echo htmlspecialchars($slot_id); ?></h6>
                            <p class="card-text small">
                                <strong>Date:</strong> <?php echo htmlspecialchars($formatted_date); ?><br>
                                <strong>Time:</strong>
                                <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?>
                            </p>
                            <form method="post" action="" class="m-auto d-inline shadow-none">
                                <input type="hidden" name="delete_slot_id" value="<?php echo $slot_id; ?>">
                                <input type="hidden" name="delete_slot_date" value="<?php echo $date; ?>">
                                <button type="submit" name="delete_slot" class="btn btn-danger btn-sm mt-2">Delete</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>


    <div class="py-5 service-7 bg-light">
        <h1 class="our-services-heading text-center py-4 m-3 mb-0 mx-5" id="services">Our Services</h1><br><br><br>
        <div class="container">
            <div class="row">
                <!-- Column 1 -->
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <img src="../images/salon5.jpg" alt="Expert Stylists" />
                        <div class="service-card-body">
                            <h6 class="font-weight-medium">Expert Stylists</h6>
                            <p class="mt-3">Award-Winning Stylists <br>
                                10+ Years of Experience <br>
                                Personalized Consultations </p>
                        </div>
                    </div>
                </div>

                <!-- Column 2 -->
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <img src="../images/salon7.jpg" alt="Premium Services" />
                        <div class="service-card-body">
                            <h6 class="font-weight-medium">Premium Services</h6>
                            <p class="mt-3">Luxury Hair Treatments <br>
                                Bridal & Special Occasion <br>
                                Organic Product Lines </p>
                        </div>
                    </div>
                </div>

                <!-- Column 3 -->
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <img src="../images/salon6.jpg" alt="Exclusive Benefits" />
                        <div class="service-card-body">
                            <h6 class="font-weight-medium">Exclusive Benefits</h6>
                            <p class="mt-3">20% Off First Visit <br>
                                Membership Rewards <br>
                                Free Touch-Ups</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <br id="about">
    <!-- about us  -->
    <div class="d-flex justify-content-evenly p-3 my-5 gap-5 bg-light">
        <div class="">
            <img src="../images/salon6.jpg" height="300px" class="rounded" alt="...">
        </div>
        <div class="">
            <h1 style="border-bottom: 2px solid #ebab34;">About Us</h1>
            <p class="lead">Welcome to The Style Studio  where beauty meets artistry. We are more than just a salon; we are a destination for transformation and self-care. Our expert stylists and beauty professionals are dedicated to helping you express your unique style through tailored services in hair, skin, and makeup.</p>
            <p>At The Style Studio, we believe beauty is personal. With over a decade of experience, we combine cutting-edge techniques, premium products, and a passion for creativity to deliver a relaxing and luxurious salon experience. Whether you're getting ready for a special occasion or simply indulging in a little "me time," we're here to make sure you walk out glowing with confidence.

</p>
        </div>
    </div>
    <!-- book appointment -->

    <div class="d-flex flex-column justify-content-center align-items-center vh-90 py-4 bg-light" id="book"
        style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../images/salon3.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            user-select: none;">
        <div class="text-center text-white ">
            <h1>Book An Appointment With Us!</h1>
        </div><br>
        <!-- form  -->
        <div class="container py-5 pt-0 ">
            <div class="row justify-content-center">
                <div class="col-12 col-md-6">
                    <form id="booking-form" id="bookingForm" method="post" class="col-md-6 bg-white p-4 rounded shadow text-center gap-3">
                        <div class="mb-3">
                            <label for="user" class="form-label fw-semibold fs-5 mb-3">Username: </label>
                            <input type="text" class="form-control text-center fs-5" id="user" name="user" value="<?php echo htmlspecialchars($username); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label fw-semibold fs-5 mb-3">Mobile Number: </label>
                            <input type="number" class="form-control fs-5 text-center" id="mobile" name="mobile" placeholder="Enter your Mobile Number" value="<?php echo ($message_type != 'success' && isset($_POST['mobile'])) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label fw-semibold text-center fs-5 mb-3">Choose the Date: </label>
                            <input type="date" class="form-control fs-5 text-center" id="date" name="date" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" required onchange="this.form.submit()">
                        </div>

                        <fieldset class="mb-3">
                            <legend class="col-form-label fw-semibold fs-5 mb-3 pt-0">Choose a Time Slot</legend>
                            <div class="d-flex gap-3 flex-wrap justify-content-center overflow-scroll">
                                <?php foreach ($all_slots as $slot): ?>
                                    <?php
                                    $slot_id = $slot['id'];
                                    $start_time = $slot['start_time'];
                                    $end_time = $slot['end_time'];
                                    $is_booked = in_array($slot_id, $booked_slots);
                                    ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="timeSlot" id="slot<?php echo $slot_id; ?>"
                                            value="<?php echo $slot_id; ?>" <?php echo $is_booked ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="slot<?php echo $slot_id; ?>">
                                            <?php echo htmlspecialchars($start_time . " - " . $end_time); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <button type="submit" class="btn btn-dark fw-semibold fs-4 mb-3 w-100">Book Appointment</button>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> mt-3" id="messageContainer">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <br><br>


    <!-- reviews  -->

    <div class="bg-light py-1 pt-0 mb-5" id="reviews">
        <!-- Reviews Section -->
        <div class="container my-5 pt-3 ">
            <h2 class="text-center fw-bold mb-4 fs-2">Customer Reviews</h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mx-auto mb-4" style="max-width: 500px;">
                    <div class="mb-3">
                        <textarea name="review" class="form-control shadow-sm" rows="2" placeholder="Share your experience..." required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="submit_review" class="btn btn-dark">Submit Review</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="text-muted text-center">Please log in to submit a review.</p>
            <?php endif; ?>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while ($row = mysqli_fetch_assoc($reviewResult)): ?>
                    <div class="col">
                        <div class="card shadow border-0 h-100">
                            <div class="card-body">
                                <p class="card-text text-secondary fst-italic mb-3">"<?php echo htmlspecialchars($row['review_text']); ?>"</p>
                                <h6 class="card-subtitle text-end text-muted mb-3">- <?php echo htmlspecialchars($row['username']); ?><br><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></h6>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']): ?>
                                    <form method="POST" class="text-end mt-2 shadow-none " style="display: inline-block;">
                                        <input type="hidden" name="review_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete_review" class="btn btn-standard text-danger btn-sm px-1 py-1" style="font-size: 1rem; line-height: 1;">Delete</button>
                                    </form>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    </div>
    <!-- footer  -->

    <footer class="text-center text-white " style="background-color:rgb(43, 48, 74)">
        <!-- Grid container -->
        <div class="container pb-0 mx-5 ">
            <!-- Section: Links -->
            <section class="mt-5">
                <!-- Grid row-->
                <div class="row text-center d-flex justify-content-center pt-5 ">
                    <!-- Grid column -->
                    <div class="col-md-2">
                        <h6 class="text-uppercase font-weight-bold">
                            <a href="#about" class="text-white">About us</a>
                        </h6>
                    </div>
                    <!-- Grid column -->

                    <!-- Grid column -->
                    <div class="col-md-2">
                        <h6 class="text-uppercase font-weight-bold">
                            <a href="#services" class="text-white">Services</a>
                        </h6>
                    </div>
                    <!-- Grid column -->

                    <!-- Grid column -->
                    <div class="col-md-2">
                        <h6 class="text-uppercase font-weight-bold">
                            <a href="#book" class="text-white">Book Appointment</a>
                        </h6>
                    </div>
                    <!-- Grid column -->


                    <!-- Grid column -->
                    <div class="col-md-2">
                        <h6 class="text-uppercase font-weight-bold">
                            <a href="#contact" class="text-white">Contact</a>
                        </h6>
                    </div>
                    <!-- Grid column -->
                </div>
                <!-- Grid row-->
            </section>
            <!-- Section: Links -->

            <hr class="my-2 mt-4" />

            <section class="">
                <div class="container text-center text-md-start mt-5 mx-5">
                    <!-- Grid row -->
                    <div class="row mt-3 gap-5">
                        <!-- Grid column -->
                        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
                            <!-- Content -->
                            <h6 class="text-uppercase fw-bold mb-4">
                                <i class="fas fa-gem me-3"></i><img src="../images/logo.png" alt="logo" height="100px" >
                            </h6>
                            <p>
Where elegance meets expertise. The Style Studio is your destination for modern beauty, personalized care, and trendsetting style. From flawless cuts to rejuvenating treatments, we craft every experience to make you feel confident and radiant. Book your transformation today!

                            </p>
                        </div>
                        <!-- Grid column -->

                        <!-- Grid column -->
                        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4 mx-0">
                            <!-- Links -->
                            <h6 class="text-uppercase fw-bold mb-4">
                                Why Choose us ?
                            </h6>
                            <p>
                                &clubs; Highly skilled and certified professionals
                            </p>
                            <p>
                                &clubs; Use of premium and skin-friendly products


                            </p>
                            <p>
                                &clubs; Hygienic environment with modern equipment
                            </p>
                            <p>
                                &clubs; Personalized care and style consultations
                            </p>
                        </div>
                        <!-- Grid column -->



                        <!-- Grid column -->
                        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4" id="contact">
                            <!-- Links -->
                            <h6 class="text-uppercase fw-bold mb-4">Contact</h6>
                            <p><i class="fas fa-home me-3"></i> Koramangala, Bengaluru, IN</p>
                            <p>
                                <i class="fas fa-envelope me-3"></i>
                                Salon@salon.com
                            </p>
                            <p><i class="fas fa-phone me-3"></i> +99 00 00 33 11</p>
                            <p><i class="fas fa-print me-3"></i> + 89 77 23 64 21</p>
                        </div>
                        <!-- Grid column -->
                    </div>
                    <!-- Grid row -->
                </div>
            </section>
            <!-- Grid container -->

            <!-- Copyright -->

            <!-- Copyright -->
    </footer>
    <div
        class="text-center p-3 text-white  border-top border-1 border-secondary"
        style="background-color:rgb(43, 48, 74);width:100vw ; position: relative; bottom: 0; left: 0;">
        © 2025 Copyright:
        <a class="text-white" href="#">The Style Studio </a>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <script>
        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];
        // Set the min attribute of the date input to today
        document.getElementById('date').setAttribute('min', today);
        document.getElementById("myLink").addEventListener("click", function(event) {
            event.preventDefault();
        });
    </script>
    <script>
        $(document).ready(function() {
            // Handle slot fetching on date change (already there)
            $('#date').on('change', function() {
                var selectedDate = $(this).val();

                if (selectedDate) {
                    $.ajax({
                        url: 'get_slots.php',
                        type: 'POST',
                        data: {
                            date: selectedDate
                        },
                        success: function(response) {
                            $('#slots-container').html(response);
                        },
                        error: function() {
                            $('#slots-container').html('<div class="text-danger">Error fetching slots.</div>');
                        }
                    });
                } else {
                    $('#slots-container').empty();
                }
            });

            // 👇 Handle actual booking form submission
            $('#bookingForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var formData = $(this).serialize(); // Get form data

                $.ajax({
                    url: 'book_appointment.php', // The PHP file that handles booking
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#messageContainer').html(response); // Show response message
                        $('#bookingForm')[0].reset(); // Optionally clear the form
                        $('#slots-container').empty(); // Optionally clear the slots
                    },
                    error: function() {
                        $('#messageContainer').html('<div class="text-danger">Error booking appointment.</div>');
                    }
                });
            });
        });
    </script>


</body>

</html>