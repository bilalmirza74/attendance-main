<?php
include_once('../templates/header.php');
?>
<?php
require_once('../includes/main.function.inc.php');
// isLoged();
require_once '../includes/dbh.inc.php';
require_once '../includes/action.function.inc.php';

$student_count = 1;
if (isset($_COOKIE['class_id'])) {
    $cookieValue = $_COOKIE['class_id'];
    $students = allStudents($conn, $cookieValue);
} else {
    $students = [];
}

function getAttendanceForCurrentMonth($conn, $class_id, $currentDate = NULL)
{
    if (!isset($currentDate)) {
        $currentDate = date('Y-m');
    }
    $monthPart = date("m", strtotime($currentDate));
    $yearPart = date("Y", strtotime($currentDate));

    $query = "SELECT A.attendance_id, A.date, A.time, B.student_id
              FROM attendance AS A
              LEFT JOIN absentees AS B 
              ON A.attendance_id = B.attendance_id
              WHERE MONTH(A.date) = ? AND YEAR(A.date) = ? AND A.class_id = ?
              ORDER BY A.date ASC";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("iii", $monthPart, $yearPart, $class_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $attendanceRecords = array(); // Initialize an empty array to store the attendance records

        while ($row = $result->fetch_assoc()) {
            $attendanceId = $row['attendance_id'];
            $date = $row['date'];
            $time = $row['time'];
            $studentId = $row["student_id"];

            // Check if the attendance record already exists in the array, and if not, create it
            if (!isset($attendanceRecords[$attendanceId])) {
                $attendanceRecords[$attendanceId] = array(
                    "date" => $date,
                    "time" => $time,
                    "absentees" => array()
                );
            }

            // Add the student to the absentees list for this attendance record
            $attendanceRecords[$attendanceId]["absentees"][] = $studentId;
        }


        $stmt->close();
        return $attendanceRecords;
    } else {
        // Handle any errors if the query fails.
        return false;
    }
}

$month = date("Y-m");
if (isset($_GET["month"])) {
    $month = $_GET['month'];
}
// print_r( $attendance);
$attendance = getAttendanceForCurrentMonth($conn, $_COOKIE["class_id"], $month);

?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../styles/attendanceSheet.css">
<div class="over main-body d-flex px-3">
    <?php include_once("../templates/sidemenu.inc.php"); ?>
    <div class="over d-flex flex-column flex-grow-1 gap-2">
        <div class="d-flex justify-content-between fg-bg rounded py-2 px-3">
            <!-- Navigation buttons -->
            <a href="<?= $_SERVER['PHP_SELF'] . "?month=" . date("Y-m", strtotime($month . " -1 month")) ?>" class="w-25 text-start">
                <i class="fa-solid fa-arrow-left"></i>
                <?= date("M, Y", strtotime($month . " -1 month")) ?>
            </a>
            <div class="w-25 text-center">
                <?= date("F, Y", strtotime($month)) ?>
            </div>
            <a href="<?= $_SERVER['PHP_SELF'] . "?month=" . date("Y-m", strtotime($month . " +1 month")) ?>" class="w-25 text-end">
                <?= date("M, Y", strtotime($month . " +1 month")) ?>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <?php foreach ($attendance as $key => $value): ?>
                            <th scope="col">
                                <?= date("d", strtotime($value["date"])) ?> <br>
                                <?= date("h:m", strtotime($value["time"])) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $student['name']; ?></td>
                            <?php foreach ($attendance as $key => $value): ?>
                                <td>
                                    <?php if (in_array($student['student_id'], $value['absentees'])): ?>
                                        <span class="absent">A</span>
                                    <?php else: ?>
                                        <span class="present">P</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once("../templates/footer.php"); ?>