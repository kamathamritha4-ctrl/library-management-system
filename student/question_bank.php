<?php
include("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS question_bank (
    file_key VARCHAR(80) PRIMARY KEY,
    course VARCHAR(20) NOT NULL,
    year_label VARCHAR(20) NOT NULL,
    semester_label VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$courses = ['BCom', 'BCA'];
$years = [
    '1st Year' => ['1st Sem', '2nd Sem'],
    '2nd Year' => ['3rd Sem', '4th Sem'],
    '3rd Year' => ['5th Sem', '6th Sem'],
];

$success = '';
$error = '';
$uploadDir = __DIR__ . '/../uploads/question_bank';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if (isset($_POST['upload_question']) && isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
    $course = $_POST['course'] ?? '';
    $year = $_POST['year_label'] ?? '';
    $semester = $_POST['semester_label'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $originalName = basename($_FILES['question_file']['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    if (!in_array($course, $courses, true) || !isset($years[$year]) || !in_array($semester, $years[$year], true)) {
        $error = 'Please select a valid course, year, and semester.';
    } elseif ($title === '') {
        $error = 'Please enter a question bank title.';
    } elseif (!in_array($ext, $allowed, true)) {
        $error = 'Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.';
    } else {
        $fileKey = bin2hex(random_bytes(16));
        $storedName = $fileKey . '.' . $ext;
        $destination = $uploadDir . '/' . $storedName;

        if (move_uploaded_file($_FILES['question_file']['tmp_name'], $destination)) {
            $stmt = $conn->prepare("INSERT INTO question_bank (file_key, course, year_label, semester_label, title, original_name, stored_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $fileKey, $course, $year, $semester, $title, $originalName, $storedName);
            if ($stmt->execute()) {
                $success = 'Question bank uploaded successfully.';
            } else {
                @unlink($destination);
                $error = 'Could not save question bank details.';
            }
        } else {
            $error = 'Could not upload the selected file.';
        }
    }
} elseif (isset($_POST['upload_question'])) {
    $error = 'Please choose a file to upload.';
}

$selectedCourse = $_GET['course'] ?? '';
$selectedYear = $_GET['year_label'] ?? '';
$selectedSemester = $_GET['semester_label'] ?? '';
$where = [];
$params = [];
$types = '';

if (in_array($selectedCourse, $courses, true)) {
    $where[] = 'course = ?';
    $params[] = $selectedCourse;
    $types .= 's';
}
if (isset($years[$selectedYear])) {
    $where[] = 'year_label = ?';
    $params[] = $selectedYear;
    $types .= 's';
}
if ($selectedYear !== '' && isset($years[$selectedYear]) && in_array($selectedSemester, $years[$selectedYear], true)) {
    $where[] = 'semester_label = ?';
    $params[] = $selectedSemester;
    $types .= 's';
}

$sql = "SELECT * FROM question_bank" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY course, year_label, semester_label, uploaded_at DESC";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $questionBanks = $stmt->get_result();
} else {
    $questionBanks = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Student Question Bank</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{--primary:#E24C24;--primary2:#C93E18;--navy:#1F2940;--muted:#64748b}
*{box-sizing:border-box}body{margin:0;font-family:'Poppins','Segoe UI',Arial,sans-serif;background:radial-gradient(circle at top right,#ffe9e1 0%,#f7ece8 35%,#f5f7fb 78%)}
.overlay{min-height:100vh;background:rgba(20,29,48,.22);display:flex;flex-direction:column;align-items:center;padding:34px 16px;color:#fff}.header{width:min(1120px,95%);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}.nav{display:flex;gap:10px;flex-wrap:wrap}.nav a,.logout{background:#1F2940;color:white;padding:9px 13px;border-radius:10px;text-decoration:none;font-weight:600}.nav a.active{background:linear-gradient(135deg,var(--primary),var(--primary2))}.panel{width:min(1120px,95%);background:white;color:#111827;padding:20px;border-radius:14px;box-shadow:0 15px 34px rgba(0,0,0,.2);margin-top:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}.card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#f8fafc}.card h4{margin:0 0 8px;color:#1f2940}.semester{display:inline-block;margin:4px 6px 4px 0;padding:6px 10px;border-radius:20px;background:#fff;color:#334155;text-decoration:none;border:1px solid #e2e8f0;font-size:13px}.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:10px}input,select{width:100%;padding:11px;border:1px solid #d9dfeb;border-radius:10px;background:#edf1fb}button{padding:11px 16px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;font-weight:700;cursor:pointer}.alert-success,.alert-error{padding:11px 14px;border-radius:10px;margin-bottom:12px}.alert-success{background:#dcfce7;color:#166534}.alert-error{background:#fee2e2;color:#991b1b}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:12px;border-bottom:1px solid #edf2f7;text-align:left}th{background:#f8fafc;color:#334155}.download{color:#E24C24;font-weight:700;text-decoration:none}@media(max-width:760px){table{display:block;overflow:auto}.header h2{font-size:20px}}
</style>
</head>
<body>
<div class="overlay">
    <div class="header">
        <div style="display:flex; align-items:center; gap:10px;"><img src="https://trishaedu.com/Trisha-Logo.png" alt="Trisha Logo" style="width:44px;height:44px;border-radius:10px;background:white;object-fit:contain;"><h2>📚 Student Question Bank</h2></div>
        <div class="nav"><a href="search.php">Book Search</a><a class="active" href="question_bank.php">Question Bank</a><a class="logout" href="../logout.php">Logout</a></div>
    </div>

    <div class="panel">
        <?php if($success) echo "<div class='alert-success'>" . htmlspecialchars($success) . "</div>"; ?>
        <?php if($error) echo "<div class='alert-error'>" . htmlspecialchars($error) . "</div>"; ?>
        <h3>Courses & Semesters</h3>
        <div class="grid">
            <?php foreach ($courses as $course): ?>
                <div class="card">
                    <h4><?php echo htmlspecialchars($course); ?></h4>
                    <?php foreach ($years as $year => $semesters): ?>
                        <strong><?php echo htmlspecialchars($year); ?></strong><br>
                        <?php foreach ($semesters as $semester): ?>
                            <a class="semester" href="?course=<?php echo urlencode($course); ?>&year_label=<?php echo urlencode($year); ?>&semester_label=<?php echo urlencode($semester); ?>"><?php echo htmlspecialchars($semester); ?></a>
                        <?php endforeach; ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h3>Upload Question Bank</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <select name="course" required><option value="">Select Course</option><?php foreach($courses as $course) echo "<option value='".htmlspecialchars($course)."'>".htmlspecialchars($course)."</option>"; ?></select>
                <select name="year_label" required><option value="">Select Year</option><?php foreach(array_keys($years) as $year) echo "<option value='".htmlspecialchars($year)."'>".htmlspecialchars($year)."</option>"; ?></select>
                <select name="semester_label" required><option value="">Select Semester</option><option>1st Sem</option><option>2nd Sem</option><option>3rd Sem</option><option>4th Sem</option><option>5th Sem</option><option>6th Sem</option></select>
                <input type="text" name="title" placeholder="Question bank title" required>
                <input type="file" name="question_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <button type="submit" name="upload_question">Upload</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3>Stored Question Banks</h3>
        <form method="get" class="form-row">
            <select name="course"><option value="">All Courses</option><?php foreach($courses as $course): ?><option value="<?php echo htmlspecialchars($course); ?>" <?php echo $selectedCourse === $course ? 'selected' : ''; ?>><?php echo htmlspecialchars($course); ?></option><?php endforeach; ?></select>
            <select name="year_label"><option value="">All Years</option><?php foreach(array_keys($years) as $year): ?><option value="<?php echo htmlspecialchars($year); ?>" <?php echo $selectedYear === $year ? 'selected' : ''; ?>><?php echo htmlspecialchars($year); ?></option><?php endforeach; ?></select>
            <select name="semester_label"><option value="">All Semesters</option><option <?php echo $selectedSemester === '1st Sem' ? 'selected' : ''; ?>>1st Sem</option><option <?php echo $selectedSemester === '2nd Sem' ? 'selected' : ''; ?>>2nd Sem</option><option <?php echo $selectedSemester === '3rd Sem' ? 'selected' : ''; ?>>3rd Sem</option><option <?php echo $selectedSemester === '4th Sem' ? 'selected' : ''; ?>>4th Sem</option><option <?php echo $selectedSemester === '5th Sem' ? 'selected' : ''; ?>>5th Sem</option><option <?php echo $selectedSemester === '6th Sem' ? 'selected' : ''; ?>>6th Sem</option></select>
            <button type="submit">Filter</button>
        </form>
        <table>
            <thead><tr><th>Course</th><th>Year</th><th>Semester</th><th>Title</th><th>File</th><th>Uploaded</th></tr></thead>
            <tbody>
            <?php if ($questionBanks && $questionBanks->num_rows > 0): ?>
                <?php while ($row = $questionBanks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                        <td><?php echo htmlspecialchars($row['year_label']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester_label']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><a class="download" href="../uploads/question_bank/<?php echo rawurlencode($row['stored_name']); ?>" target="_blank">Download</a></td>
                        <td><?php echo htmlspecialchars($row['uploaded_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No question banks found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
