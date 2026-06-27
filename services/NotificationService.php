<?php
declare(strict_types=1);
class NotificationService {
    public static function create(
        PDO $pdo, int $userId, string $type, string $title, string $message, ?int $actorId = null,
        ?string $entityType = null, ?int $entityId = null, ?array $data = null): int {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, title,
                message, entity_type, entity_id, data
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$userId, $actorId, $type, $title, $message, $entityType, $entityId,
            $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function notifyAdmins(PDO $pdo, array $roles, string $type, string $title,
        string $message, ?int $actorId = null, ?string $entityType = null, ?int $entityId = null,
        ?array $data = null): void {

        if (empty($roles)) { return; }
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $pdo->prepare("SELECT id FROM users  WHERE role IN ($placeholders)");
        $stmt->execute($roles);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($userIds as $userId) {
            self::create($pdo, (int) $userId, $type, $title, $message, $actorId, $entityType,
                $entityId, $data
            );
        }
    }

    public static function getUnseenCount(PDO $pdo, int $userId): int {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications
            WHERE user_id = ? AND is_seen = 0"
        );

        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markSeen(PDO $pdo, int $userId): bool {
        $stmt = $pdo->prepare("UPDATE notifications SET is_seen = 1
            WHERE user_id = ? AND is_seen = 0"
        );

        return $stmt->execute([$userId]);
    }

    public static function getAll(PDO $pdo, int $userId): array {
        $stmt = $pdo->prepare("SELECT id, type, title, message, is_read, is_seen, created_at, entity_type,
            entity_id FROM notifications WHERE user_id = ? ORDER BY created_at DESC
        ");

        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRecent(PDO $pdo, int $userId, int $limit = 20): array {
        $stmt = $pdo->prepare("SELECT id, type, title, message, is_read, is_seen, created_at, entity_type,
            entity_id FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markRead(PDO $pdo, int $notificationId, int $userId): bool {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllRead(PDO $pdo, int $userId): bool {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    }

    public static function markUnread(PDO $pdo, int $notificationId, int $userId): bool {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 0, read_at = NULL  WHERE id = ?
            AND user_id = ?
        ");

        return $stmt->execute([$notificationId, $userId]);
    }

    public static function delete(PDO $pdo, int $notificationId, int $userId): bool {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    public static function deleteAll(PDO $pdo, int $userId): bool {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public static function getSince(PDO $pdo, int $userId, int $lastId): array {
        $stmt = $pdo->prepare("SELECT id, type, title, message, is_read, is_seen, created_at,
                entity_type, entity_id FROM notifications WHERE user_id = ? AND id > ?
            ORDER BY id ASC
        ");

        $stmt->execute([$userId, $lastId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}