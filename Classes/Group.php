<?php
/**
 * TrustLoan – Groups (under MFI), members, meeting location.
 */
if (!defined('TRUSTLOAN_GROUP_LOADED')) {
    define('TRUSTLOAN_GROUP_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class Group {

    public static function create($mfiId, $name) {
        $mfiId = (int) $mfiId;
        $name = trim((string) $name);
        if ($mfiId <= 0 || $name === '') return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO groups (mfi_id, name, repayment_status) VALUES (?, ?, ?)');
        $stmt->execute([$mfiId, $name, 'good']);
        return (int) $pdo->lastInsertId();
    }

    public static function getById($groupId) {
        $id = (int) $groupId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT g.*, m.name AS mfi_name FROM groups g JOIN mfis m ON m.id = g.mfi_id WHERE g.id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByMfi($mfiId) {
        $mfiId = (int) $mfiId;
        if ($mfiId <= 0) return [];
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS member_count FROM groups g WHERE g.mfi_id = ? ORDER BY g.name');
        $stmt->execute([$mfiId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllForAdmin() {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT g.*, m.name AS mfi_name, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS member_count FROM groups g JOIN mfis m ON m.id = g.mfi_id ORDER BY m.name, g.name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function getByIdWithMembers($groupId) {
        $group = self::getById($groupId);
        if (!$group) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT gm.*, u.full_name, u.phone FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY gm.role DESC, gm.joined_at');
        $stmt->execute([$groupId]);
        $group['members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT * FROM meeting_locations WHERE group_id = ? LIMIT 1');
        $stmt->execute([$groupId]);
        $group['meeting_location'] = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group;
    }

    /**
     * Add borrower to group. Uses INSERT IGNORE so duplicates are OK — same person may already
     * be in the group from an earlier loan while this is the first approval of a new application.
     */
    public static function addMember($groupId, $userId) {
        $gid = (int) $groupId;
        $uid = (int) $userId;
        if ($gid <= 0 || $uid <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
        $stmt->execute([$gid, $uid, 'member']);
        return true;
    }

    public static function setHead($groupId, $userId) {
        $gid = (int) $groupId;
        $uid = (int) $userId;
        if ($gid <= 0 || $uid <= 0) return false;
        $pdo = DB::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE group_members SET role = ? WHERE group_id = ?');
            $stmt->execute(['member', $gid]);
            $stmt = $pdo->prepare('UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?');
            $stmt->execute(['head', $gid, $uid]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function updateRepaymentStatus($groupId, $status) {
        $gid = (int) $groupId;
        if ($gid <= 0) return false;
        if (!in_array($status, ['good', 'watch', 'bad'], true)) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE groups SET repayment_status = ? WHERE id = ?');
        $stmt->execute([$status, $gid]);
        return true;
    }

    public static function updateName($groupId, $name) {
        $gid = (int) $groupId;
        $name = trim((string) $name);
        if ($gid <= 0 || $name === '') return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE groups SET name = ? WHERE id = ?');
        $stmt->execute([$name, $gid]);
        return true;
    }

    /**
     * Set or create meeting location for group.
     */
    public static function setMeetingLocation($groupId, $name, $meetingDay, $meetingTime) {
        $gid = (int) $groupId;
        $name = trim((string) $name);
        $day = (int) $meetingDay;
        if ($gid <= 0 || $name === '') return false;
        if ($day < 0 || $day > 6) $day = 3;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM meeting_locations WHERE group_id = ? LIMIT 1');
        $stmt->execute([$gid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stmt = $pdo->prepare('UPDATE meeting_locations SET name = ?, meeting_day = ?, meeting_time = ? WHERE group_id = ?');
            $stmt->execute([$name, $day, $meetingTime ?: '09:00:00', $gid]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO meeting_locations (group_id, name, meeting_day, meeting_time) VALUES (?, ?, ?, ?)');
            $stmt->execute([$gid, $name, $day, $meetingTime ?: '09:00:00']);
        }
        return true;
    }
}
