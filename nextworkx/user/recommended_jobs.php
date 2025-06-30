<?php
session_start();
include('../includes/db.php');
include('../includes/header_jobseeker.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'job_seeker') {
    header("Location: ../login.php");
    exit();
}

// Call recommend_jobs.php and get recommended job IDs
$recommendations_json = file_get_contents('recommend_jobs.php');
$recommendations = json_decode($recommendations_json, true);

$recommended_jobs = [];

if (isset($recommendations['recommended_jobs']) && !empty($recommendations['recommended_jobs'])) {
    $job_ids = array_column($recommendations['recommended_jobs'], 'job_id');

    // Prepare dynamic placeholders
    $placeholders = rtrim(str_repeat('?,', count($job_ids)), ',');

    // Fetch job details for recommended job IDs
    $stmt = $conn->prepare("
        SELECT jobs.*, cp.logo, cp.company_name
        FROM jobs
        LEFT JOIN company_profiles cp ON jobs.recruiter_id = cp.recruiter_id
        WHERE jobs.job_id IN ($placeholders)
    ");
    $stmt->execute($job_ids);
    $recommended_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="../assets/css/saved_jobs.css">

<div class="saved-jobs-container">
    <h1 class="page-title">Recommended Jobs for You</h1>

    <div class="jobs-grid">
        <?php if (count($recommended_jobs) > 0): ?>
            <?php foreach ($recommended_jobs as $job): ?>
                <div class="job-card">
                    <div class="job-type-badge"><?= htmlspecialchars($job['job_level']) ?></div>

                    <div class="company-logo">
                        <img src="<?= !empty($job['logo']) ? '../uploads/company/' . htmlspecialchars($job['logo']) : '../uploads/company/default_logo.png' ?>" alt="Company Logo">
                    </div>

                    <h2 class="job-title"><?= htmlspecialchars($job['job_title']) ?></h2>

                    <div class="company-name"><?= htmlspecialchars($job['company_name']) ?: 'Company Name' ?></div>

                    <div class="location-salary">
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['city']) ?>, <?= htmlspecialchars($job['country']) ?></span>
                        <span><i class="fas fa-wallet"></i> $<?= number_format($job['min_salary']) ?> - $<?= number_format($job['max_salary']) ?></span>
                    </div>

                    <div class="bookmark-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">No recommendations found. Update your profile skills!</p>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer_jobseeker.php'); ?>
