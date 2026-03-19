<?php
/**
 * TrustLoan – Admin: approve application. Assign group (or create), set meeting, create loan + repayments, audit.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/Group.php';
require_once __DIR__ . '/../Classes/Loan.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$appId = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
$groupId = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
$createGroupName = isset($_POST['group_name']) ? trim((string) $_POST['group_name']) : '';
$mfiId = isset($_POST['mfi_id']) ? (int) $_POST['mfi_id'] : 0;
$meetingName = isset($_POST['meeting_name']) ? trim((string) $_POST['meeting_name']) : '';
$meetingDay = isset($_POST['meeting_day']) ? (int) $_POST['meeting_day'] : 3;
$meetingTime = isset($_POST['meeting_time']) ? trim((string) $_POST['meeting_time']) : '09:00';

if ($appId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_application']);
    exit;
}

$app = Application::getByIdWithDetails($appId);
if (!$app || $app['status'] !== 'new' && $app['status'] !== 'in_progress') {
    echo json_encode(['ok' => false, 'error' => 'invalid_or_already_processed']);
    exit;
}

$userId = (int) $app['user_id'];
$amount = (float) $app['requested_amount'];
$weeks = (int) ($app['repayment_weeks'] ?? 12);
if ($weeks <= 0) $weeks = 12;

$existingLoanId = Loan::getByApplicationId($appId);
if ($existingLoanId) {
    $existingLoan = Loan::getById($existingLoanId);
    $existingGroupId = $existingLoan ? (int) $existingLoan['group_id'] : 0;
    echo json_encode(['ok' => true, 'loan_id' => $existingLoanId, 'group_id' => $existingGroupId, 'already_created' => true]);
    exit;
}

if ($groupId <= 0) {
    if ($createGroupName === '' || $mfiId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'provide_group_or_create']);
        exit;
    }
    $groupId = Group::create($mfiId, $createGroupName);
    if (!$groupId) {
        echo json_encode(['ok' => false, 'error' => 'create_group_failed']);
        exit;
    }
}

Group::addMember($groupId, $userId);
if ($meetingName !== '') {
    Group::setMeetingLocation($groupId, $meetingName, $meetingDay, $meetingTime);
}

$loanId = Loan::create($appId, $userId, $groupId, $amount, $weeks);
if (!$loanId) {
    echo json_encode(['ok' => false, 'error' => 'create_loan_failed']);
    exit;
}

Application::updateStatus($appId, 'approved', get_admin_user_id());
Application::confirmGuarantorForApplication($appId);
AuditLog::add(get_admin_user_id(), 'Approved application', 'application', $appId, "Loan #{$loanId} created, group #{$groupId}");
echo json_encode(['ok' => true, 'loan_id' => $loanId, 'group_id' => $groupId]);
