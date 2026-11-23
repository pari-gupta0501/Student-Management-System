<?php
require_once __DIR__ . '/config.php';
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect_to(string $url='index.php'): never { header('Location: '.$url); exit; }
$message = '';
try {
    $pdo = db();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
            throw new RuntimeException('Invalid form token. Please try again.');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $roll = trim($_POST['roll_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $course = trim($_POST['course'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $semester = (int)($_POST['semester'] ?? 0);
            if ($name === '' || $roll === '' || $email === '' || $course === '' || $department === '' || $semester < 1 || $semester > 8) {
                throw new RuntimeException('Please complete all required fields. Semester must be between 1 and 8.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please enter a valid email address.');
            $check = $pdo->prepare('SELECT id FROM students WHERE (roll_number = ? OR email = ?) AND id <> ?');
            $check->execute([$roll, $email, $id]);
            if ($check->fetch()) throw new RuntimeException('That roll number or email is already registered.');
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE students SET name=?, roll_number=?, email=?, phone=?, course=?, department=?, semester=? WHERE id=?');
                $stmt->execute([$name,$roll,$email,$phone,$course,$department,$semester,$id]);
                $_SESSION['flash'] = 'Student record updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO students (name,roll_number,email,phone,course,department,semester) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$name,$roll,$email,$phone,$course,$department,$semester]);
                $_SESSION['flash'] = 'Student added successfully.';
            }
            redirect_to();
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM students WHERE id=?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Student record deleted.';
            redirect_to();
        }
    }
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student-records.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name','Roll Number','Email','Phone','Course','Department','Semester']);
        $rows = $pdo->query('SELECT name,roll_number,email,phone,course,department,semester FROM students ORDER BY name')->fetchAll();
        foreach ($rows as $row) fputcsv($out, $row);
        fclose($out); exit;
    }
    $flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
    $editStudent = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id=?');
        $stmt->execute([(int)$_GET['edit']]);
        $editStudent = $stmt->fetch() ?: null;
    }
    $search = trim($_GET['q'] ?? '');
    $departmentFilter = trim($_GET['department'] ?? '');
    $sql = 'SELECT * FROM students WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (name LIKE ? OR roll_number LIKE ? OR email LIKE ?)';
        $like = '%'.$search.'%'; array_push($params,$like,$like,$like);
    }
    if ($departmentFilter !== '') { $sql .= ' AND department=?'; $params[] = $departmentFilter; }
    $sql .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $students = $stmt->fetchAll();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $courses = (int)$pdo->query('SELECT COUNT(DISTINCT course) FROM students')->fetchColumn();
    $departments = (int)$pdo->query('SELECT COUNT(DISTINCT department) FROM students')->fetchColumn();
    $departmentList = $pdo->query('SELECT DISTINCT department FROM students ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
$val = fn($key,$default='') => e($editStudent[$key] ?? $default);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CampusDesk | Student Management</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top"><div class="brand"><span class="logo">C</span><div><b>CampusDesk</b><small>Student Management System</small></div></div><span class="pill">PHP + MySQL</span></header>
<main class="wrap">
<section class="hero"><div><p class="eyebrow">ACADEMIC ADMINISTRATION</p><h1>Student Management</h1><p>Manage student records securely in a MySQL database.</p></div><a class="btn white" href="#student-form">＋ Add student</a></section>
<?php if (!empty($error)): ?><div class="alert error"><?=e($error)?><p>Check that MySQL is running, the database has been imported, and <code>config.php</code> has the correct credentials.</p></div><?php endif; ?>
<?php if (!empty($flash)): ?><div class="alert success"><?=e($flash)?></div><?php endif; ?>
<section class="stats">
<div class="stat"><span>Total students</span><strong><?=isset($total)?$total:0?></strong></div>
<div class="stat"><span>Courses</span><strong><?=isset($courses)?$courses:0?></strong></div>
<div class="stat"><span>Departments</span><strong><?=isset($departments)?$departments:0?></strong></div>
</section>
<div class="layout">
<section class="panel" id="student-form">
<div class="panel-head"><div><h2><?= $editStudent ? 'Edit student' : 'Add a student' ?></h2><p>Fields marked * are required.</p></div><?php if($editStudent): ?><a class="muted-link" href="index.php">Cancel edit</a><?php endif; ?></div>
<form method="post" action="index.php">
<input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?=$editStudent ? (int)$editStudent['id'] : 0?>">
<label>Full name *<input name="name" required maxlength="100" value="<?=$val('name')?>" placeholder="e.g. Aditi Sharma"></label>
<label>Roll number *<input name="roll_number" required maxlength="30" value="<?=$val('roll_number')?>" placeholder="e.g. IT2026-014"></label>
<label>Email *<input name="email" type="email" required maxlength="120" value="<?=$val('email')?>" placeholder="name@example.com"></label>
<label>Phone<input name="phone" maxlength="20" value="<?=$val('phone')?>" placeholder="Optional contact number"></label>
<label>Course *<input name="course" required maxlength="80" value="<?=$val('course')?>" placeholder="e.g. B.Tech"></label>
<label>Department *<select name="department" required><option value="">Select department</option><?php foreach(['Computer Science','Information Technology','Electronics','Mechanical','Electrical','Business Administration','Other'] as $d): ?><option value="<?=e($d)?>" <?=($val('department')===$d?'selected':'')?>><?=e($d)?></option><?php endforeach; ?></select></label>
<label>Semester *<select name="semester" required><option value="">Select semester</option><?php for($i=1;$i<=8;$i++): ?><option value="<?=$i?>" <?=((string)($editStudent['semester']??'')===(string)$i?'selected':'')?>><?=$i?></option><?php endfor; ?></select></label>
<button class="btn primary" type="submit"><?=$editStudent?'Update student':'Save student'?></button>
</form>
</section>
<section class="panel records">
<div class="panel-head"><div><h2>Student records</h2><p><?=count($students ?? [])?> record(s) shown</p></div><a class="btn outline" href="index.php?export=csv">↓ Export CSV</a></div>
<form class="filters" method="get">
<input name="q" value="<?=e($search ?? '')?>" placeholder="Search name, roll number or email">
<select name="department"><option value="">All departments</option><?php foreach(($departmentList ?? []) as $d): ?><option value="<?=e($d)?>" <?= (($departmentFilter ?? '')===$d?'selected':'') ?>><?=e($d)?></option><?php endforeach; ?></select>
<button class="btn primary" type="submit">Search</button><a class="btn outline" href="index.php">Reset</a>
</form>
<div class="table-wrap"><table><thead><tr><th>Student</th><th>Roll no.</th><th>Email</th><th>Course</th><th>Department</th><th>Sem.</th><th>Actions</th></tr></thead><tbody>
<?php foreach(($students ?? []) as $s): ?><tr><td><b><?=e($s['name'])?></b></td><td><?=e($s['roll_number'])?></td><td><?=e($s['email'])?></td><td><?=e($s['course'])?></td><td><?=e($s['department'])?></td><td><?=e($s['semester'])?></td><td class="actions"><a href="index.php?edit=<?=(int)$s['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this student record?')"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$s['id']?>"><button type="submit">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if(empty($students)): ?><div class="empty">No student records found. Add a student using the form.</div><?php endif; ?>
</section>
</div>
<footer>CampusDesk · Academic project prototype · Data stored in MySQL</footer>
</main>
</body></html>
