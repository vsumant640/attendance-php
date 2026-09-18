<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$dateTaken = date('Y-m-d');
$statusFilter = isset($_GET['statusFilter']) ? strtolower(trim($_GET['statusFilter'])) : 'all';

$statusLabel = 'All';
$statusValue = null;
if ($statusFilter === 'present') {
    $statusLabel = 'Present';
    $statusValue = '1';
} elseif ($statusFilter === 'absent') {
    $statusLabel = 'Absent';
    $statusValue = '0';
}

$filename = 'Attendance-' . $statusLabel . '-list-' . $dateTaken;

header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $filename . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

$query = "SELECT tblattendance.Id,tblattendance.status,tblattendance.dateTimeTaken,tblclass.className,
        tblclassarms.classArmName,tblsessionterm.sessionName,tblsessionterm.termId,tblterm.termName,
        tblstudents.firstName,tblstudents.lastName,tblstudents.otherName,tblstudents.admissionNumber
        FROM tblattendance
        INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
        INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
        INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
        INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
        WHERE tblattendance.dateTimeTaken = ?
        AND tblattendance.classId = ?
        AND tblattendance.classArmId = ?";

if ($statusValue !== null) {
    $query .= " AND tblattendance.status = ?";
}

$stmt = $conn->prepare($query);

if ($statusValue !== null) {
    $stmt->bind_param('siis', $dateTaken, $_SESSION['classId'], $_SESSION['classArmId'], $statusValue);
} else {
    $stmt->bind_param('sii', $dateTaken, $_SESSION['classId'], $_SESSION['classArmId']);
}

$stmt->execute();
$ret = $stmt->get_result();

echo '<table border="1">';
echo '<thead>
        <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Other Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Class Arm</th>
            <th>Session</th>
            <th>Term</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
      </thead>';

echo '<tbody>';

$cnt = 1;
if ($ret && $ret->num_rows > 0) {
    while ($row = $ret->fetch_assoc()) {
        $status = ($row['status'] == '1') ? 'Present' : 'Absent';

        echo '<tr>
                <td>' . $cnt . '</td>
                <td>' . $row['firstName'] . '</td>
                <td>' . $row['lastName'] . '</td>
                <td>' . $row['otherName'] . '</td>
                <td>' . $row['admissionNumber'] . '</td>
                <td>' . $row['className'] . '</td>
                <td>' . $row['classArmName'] . '</td>
                <td>' . $row['sessionName'] . '</td>
                <td>' . $row['termName'] . '</td>
                <td>' . $status . '</td>
                <td>' . $row['dateTimeTaken'] . '</td>
              </tr>';
        $cnt++;
    }
} else {
    echo '<tr><td colspan="11">No attendance records found for the selected filter.</td></tr>';
}

echo '</tbody></table>';

$stmt->close();
?>