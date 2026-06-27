<?php
function formatConversationDate($timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $date->getTimestamp();
    
    if ($diff < 3600) {
        return $date->format('g:i A');
    } elseif ($diff < 86400) {
        return 'Today';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } else {
        return $date->format('M j');
    }
}

function getInitials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getInquiryTypeDetails($type) {
    $types = [
        'tutoring' => ['icon' => 'fas fa-graduation-cap', 'label' => 'Tutoring', 'color' => 'blue'],
        'teacher-training' => ['icon' => 'fas fa-chalkboard-teacher', 'label' => 'Training', 'color' => 'indigo'],
        'admissions' => ['icon' => 'fas fa-door-open', 'label' => 'Admissions', 'color' => 'green'],
        'technical' => ['icon' => 'fas fa-terminal', 'label' => 'Technical', 'color' => 'purple'],
        'feedback' => ['icon' => 'fas fa-star', 'label' => 'Feedback', 'color' => 'orange'],
        'general' => ['icon' => 'fas fa-comment', 'label' => 'General', 'color' => 'gray']
    ];
    return $types[$type] ?? $types['general'];
}

function cleanPreviewText($text) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    return $text;
}