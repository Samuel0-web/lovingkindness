<?php
declare(strict_types=1);
class ActivityLogService {
    public static function log(PDO $db, int $adminId, string $adminName, string $action,
        string $description, ?string $entityType = null, ?int $entityId = null
    ): void {
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id,admin_name, action, entity_type,
                entity_id, description, ip_address
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $adminId,
            $adminName,
            $action,
            $entityType,
            $entityId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    public static function getRecent(PDO $db, int $limit = 20): array {
        $stmt = $db->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRecentActivities(PDO $db, int $limit = 10): array {
        $stmt = $db->prepare("SELECT admin_name, action, description, entity_type, created_at
            FROM activity_logs ORDER BY created_at DESC LIMIT ?
        ");

        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTotalLogs(PDO $db): int {
        return (int)$db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    }

    public static function getAuditLogs(PDO $db, int $limit = 100, int $offset = 0): array {
        $stmt = $db->prepare("SELECT id, admin_id, admin_name, action, entity_type, entity_id,
            description, ip_address, created_at FROM activity_logs ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getGroupedAuditLogs(PDO $db, int $limit = 100, int $offset = 0): array {
        $logs = self::getAuditLogs($db, $limit, $offset);
        $groups = [];

        foreach ($logs as $log) {
            $date = new DateTime($log['created_at']);
            $today = new DateTime('today');
            $yesterday = new DateTime('yesterday');

            if ($date >= $today) {
                $group = 'Today';
            } elseif ($date >= $yesterday) {
                $group = 'Yesterday';
            } else {
                $group = $date->format('F j, Y');
            }

            $log['time'] = self::timeAgo($log['created_at']);

            $groups[$group][] = $log;
        }

        return $groups;
    }

    private static function timeAgo(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) { return $diff . 's ago'; }
        if ($diff < 3600) { return floor($diff / 60) . 'm ago'; }
        if ($diff < 86400) { return floor($diff / 3600) . 'h ago'; }
        return floor($diff / 86400) . 'd ago';
    }

    public static function getAdmins(PDO $db): array {
        $stmt = $db->query("SELECT DISTINCT admin_name FROM activity_logs ORDER BY admin_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getActions(PDO $db): array{
        $stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}