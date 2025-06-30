<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'job_seeker') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$job_seeker_id = $_SESSION['user_id'];

// 1. Fetch job seeker's skills
$skillsStmt = $conn->prepare("
    SELECT sm.name 
    FROM job_seeker_skills js 
    JOIN skill_master sm ON js.skill_id = sm.id 
    WHERE js.job_seeker_id = ?
");
$skillsStmt->execute([$job_seeker_id]);
$skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

// 2. Fetch all available jobs
$jobsStmt = $conn->prepare("
    SELECT job_id, job_title, tags, skills 
    FROM jobs 
    WHERE expire_date >= NOW()
");
$jobsStmt->execute();
$jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$jobs || empty($skills)) {
    echo json_encode(['error' => 'No jobs or no skills found']);
    exit();
}

// 3. Prepare data for Python
$seeker_profile = implode(', ', $skills);

$jobs_for_python = [];
foreach ($jobs as $job) {
    $job_text = $job['job_title'] . ' ' . $job['tags'] . ' ' . $job['skills'];
    $jobs_for_python[] = [
        'job_id' => $job['job_id'],
        'text' => $job_text
    ];
}

$input = [
    'seeker_profile' => $seeker_profile,
    'jobs' => $jobs_for_python
];

$input_json = json_encode($input);

// 4. Run Python recommendation script
$command = "python ../ai/recommend.py " . escapeshellarg($input_json);
$output = shell_exec($command);

// 5. Output result back to AJAX
header('Content-Type: application/json');
echo $output;
?>
