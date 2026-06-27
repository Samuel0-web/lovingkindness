<?php
declare(strict_types=1);
class DashboardService {
    public static function getStats(PDO $db): array {
        $stats = [];

        $stats['totalUsers'] = (int)$db
            ->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $stats['totalMessages'] = (int)$db->query("SELECT COUNT(*)
            FROM contact_messages
            WHERE deleted_at IS NULL
        ")
        ->fetchColumn();

        $stats['newEnrollmentsThisMonth'] = (int)$db->query("SELECT COUNT(*) FROM enrollments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ")->fetchColumn();

        $row = $db->query("SELECT COUNT(*) total, COALESCE(SUM(status='pending'),0) pending,
                COALESCE(SUM(status='contacted'),0) contacted,
                COALESCE(SUM(status='consultation_booked'),0) booked,
                COALESCE(SUM(status='enrolled'),0) enrolled,
                COALESCE(SUM(status='rejected'),0) rejected
            FROM enrollments
        ")->fetch(PDO::FETCH_ASSOC);

        $stats['totalEnrollments'] = (int)$row['total'];
        $stats['pendingEnrollments'] = (int)$row['pending'];
        $stats['contactedEnrollments'] = (int)$row['contacted'];
        $stats['consultationBooked'] = (int)$row['booked'];
        $stats['enrolledStudents'] = (int)$row['enrolled'];
        $stats['rejectedEnrollments'] = (int)$row['rejected'];

        $stats['conversionRate'] =
            $stats['totalEnrollments'] > 0
                ? round(
                    ($stats['enrolledStudents'] / $stats['totalEnrollments']) * 100,
                    1
                )
                : 0;

        $stats['unreadMessages'] = (int)$db
            ->query("SELECT COUNT(*) FROM contact_messages WHERE status='unread'
                AND deleted_at IS NULL
            ")
            ->fetchColumn();

        $stats['repliedMessages'] = (int)$db
            ->query("SELECT COUNT(*) FROM contact_messages WHERE status='replied'
                AND deleted_at IS NULL
            ")
            ->fetchColumn();

        return $stats;
    }

    public static function getRecentEnrollments(PDO $db, int $limit = 5): array {
        $stmt = $db->prepare("SELECT id, full_name, program, country, status, created_at
            FROM enrollments ORDER BY created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRecentMessages(PDO $db, int $limit = 10): array {
        $stmt = $db->prepare("SELECT id, full_name, inquiry_type, subject, status,
            created_at FROM contact_messages WHERE deleted_at IS NULL
            ORDER BY created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getMonthlyEnrollmentData(PDO $db): array {
            $stmt = $db->query("SELECT DATE_FORMAT(created_at,'%Y-%m') month_key,
            DATE_FORMAT(created_at,'%b %Y') month_label, COUNT(*) total FROM enrollments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month_key, month_label
            ORDER BY month_key
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'labels' => array_column($rows, 'month_label'),
            'counts' => array_map(
                'intval',
                array_column($rows, 'total')
            )
        ];
    }

    public static function getProgramDistribution(PDO $db): array {
        $stmt = $db->query("SELECT program, COUNT(*) total FROM enrollments GROUP BY program");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'labels' => array_map(
                fn($program) => ucwords(str_replace('_', ' ', $program)),
                array_column($rows, 'program')
            ),
            'counts' => array_map(
                'intval',
                array_column($rows, 'total')
            )
        ];
    }

    public static function getStatusPercentages(array $stats): array {
        $total = max(1, $stats['totalEnrollments']);

        return [
            'pending' => round(($stats['pendingEnrollments'] / $total) * 100),
            'contacted' => round(($stats['contactedEnrollments'] / $total) * 100),
            'consultation_booked' => round(($stats['consultationBooked'] / $total) * 100),
            'enrolled' => round(($stats['enrolledStudents'] / $total) * 100),
            'rejected' => round(($stats['rejectedEnrollments'] / $total) * 100),
        ];
    }

    public static function getWeeklyGrowth(PDO $db): array {
        $thisWeek = (int)$db->query("
            SELECT COUNT(*) 
            FROM enrollments 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();

        $lastWeek = (int)$db->query("
            SELECT COUNT(*) 
            FROM enrollments 
            WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY)
            AND DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();

        $growth = $lastWeek > 0
            ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1)
            : 0;

        return [
            'thisWeek' => $thisWeek,
            'lastWeek' => $lastWeek,
            'growth' => $growth
        ];
    }

    public static function getTopCountry(PDO $db): array {
        $stmt = $db->query("
            SELECT country, COUNT(*) as total
            FROM enrollments
            GROUP BY country
            ORDER BY total DESC
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'country' => 'N/A',
            'total' => 0
        ];
    }

    public static function getCountriesReached(PDO $db): int {
        return (int)$db->query("
            SELECT COUNT(DISTINCT country)
            FROM enrollments
            WHERE country IS NOT NULL
            AND country <> ''
        ")->fetchColumn();
    }
}