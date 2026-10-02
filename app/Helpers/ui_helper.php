<?php

use App\Models\NotificationModel;
use App\Models\SettingModel;

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        $all = model(SettingModel::class)->allSettings();

        return $all[$key] ?? $default;
    }
}

if (! function_exists('status_meta')) {
    /**
     * Label, colour and icon for every status used in the system.
     * Colour is never the only signal: every badge carries an icon and text.
     *
     * @return array{label: string, color: string, icon: string}
     */
    function status_meta(string $status): array
    {
        $map = [
            // Subject / clearance outcomes
            'PASSED'                        => ['Passed', 'green', 'bi-check-circle-fill'],
            'PENDING'                       => ['Pending', 'yellow', 'bi-hourglass-split'],
            'INC'                           => ['INC', 'orange', 'bi-exclamation-triangle-fill'],
            'FAILED'                        => ['Failed', 'red', 'bi-x-circle-fill'],
            'RE_ENROLLMENT_REQUIRED'        => ['Re-enrollment required', 'red', 'bi-arrow-repeat'],
            'RE_ENROLLMENT_RECEIPT_PENDING' => ['Re-enrollment receipt under review', 'blue', 'bi-eye-fill'],
            'RE_ENROLLMENT_APPROVED'        => ['Re-enrollment confirmed', 'green', 'bi-patch-check-fill'],
            'SIGNED'                        => ['Signed', 'green', 'bi-pen-fill'],
            'AWAITING_SIGNATURE'            => ['Awaiting signature', 'yellow', 'bi-pen'],
            // Receipts
            'submitted'                     => ['Submitted', 'yellow', 'bi-cloud-arrow-up-fill'],
            'under_review'                  => ['Under review', 'blue', 'bi-eye-fill'],
            'approved'                      => ['Approved', 'green', 'bi-check-circle-fill'],
            'reupload_required'             => ['Re-upload required', 'orange', 'bi-arrow-counterclockwise'],
            'not_submitted'                 => ['Not submitted', 'gray', 'bi-dash-circle'],
            // Clearance
            'not_started'                   => ['Not started', 'gray', 'bi-circle'],
            'awaiting_receipt'              => ['Awaiting receipt', 'yellow', 'bi-receipt'],
            'receipt_review'                => ['Receipt under review', 'blue', 'bi-eye-fill'],
            'in_progress'                   => ['In progress', 'blue', 'bi-arrow-right-circle-fill'],
            'incomplete'                    => ['Clearance incomplete', 'orange', 'bi-exclamation-circle-fill'],
            'completed'                     => ['Completed', 'green', 'bi-patch-check-fill'],
            // INC requirements
            'pending'                       => ['Pending', 'yellow', 'bi-hourglass-split'],
            'reported'                      => ['Awaiting verification', 'blue', 'bi-send-check-fill'],
            'verified'                      => ['Verified', 'green', 'bi-check-circle-fill'],
            // Eligibility
            'ELIGIBLE'                      => ['Eligible', 'green', 'bi-check-circle-fill'],
            'NOT_ELIGIBLE'                  => ['Not yet eligible', 'red', 'bi-exclamation-triangle-fill'],
            // Accounts / terms
            'pending_approval'              => ['Pending approval', 'yellow', 'bi-person-exclamation'],
            'active'                        => ['Active', 'green', 'bi-check-circle'],
            'inactive'                      => ['Inactive', 'gray', 'bi-slash-circle'],
            'graduated'                     => ['Graduated', 'blue', 'bi-mortarboard'],
            'open'                          => ['Open', 'green', 'bi-unlock-fill'],
            'upcoming'                      => ['Upcoming', 'yellow', 'bi-calendar-event'],
            'closed'                        => ['Closed', 'gray', 'bi-lock-fill'],
            'enrolled'                      => ['Enrolled', 'green', 'bi-check-circle'],
            'dropped'                       => ['Dropped', 'gray', 'bi-dash-circle'],
        ];

        [$label, $color, $icon] = $map[$status] ?? [ucwords(strtolower(str_replace('_', ' ', $status))), 'gray', 'bi-circle'];

        return ['label' => $label, 'color' => $color, 'icon' => $icon];
    }
}

if (! function_exists('status_badge')) {
    function status_badge(string $status, ?string $label = null, string $extra = ''): string
    {
        $m = status_meta($status);

        return '<span class="badge-status badge-' . $m['color'] . ' ' . esc($extra, 'attr') . '">'
            . '<i class="bi ' . $m['icon'] . '" aria-hidden="true"></i>'
            . esc($label ?? $m['label']) . '</span>';
    }
}

if (! function_exists('subject_status_key')) {
    /**
     * The single status to show for a clearance subject, combining the teacher's
     * decision, signature and any re-enrollment progress.
     */
    function subject_status_key(array $cs): string
    {
        if ($cs['status'] === 'FAILED' && ! empty($cs['reenrollment_status'])) {
            return $cs['reenrollment_status'];
        }

        return $cs['status'];
    }
}

if (! function_exists('semester_label')) {
    function semester_label(string $semester): string
    {
        return match ($semester) {
            '1st'    => '1st Semester',
            '2nd'    => '2nd Semester',
            'summer' => 'Summer Term',
            default  => $semester,
        };
    }
}

if (! function_exists('term_label')) {
    /**
     * "1st Semester AY 2026–2027" from a row that has semester + school_year.
     */
    function term_label(?array $term, bool $short = false): string
    {
        if (! $term) {
            return 'No active term';
        }
        $year = str_replace('-', '–', (string) ($term['school_year'] ?? ''));

        return $short
            ? ($term['semester'] === 'summer' ? 'Summer' : $term['semester'] . ' Sem') . ' ' . $year
            : semester_label($term['semester']) . ' AY ' . $year;
    }
}

if (! function_exists('person_name')) {
    function person_name(array $row, bool $lastFirst = false): string
    {
        $first  = trim((string) ($row['first_name'] ?? ''));
        $last   = trim((string) ($row['last_name'] ?? ''));
        $middle = trim((string) ($row['middle_name'] ?? ''));
        // Middle name shown as an initial: "Jasrylle Nicole B. Botobara".
        $mi = $middle !== '' ? mb_strtoupper(mb_substr($middle, 0, 1)) . '.' : '';
        $firstMi = trim($first . ' ' . $mi);

        return $lastFirst ? trim($last . ', ' . $firstMi, ', ') : trim($firstMi . ' ' . $last);
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim(preg_replace('/^(Prof\.|Dr\.|Mr\.|Ms\.|Mrs\.|Engr\.)\s*/i', '', $name)));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}

if (! function_exists('year_level_label')) {
    function year_level_label(int|string|null $level): string
    {
        $n = (int) $level;

        return match ($n) {
            1       => '1st Year',
            2       => '2nd Year',
            3       => '3rd Year',
            default => $n . 'th Year',
        };
    }
}

if (! function_exists('peso')) {
    function peso(float|string|null $amount): string
    {
        return '₱' . number_format((float) $amount, 2);
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(?string $value, string $format = 'j M Y'): string
    {
        return $value ? date($format, strtotime($value)) : '—';
    }
}

if (! function_exists('fmt_datetime')) {
    function fmt_datetime(?string $value): string
    {
        return $value ? date('j M Y, g:i A', strtotime($value)) : '—';
    }
}

if (! function_exists('time_ago')) {
    function time_ago(?string $value): string
    {
        if (! $value) {
            return '—';
        }
        $diff = time() - strtotime($value);

        return match (true) {
            $diff < 60     => 'just now',
            $diff < 3600   => floor($diff / 60) . ' min ago',
            $diff < 86400  => floor($diff / 3600) . ' hr' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago',
            $diff < 604800 => floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago',
            default        => fmt_date($value),
        };
    }
}

if (! function_exists('file_size_label')) {
    function file_size_label(int|string $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
    }
}

if (! function_exists('reupload_reasons')) {
    /** Preset reasons an admin can pick when requesting a receipt re-upload. */
    function reupload_reasons(): array
    {
        return [
            'blurry'     => 'Image is blurry',
            'unreadable' => 'Receipt information cannot be read',
            'wrong'      => 'Wrong receipt uploaded',
            'incomplete' => 'Receipt is incomplete',
            'mismatch'   => 'Details do not match the student record',
            'other'      => 'Other',
        ];
    }
}

if (! function_exists('receipt_checklist_items')) {
    function receipt_checklist_items(): array
    {
        return [
            'name'     => 'Student name matches record',
            'or'       => 'Official receipt (OR) number is visible',
            'amount'   => 'Amount matches assessment',
            'semester' => 'Correct semester indicated',
        ];
    }
}

if (! function_exists('nav_is')) {
    /**
     * True when the current path matches one of the given route patterns.
     */
    function nav_is(string ...$patterns): bool
    {
        $path = trim(uri_string(), '/');
        foreach ($patterns as $pattern) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote(trim($pattern, '/'), '#')) . '$#';
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('nav_items')) {
    /**
     * Sidebar navigation for each role. Each item: label, icon, url, match patterns, children.
     */
    function nav_items(string $role): array
    {
        return match ($role) {
            'student' => [
                ['Dashboard', 'bi-grid-1x2', 'student/dashboard', ['student/dashboard']],
                ['Clearance', 'bi-clipboard-check', 'student/receipt', ['student/receipt', 'student/card', 'student/subjects'], [
                    ['Tuition Receipt', 'student/receipt', ['student/receipt']],
                    ['Clearance Card', 'student/card', ['student/card']],
                    ['Subject Status', 'student/subjects', ['student/subjects']],
                ]],
                ['INC Requirements', 'bi-exclamation-diamond', 'student/inc', ['student/inc*']],
                ['Re-enrollment', 'bi-arrow-repeat', 'student/reenrollment', ['student/reenrollment*']],
                ['Clearance History', 'bi-clock-history', 'student/history', ['student/history*']],
                ['Notifications', 'bi-bell', 'notifications', ['notifications*']],
                ['Profile', 'bi-person-circle', 'profile', ['profile*']],
            ],
            'teacher' => [
                ['Dashboard', 'bi-grid-1x2', 'teacher/dashboard', ['teacher/dashboard']],
                ['My Subjects', 'bi-journal-bookmark', 'teacher/subjects', ['teacher/subjects']],
                ['Students', 'bi-people', 'teacher/students', ['teacher/students*']],
                ['Clearance', 'bi-clipboard-check', 'teacher/clearance', ['teacher/clearance*', 'teacher/subjects/*']],
                ['INC Requirements', 'bi-exclamation-diamond', 'teacher/inc', ['teacher/inc*']],
                ['Notifications', 'bi-bell', 'notifications', ['notifications*']],
                ['Profile', 'bi-person-circle', 'profile', ['profile*']],
            ],
            'admin' => [
                ['Dashboard', 'bi-grid-1x2', 'admin/dashboard', ['admin/dashboard']],
                ['Students', 'bi-mortarboard', 'admin/students', ['admin/students*']],
                ['Teachers', 'bi-person-badge', 'admin/teachers', ['admin/teachers*']],
                ['Subjects', 'bi-journal-bookmark', 'admin/subjects', ['admin/subjects*', 'admin/offerings*']],
                ['School Terms', 'bi-calendar3', 'admin/terms', ['admin/terms*']],
                ['Tuition Receipts', 'bi-receipt', 'admin/receipts', ['admin/receipts*']],
                ['Clearances', 'bi-clipboard-check', 'admin/clearances', ['admin/clearances*']],
                ['Re-enrollment', 'bi-arrow-repeat', 'admin/reenrollment', ['admin/reenrollment*']],
                ['Reports', 'bi-bar-chart-line', 'admin/reports', ['admin/reports*']],
                ['Notifications', 'bi-bell', 'notifications', ['notifications*']],
                ['Settings', 'bi-gear', 'admin/settings', ['admin/settings*']],
            ],
            default => [],
        };
    }
}

if (! function_exists('notification_summary')) {
    /** @return array{unread: int, items: list<array>} */
    function notification_summary(int $userId): array
    {
        $model = model(NotificationModel::class);

        return ['unread' => $model->unreadCount($userId), 'items' => $model->latestFor($userId, 6)];
    }
}

if (! function_exists('notification_icon')) {
    function notification_icon(string $type): string
    {
        return match (true) {
            str_contains($type, 'approved'), str_contains($type, 'completed'), str_contains($type, 'eligible'), str_contains($type, 'passed') => 'bi-check-circle-fill text-[#1f6a3f]',
            str_contains($type, 'reupload'), str_contains($type, 'inc')    => 'bi-exclamation-triangle-fill text-[#a4521a]',
            str_contains($type, 'failed')                                  => 'bi-x-circle-fill text-[#a3362a]',
            default                                                        => 'bi-bell-fill text-brand-600',
        };
    }
}

if (! function_exists('old_or')) {
    /** old() input with a fallback to the stored row value. */
    function old_or(string $key, ?array $row, string $default = ''): string
    {
        return (string) old($key, $row[$key] ?? $default);
    }
}
