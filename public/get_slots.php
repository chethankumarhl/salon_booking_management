<?php
include '../includes/db.php';
session_start();

if (isset($_POST['date'])) {
    $selected_date = $_POST['date'];
    $booked_slots = [];

    // Get booked slot IDs for the selected date
    $sql = "SELECT slot_id FROM bookings WHERE date = ? AND status = 'booked'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $booked_slots[] = $row['slot_id'];
    }

    mysqli_stmt_close($stmt);

    // Get all slots
    $slots_sql = "SELECT * FROM slots";
    $slots_result = mysqli_query($conn, $slots_sql);

    if (mysqli_num_rows($slots_result) > 0) {
        echo '<fieldset class="mb-3">
            <legend class="col-form-label fw-semibold fs-5 mb-3 pt-0">Choose a Time Slot</legend>
            <div class="d-flex gap-3 flex-wrap justify-content-center overflow-scroll">';
        while ($slot = mysqli_fetch_assoc($slots_result)) {
            $slot_id = $slot['id'];
            $start_time = $slot['start_time'];
            $end_time = $slot['end_time'];
            $is_booked = in_array($slot_id, $booked_slots);

            echo '<div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="timeSlot" id="slot'.$slot_id.'" value="'.$slot_id.'" '.($is_booked ? 'disabled' : '').'>
                <label class="form-check-label" for="slot'.$slot_id.'">'.htmlspecialchars($start_time." - ".$end_time).'</label>
            </div>';
        }
        echo '</div></fieldset>';
    } else {
        echo '<div class="alert alert-warning">No slots available.</div>';
    }
}
?>
