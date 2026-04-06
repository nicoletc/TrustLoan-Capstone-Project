<?php
/**
 * TrustLoan – Admin: approve application. Assign group (or create), set meeting, create loan + repayments, audit.
 */
ini_set('display_errors', '0');

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/Group.php';
require_once __DIR__ . '/../Classes/Loan.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

try {

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$appId = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
$postedGroupId = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
$groupId = $postedGroupId;
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
if (!$app) {
    echo json_encode(['ok' => false, 'error' => 'Application not found.']);
    exit;
}

$userId = (int) $app['user_id'];
$amount = (float) $app['requested_amount'];
$weeks = (int) ($app['repayment_weeks'] ?? 12);
if ($weeks <= 0) {
    $weeks = 12;
}

// Idempotent: if this application already has a loan, succeed (avoids error after status was set to approved).
$existingLoanId = Loan::getByApplicationId($appId);
if ($existingLoanId) {
    $existingLoan = Loan::getById($existingLoanId);
    $existingGroupId = $existingLoan ? (int) $existingLoan['group_id'] : 0;
    echo json_encode(['ok' => true, 'loan_id' => $existingLoanId, 'group_id' => $existingGroupId, 'already_created' => true]);
    exit;
}

$status = strtolower(trim((string) ($app['status'] ?? '')));
if ($status !== 'new' && $status !== 'in_progress') {
    if ($status === 'rejected') {
        echo json_encode(['ok' => false, 'error' => 'This application was rejected. It cannot be approved.']);
        exit;
    }
    if ($status === 'approved') {
        echo json_encode(['ok' => false, 'error' => 'This application is already marked approved, but no loan record was found. Please contact support or check the Loans screen.']);
        exit;
    }
    echo json_encode(['ok' => false, 'error' => 'This application cannot be approved in its current state.']);
    exit;
}

if ($groupId <= 0) {
    if ($createGroupName === '' || $mfiId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Choose an existing group ID or enter a new group name and MFI ID.']);
        exit;
    }
    $groupId = Group::create($mfiId, $createGroupName);
    if (!$groupId) {
        echo json_encode(['ok' => false, 'error' => 'Could not create the group. Check the MFI ID and try again.']);
        exit;
    }
}

Group::addMember($groupId, $userId);
// Meeting from this form only applies when creating a new group; existing groups keep their saved location.
if ($meetingName !== '' && $postedGroupId <= 0) {
    Group::setMeetingLocation($groupId, $meetingName, $meetingDay, $meetingTime);
}

$loanId = Loan::create($appId, $userId, $groupId, $amount, $weeks);
if (!$loanId) {
    echo json_encode(['ok' => false, 'error' => 'Could not create the loan or repayment schedule. Check the database and try again.']);
    exit;
}

Application::updateStatus($appId, 'approved', get_admin_user_id());
Application::confirmGuarantorForApplication($appId);
AuditLog::add(get_admin_user_id(), 'Approved application', 'application', $appId, "Loan #{$loanId} created, group #{$groupId}");
echo json_encode(['ok' => true, 'loan_id' => $loanId, 'group_id' => $groupId]);

} catch (Throwable $e) {
    error_log('approve_application_action: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'Approval failed due to a server or database error. Restart Apache after schema changes. Check the PHP error log for the full message.';
    if (defined('TRUSTLOAN_DEV_MODE') && TRUSTLOAN_DEV_MODE) {
        $msg .= ' Technical detail: ' . $e->getMessage();
    }
    echo json_encode(['ok' => false, 'error' => $msg]);
}
