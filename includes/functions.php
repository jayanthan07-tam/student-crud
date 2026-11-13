<?php
// includes/functions.php
// Reusable Business Logic and Helper Functions
// Academic Operations System - Student CRUD Application

/**
 * Calculate age dynamically from Date of Birth using PHP DateTime
 * Do not store age in the database
 * 
 * @param string $dob Date of Birth in YYYY-MM-DD or parseable format
 * @return int Age in years
 */
function calculateAge($dob) {
    if (empty($dob)) {
        return 0;
    }
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        return $birthDate->diff($today)->y;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Calculate student dynamic percentage according to academic rules:
 * - Percentage = (Total Obtained Marks / Total Maximum Marks) * 100
 * - Excludes absent subjects from obtained marks and maximum marks
 * - If all registered subjects are absent, returns 'Absent'
 * - If no subjects registered or no marks entered, returns null
 * - Missing marks are not treated as zero
 * 
 * @param float|null $obtainedSum Sum of marks in attended subjects
 * @param float|null $attendedMaxSum Sum of maximum marks for attended subjects
 * @param int $presentCount Count of attended subjects
 * @param int $absentCount Count of absent subjects
 * @param int $totalCount Total registered subjects
 * @return float|string|null Numeric percentage (float), 'Absent' (string), or null
 */
function calculateStudentPercentage($obtainedSum, $attendedMaxSum, $presentCount, $absentCount, $totalCount, $incompleteCount = 0, $expectedSubjectCount = null) {
    if ($totalCount === 0 || ($presentCount === 0 && $absentCount === 0 && $incompleteCount === 0)) {
        return null;
    }
    
    // If student was absent in all registered subjects (and no incomplete Present rows)
    if ($absentCount > 0 && $presentCount === 0 && $incompleteCount === 0) {
        if ($expectedSubjectCount === null || $absentCount >= $expectedSubjectCount) {
            return 'Absent';
        }
        return null;
    }

    // If any Present subject is missing marks or fewer subjects than expected curriculum are recorded
    if ($incompleteCount > 0 || ($expectedSubjectCount !== null && $totalCount < $expectedSubjectCount)) {
        return null;
    }
    
    // Attended subjects calculation
    if ($presentCount > 0 && $attendedMaxSum > 0) {
        $pct = ($obtainedSum / $attendedMaxSum) * 100;
        return round($pct, 2);
    }
    
    return null;
}

/**
 * Format percentage value for presentation with semantic badge styling and progress bar
 * 
 * @param float|string|null $percentage
 * @return array ['text' => string, 'class' => string, 'numeric' => float|null]
 */
function formatPercentageBadge($percentage) {
    if ($percentage === 'Absent') {
        return [
            'text'    => 'Absent',
            'class'   => 'badge-danger',
            'numeric' => null
        ];
    }
    
    if ($percentage === null || $percentage === '') {
        return [
            'text'    => 'Not Available',
            'class'   => 'badge-neutral',
            'numeric' => null
        ];
    }
    
    $val = (float)$percentage;
    $text = number_format($val, 2) . '%';
    
    if ($val >= 70.0) {
        $class = 'badge-success'; // High distinction
    } elseif ($val >= 60.0) {
        $class = 'badge-primary'; // First Class
    } elseif ($val >= 50.0) {
        $class = 'badge-teal';    // Second Class
    } elseif ($val >= 40.0) {
        $class = 'badge-warning'; // Pass Class
    } else {
        $class = 'badge-danger';  // Academic Review (< 40%)
    }
    
    return [
        'text'    => $text,
        'class'   => $class,
        'numeric' => $val
    ];
}

/**
 * Generate 2-character uppercase initials for student avatars
 * 
 * @param string $name
 * @return string
 */
function getStudentInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        if (!empty($p)) {
            $initials .= strtoupper($p[0]);
        }
    }
    return substr($initials, 0, 2) ?: 'ST';
}

/**
 * Fetch all students with their dynamically calculated percentages
 * 
 * @param PDO $pdo
 * @return array Array of students with calculated performance metrics
 */
function getAllStudentsWithPercentages(PDO $pdo) {
    $expectedSubjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();

    $sql = "
        SELECT 
            s.id,
            s.student_name,
            s.register_no,
            s.dob,
            s.mobile_number,
            COUNT(sub.id) AS total_subjects_registered,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) AS present_count,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NULL THEN 1 END) AS incomplete_count,
            COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) AS absent_count,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) AS total_obtained,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END) AS attended_maximum_marks
        FROM students s
        LEFT JOIN student_marks sm ON s.id = sm.student_id
        LEFT JOIN subjects sub ON sm.subject_id = sub.id
        GROUP BY s.id, s.student_name, s.register_no, s.dob, s.mobile_number
        ORDER BY s.id ASC
    ";
    
    $stmt = $pdo->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    foreach ($records as $row) {
        $pct = calculateStudentPercentage(
            $row['total_obtained'],
            $row['attended_maximum_marks'],
            (int)$row['present_count'],
            (int)$row['absent_count'],
            (int)$row['total_subjects_registered'],
            (int)$row['incomplete_count'],
            $expectedSubjects > 0 ? $expectedSubjects : null
        );
        
        $row['calculated_percentage'] = $pct;
        $row['age'] = calculateAge($row['dob']);
        $results[] = $row;
    }
    
    return $results;
}

/**
 * Fetch complete dashboard aggregate statistics from MySQL
 * 
 * @param PDO $pdo
 * @return array
 */
function getDashboardMetrics(PDO $pdo) {
    // 1. Total Students
    $stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM students");
    $totalStudents = (int)($stmtTotal->fetch()['total'] ?? 0);
    
    // 2. Fetch all student percentages to compute statistics and distribution
    $allStudents = getAllStudentsWithPercentages($pdo);
    
    $validPercentages = [];
    $distribution = [
        '0-40%'   => 0,
        '40-50%'  => 0,
        '50-60%'  => 0,
        '60-70%'  => 0,
        '70-80%'  => 0,
        '80-100%' => 0,
    ];
    
    foreach ($allStudents as $student) {
        $pct = $student['calculated_percentage'];
        if ($pct !== null && is_numeric($pct)) {
            $numPct = (float)$pct;
            $validPercentages[] = $numPct;
            
            if ($numPct < 40) {
                $distribution['0-40%']++;
            } elseif ($numPct < 50) {
                $distribution['40-50%']++;
            } elseif ($numPct < 60) {
                $distribution['50-60%']++;
            } elseif ($numPct < 70) {
                $distribution['60-70%']++;
            } elseif ($numPct < 80) {
                $distribution['70-80%']++;
            } else {
                $distribution['80-100%']++;
            }
        }
    }
    
    // Compute aggregates
    $validCount = count($validPercentages);
    $avgPercentage = $validCount > 0 ? round(array_sum($validPercentages) / $validCount, 2) : null;
    $highestPercentage = $validCount > 0 ? max($validPercentages) : null;
    $lowestPercentage = $validCount > 0 ? min($validPercentages) : null;
    
    // 3. Fetch latest 5 students for the Recent Students table
    $expectedSubjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $sqlRecent = "
        SELECT 
            s.id,
            s.student_name,
            s.register_no,
            s.dob,
            s.mobile_number,
            COUNT(sub.id) AS total_subjects_registered,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) AS present_count,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NULL THEN 1 END) AS incomplete_count,
            COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) AS absent_count,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) AS total_obtained,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END) AS attended_maximum_marks
        FROM students s
        LEFT JOIN student_marks sm ON s.id = sm.student_id
        LEFT JOIN subjects sub ON sm.subject_id = sub.id
        GROUP BY s.id, s.student_name, s.register_no, s.dob, s.mobile_number
        ORDER BY s.id DESC
        LIMIT 5
    ";
    $stmtRecent = $pdo->query($sqlRecent);
    $recentRaw = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    
    $recentStudents = [];
    foreach ($recentRaw as $row) {
        $pct = calculateStudentPercentage(
            $row['total_obtained'],
            $row['attended_maximum_marks'],
            (int)$row['present_count'],
            (int)$row['absent_count'],
            (int)$row['total_subjects_registered'],
            (int)$row['incomplete_count'],
            $expectedSubjects > 0 ? $expectedSubjects : null
        );
        $row['calculated_percentage'] = $pct;
        $row['age'] = calculateAge($row['dob']);
        $recentStudents[] = $row;
    }
    
    return [
        'totalStudents'      => $totalStudents,
        'averagePercentage'  => $avgPercentage,
        'highestPercentage'  => $highestPercentage,
        'lowestPercentage'   => $lowestPercentage,
        'distribution'       => $distribution,
        'recentStudents'     => $recentStudents,
        'totalGraded'        => $validCount,
    ];
}

/**
 * Query students with server-side MySQL search, filtering, sorting, and pagination
 * 
 * @param PDO $pdo
 * @param array $params
 * @return array
 */
function getPaginatedStudents(PDO $pdo, array $params = []) {
    $search     = trim($params['search'] ?? '');
    $pctRange   = trim($params['pct_range'] ?? '');
    $ageFilter  = trim($params['age'] ?? '');
    $sort       = trim($params['sort'] ?? 'id_desc');
    $page       = max(1, (int)($params['page'] ?? 1));
    $limit      = in_array((int)($params['limit'] ?? 10), [10, 20, 50]) ? (int)$params['limit'] : 10;
    $offset     = ($page - 1) * $limit;

    $whereClauses = ['1=1'];
    $bindings = [];

    // Search filter across student_name, register_no, mobile_number
    if ($search !== '') {
        $whereClauses[] = "(s.student_name LIKE :search_name OR s.register_no LIKE :search_reg OR s.mobile_number LIKE :search_mob)";
        $searchPattern = '%' . $search . '%';
        $bindings[':search_name'] = $searchPattern;
        $bindings[':search_reg']  = $searchPattern;
        $bindings[':search_mob']  = $searchPattern;
    }

    // Age filter (calculated dynamically from DOB)
    if ($ageFilter !== '') {
        if ($ageFilter === 'under_18') {
            $whereClauses[] = "TIMESTAMPDIFF(YEAR, s.dob, CURDATE()) < 18";
        } elseif ($ageFilter === '18') {
            $whereClauses[] = "TIMESTAMPDIFF(YEAR, s.dob, CURDATE()) = 18";
        } elseif ($ageFilter === '19') {
            $whereClauses[] = "TIMESTAMPDIFF(YEAR, s.dob, CURDATE()) = 19";
        } elseif ($ageFilter === '20_plus') {
            $whereClauses[] = "TIMESTAMPDIFF(YEAR, s.dob, CURDATE()) >= 20";
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    // Having clause for calculated percentage
    $havingClauses = [];
    if ($pctRange !== '') {
        if ($pctRange === 'distinction') {
            $havingClauses[] = "(calculated_percentage IS NOT NULL AND calculated_percentage != 'Absent' AND (calculated_percentage + 0) >= 70)";
        } elseif ($pctRange === 'first_class') {
            $havingClauses[] = "(calculated_percentage IS NOT NULL AND calculated_percentage != 'Absent' AND (calculated_percentage + 0) >= 60 AND (calculated_percentage + 0) < 70)";
        } elseif ($pctRange === 'second_class') {
            $havingClauses[] = "(calculated_percentage IS NOT NULL AND calculated_percentage != 'Absent' AND (calculated_percentage + 0) >= 50 AND (calculated_percentage + 0) < 60)";
        } elseif ($pctRange === 'pass') {
            $havingClauses[] = "(calculated_percentage IS NOT NULL AND calculated_percentage != 'Absent' AND (calculated_percentage + 0) >= 40 AND (calculated_percentage + 0) < 50)";
        } elseif ($pctRange === 'below_40') {
            $havingClauses[] = "(calculated_percentage IS NOT NULL AND calculated_percentage != 'Absent' AND (calculated_percentage + 0) < 40)";
        } elseif ($pctRange === 'absent') {
            $havingClauses[] = "calculated_percentage = 'Absent'";
        } elseif ($pctRange === 'missing') {
            $havingClauses[] = "calculated_percentage IS NULL";
        }
    }
    $havingSql = !empty($havingClauses) ? 'HAVING ' . implode(' AND ', $havingClauses) : '';

    // Sort order validation (strict whitelist)
    switch ($sort) {
        case 'name_asc':
            $orderSql = "ORDER BY s.student_name ASC";
            break;
        case 'name_desc':
            $orderSql = "ORDER BY s.student_name DESC";
            break;
        case 'pct_desc':
            $orderSql = "ORDER BY CASE WHEN calculated_percentage = 'Absent' THEN -1 WHEN calculated_percentage IS NULL THEN -2 ELSE (calculated_percentage + 0) END DESC, s.student_name ASC";
            break;
        case 'pct_asc':
            $orderSql = "ORDER BY CASE WHEN calculated_percentage = 'Absent' THEN 999 WHEN calculated_percentage IS NULL THEN 1000 ELSE (calculated_percentage + 0) END ASC, s.student_name ASC";
            break;
        case 'reg_asc':
            $orderSql = "ORDER BY s.register_no ASC";
            break;
        case 'reg_desc':
            $orderSql = "ORDER BY s.register_no DESC";
            break;
        case 'id_asc':
            $orderSql = "ORDER BY s.id ASC";
            break;
        case 'id_desc':
        default:
            $orderSql = "ORDER BY s.id DESC";
            $sort = 'id_desc';
            break;
    }

    $baseSelect = "
        SELECT 
            s.id,
            s.student_name,
            s.register_no,
            s.dob,
            s.mobile_number,
            COUNT(sub.id) AS total_subjects_registered,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) AS present_count,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NULL THEN 1 END) AS incomplete_count,
            COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) AS absent_count,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) AS total_obtained,
            SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END) AS attended_maximum_marks,
            CASE 
                WHEN COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) > 0
                     AND COUNT(CASE WHEN sm.attendance_status = 'Present' THEN 1 END) = 0
                     AND COUNT(sm.id) >= (SELECT COUNT(*) FROM subjects) THEN 'Absent'
                WHEN COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NULL THEN 1 END) > 0
                     OR COUNT(sm.id) < (SELECT COUNT(*) FROM subjects)
                     OR COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) = 0 THEN NULL
                ELSE ROUND(
                    (SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) / 
                     NULLIF(SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END), 0)) * 100, 2
                )
            END AS calculated_percentage
        FROM students s
        LEFT JOIN student_marks sm ON s.id = sm.student_id
        LEFT JOIN subjects sub ON sm.subject_id = sub.id
        WHERE {$whereSql}
        GROUP BY s.id, s.student_name, s.register_no, s.dob, s.mobile_number
        {$havingSql}
    ";

    // Count total filtered records
    $countSql = "SELECT COUNT(*) AS total_count FROM ({$baseSelect}) AS count_tbl";
    $countStmt = $pdo->prepare($countSql);
    foreach ($bindings as $key => $val) {
        $countStmt->bindValue($key, $val);
    }
    $countStmt->execute();
    $totalFiltered = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total_count'] ?? 0);

    // Compute total pages and ensure current page within bounds
    $totalPages = max(1, (int)ceil($totalFiltered / $limit));
    if ($page > $totalPages && $totalFiltered > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // Query current page records
    $dataSql = "{$baseSelect} {$orderSql} LIMIT :limit OFFSET :offset";
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($bindings as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add runtime age calculation via DateTime
    foreach ($rows as &$r) {
        $r['age'] = calculateAge($r['dob']);
    }

    return [
        'students'      => $rows,
        'totalRecords'  => $totalFiltered,
        'currentPage'   => $page,
        'totalPages'    => $totalPages,
        'limit'         => $limit,
        'offset'        => $offset,
        'search'        => $search,
        'pct_range'     => $pctRange,
        'age'           => $ageFilter,
        'sort'          => $sort
    ];
}

/**
 * Generate a cryptographically secure CSRF token stored in session
 * 
 * @return string
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against session
 * 
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retrieve all curriculum subjects from MySQL
 * Falls back to canonical 7 curriculum subjects if database is offline or empty
 * 
 * @param PDO|null $pdo
 * @return array
 */
function getAllSubjects($pdo) {
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT id, subject_name, maximum_marks FROM subjects ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            error_log("Failed to fetch subjects from MySQL: " . $e->getMessage());
        }
    }

    // Default canonical 7 subjects (100 max marks each)
    return [
        ['id' => 1, 'subject_name' => 'Fundamentals of Management', 'maximum_marks' => 100],
        ['id' => 2, 'subject_name' => 'Computer Architecture and Organization', 'maximum_marks' => 100],
        ['id' => 3, 'subject_name' => 'Java Programming', 'maximum_marks' => 100],
        ['id' => 4, 'subject_name' => 'Human Values and Ethics', 'maximum_marks' => 100],
        ['id' => 5, 'subject_name' => 'Formal Language and Automata Theory', 'maximum_marks' => 100],
        ['id' => 6, 'subject_name' => 'Database Management Systems', 'maximum_marks' => 100],
        ['id' => 7, 'subject_name' => 'Computational Statistics', 'maximum_marks' => 100],
    ];
}

/**
 * Check if a student register number is already registered in MySQL
 * 
 * @param PDO $pdo
 * @param string $registerNo
 * @param int|null $excludeStudentId Optional student ID to exclude (useful during edit)
 * @return bool
 */
function isRegisterNumberTaken(PDO $pdo, $registerNo, $excludeStudentId = null) {
    $sql = "SELECT id FROM students WHERE register_no = :reg";
    $bindings = [':reg' => trim($registerNo)];

    if ($excludeStudentId !== null) {
        $sql .= " AND id != :exclude_id";
        $bindings[':exclude_id'] = (int)$excludeStudentId;
    }

    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindings);
    return (bool)$stmt->fetchColumn();
}

/**
 * Fetch a single student record by primary key ID
 * 
 * @param PDO $pdo
 * @param int $studentId
 * @return array|null
 */
function getStudentById(PDO $pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    return $student ?: null;
}

/**
 * Fetch all subject examination marks for a given student ID joined with subjects
 * 
 * @param PDO $pdo
 * @param int $studentId
 * @return array
 */
function getStudentMarksWithSubjects(PDO $pdo, $studentId) {
    $sql = "
        SELECT 
            s.id AS subject_id,
            s.subject_name,
            s.maximum_marks,
            sm.id AS mark_id,
            sm.marks_obtained,
            sm.attendance_status
        FROM subjects s
        LEFT JOIN student_marks sm 
            ON s.id = sm.subject_id AND sm.student_id = :student_id
        ORDER BY s.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => (int)$studentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Build complete student academic dossier with dynamic age and performance metrics
 * 
 * @param PDO $pdo
 * @param int $studentId
 * @return array|null
 */
function getStudentCompleteDossier(PDO $pdo, $studentId) {
    $student = getStudentById($pdo, $studentId);
    if (!$student) {
        return null;
    }

    $marksRows = getStudentMarksWithSubjects($pdo, $studentId);

    $totalObtained = 0.0;
    $totalAttendedMax = 0;
    $presentCount = 0;
    $absentCount = 0;
    $hasIncomplete = false;

    foreach ($marksRows as $row) {
        $status = $row['attendance_status'] ?? 'Present';
        $max = (int)($row['maximum_marks'] ?? 100);

        if ($status === 'Absent') {
            $absentCount++;
        } else {
            $presentCount++;
            if ($row['marks_obtained'] === null) {
                $hasIncomplete = true;
            } else {
                $obtained = (float)$row['marks_obtained'];
                $totalObtained += $obtained;
                $totalAttendedMax += $max;
            }
        }
    }

    $totalCount = count($marksRows);
    $dynamicPercentage = null;
    $statusType = 'graded';

    if ($totalCount === 0 || ($presentCount === 0 && $absentCount === 0)) {
        $dynamicPercentage = null;
        $statusType = 'empty';
    } elseif ($absentCount === $totalCount && $presentCount === 0) {
        $dynamicPercentage = 'Absent';
        $statusType = 'absent';
    } elseif ($hasIncomplete || $presentCount === 0 || $totalAttendedMax === 0) {
        $dynamicPercentage = null;
        $statusType = 'incomplete';
    } else {
        $dynamicPercentage = round(($totalObtained / $totalAttendedMax) * 100, 2);
        $statusType = 'graded';
    }

    $badge = formatPercentageBadge($dynamicPercentage);
    $age = calculateAge($student['dob']);

    return [
        'student'           => $student,
        'age'               => $age,
        'marks'             => $marksRows,
        'presentCount'      => $presentCount,
        'absentCount'       => $absentCount,
        'totalCount'        => $totalCount,
        'totalObtained'     => $totalObtained,
        'totalAttendedMax'  => $totalAttendedMax,
        'dynamicPercentage' => $dynamicPercentage,
        'statusType'        => $statusType,
        'badge'             => $badge
    ];
}

/**
 * Delete a student and all related marks records safely within an atomic transaction
 * 
 * @param PDO $pdo
 * @param int $studentId
 * @return bool
 */
function deleteStudentWithMarks(PDO $pdo, $studentId) {
    try {
        $pdo->beginTransaction();

        // 1. Delete associated marks
        $stmtMarks = $pdo->prepare("DELETE FROM student_marks WHERE student_id = :student_id");
        $stmtMarks->execute([':student_id' => (int)$studentId]);

        // 2. Delete student record
        $stmtStudent = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $stmtStudent->execute([':id' => (int)$studentId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Failed to delete student #{$studentId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Categorize student percentage into 8 performance statuses
 * 
 * - 80–100%: Excellent
 * - 70–79.99%: Very Good
 * - 60–69.99%: Good
 * - 50–59.99%: Average
 * - 40–49.99%: Needs Improvement
 * - Below 40%: Low Performance
 * - All subjects absent: Absent
 * - Incomplete marks: Not Available
 * 
 * @param float|string|null $percentage
 * @return array ['code' => string, 'label' => string, 'class' => string]
 */
function getPerformanceCategory($percentage) {
    if ($percentage === 'Absent') {
        return [
            'code'  => 'absent',
            'label' => 'Absent',
            'class' => 'badge-danger'
        ];
    }

    if ($percentage === null || $percentage === '') {
        return [
            'code'  => 'incomplete',
            'label' => 'Not Available',
            'class' => 'badge-neutral'
        ];
    }

    $val = (float)$percentage;
    if ($val >= 80.0) {
        return ['code' => 'excellent', 'label' => 'Excellent', 'class' => 'badge-success'];
    } elseif ($val >= 70.0) {
        return ['code' => 'very_good', 'label' => 'Very Good', 'class' => 'badge-primary'];
    } elseif ($val >= 60.0) {
        return ['code' => 'good', 'label' => 'Good', 'class' => 'badge-teal'];
    } elseif ($val >= 50.0) {
        return ['code' => 'average', 'label' => 'Average', 'class' => 'badge-blue'];
    } elseif ($val >= 40.0) {
        return ['code' => 'needs_improvement', 'label' => 'Needs Improvement', 'class' => 'badge-warning'];
    } else {
        return ['code' => 'low_performance', 'label' => 'Low Performance', 'class' => 'badge-danger'];
    }
}

/**
 * Retrieve all students with competition ranking (1, 2, 2, 4), performance categories, and search/filter support
 * 
 * @param PDO $pdo
 * @param array $filters ['search' => string, 'category' => string, 'sort' => string]
 * @return array
 */
function getStudentRankingList(PDO $pdo, array $filters = []) {
    $allStudents = getAllStudentsWithPercentages($pdo);
    
    // Attach category and format
    foreach ($allStudents as &$st) {
        $st['category'] = getPerformanceCategory($st['calculated_percentage']);
    }
    unset($st);

    // Initial separation: graded students sorted descending by percentage vs unranked (absent/incomplete)
    $graded = [];
    $unranked = [];

    foreach ($allStudents as $st) {
        $pct = $st['calculated_percentage'];
        if ($pct !== null && is_numeric($pct)) {
            $st['numeric_pct'] = (float)$pct;
            $graded[] = $st;
        } else {
            $st['numeric_pct'] = -1.0;
            $st['rank'] = '-';
            $unranked[] = $st;
        }
    }

    // Sort graded students descending by percentage
    usort($graded, function($a, $b) {
        if ($b['numeric_pct'] > $a['numeric_pct']) return 1;
        if ($b['numeric_pct'] < $a['numeric_pct']) return -1;
        return strcmp($a['student_name'], $b['student_name']);
    });

    // Assign standard competition ranking (1, 2, 2, 4...)
    $currentRank = 1;
    $prevPct = null;
    $itemCount = count($graded);

    for ($i = 0; $i < $itemCount; $i++) {
        $currPct = round((float)$graded[$i]['numeric_pct'], 2);
        if ($prevPct !== null && abs($currPct - $prevPct) < 0.0001) {
            $graded[$i]['rank'] = $currentRank;
        } else {
            $currentRank = $i + 1; // Competition rank skips
            $graded[$i]['rank'] = $currentRank;
            $prevPct = $currPct;
        }
    }

    $combined = array_merge($graded, $unranked);

    // Apply search filter (name or register number)
    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $lowerSearch = mb_strtolower($search);
        $combined = array_filter($combined, function($s) use ($lowerSearch) {
            return (mb_strpos(mb_strtolower($s['student_name']), $lowerSearch) !== false) ||
                   (mb_strpos(mb_strtolower($s['register_no']), $lowerSearch) !== false);
        });
    }

    // Apply performance category filter
    $categoryFilter = trim($filters['category'] ?? 'all');
    if ($categoryFilter !== '' && $categoryFilter !== 'all') {
        $combined = array_filter($combined, function($s) use ($categoryFilter) {
            return $s['category']['code'] === $categoryFilter;
        });
    }

    // Apply sorting
    $sort = trim($filters['sort'] ?? 'pct_desc');
    usort($combined, function($a, $b) use ($sort) {
        switch ($sort) {
            case 'pct_asc':
                if ($a['numeric_pct'] >= 0 && $b['numeric_pct'] >= 0) {
                    return $a['numeric_pct'] <=> $b['numeric_pct'];
                }
                return ($a['numeric_pct'] < 0) ? 1 : -1;
            case 'name_asc':
                return strcmp($a['student_name'], $b['student_name']);
            case 'name_desc':
                return strcmp($b['student_name'], $a['student_name']);
            case 'rank_asc':
                if (is_numeric($a['rank']) && is_numeric($b['rank'])) {
                    return $a['rank'] <=> $b['rank'];
                }
                return is_numeric($a['rank']) ? -1 : 1;
            case 'pct_desc':
            default:
                if ($a['numeric_pct'] >= 0 && $b['numeric_pct'] >= 0) {
                    if ($b['numeric_pct'] != $a['numeric_pct']) {
                        return $b['numeric_pct'] <=> $a['numeric_pct'];
                    }
                    return strcmp($a['student_name'], $b['student_name']);
                }
                return ($a['numeric_pct'] < 0) ? 1 : -1;
        }
    });

    return array_values($combined);
}

/**
 * Fetch subject-wise average marks across all attended students from MySQL
 * 
 * @param PDO $pdo
 * @return array
 */
function getSubjectWisePerformanceAverages(PDO $pdo) {
    $sql = "
        SELECT 
            s.id,
            s.subject_name,
            s.maximum_marks,
            COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) AS attended_count,
            COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) AS absent_count,
            AVG(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained END) AS average_marks,
            MIN(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained END) AS min_marks,
            MAX(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained END) AS max_marks
        FROM subjects s
        LEFT JOIN student_marks sm ON s.id = sm.subject_id
        GROUP BY s.id, s.subject_name, s.maximum_marks
        ORDER BY s.id ASC
    ";
    try {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            foreach ($rows as &$r) {
                $r['average_marks'] = ($r['average_marks'] !== null) ? round((float)$r['average_marks'], 2) : 0.0;
            }
            return $rows;
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch subject averages: " . $e->getMessage());
    }

    return [];
}

/**
 * Neutralize potential CSV formula injection (=, +, -, @, tab, CR) before output.
 *
 * @param string|int|float|null $value
 * @return string
 */
function sanitizeCsvCell($value): string {
    $str = (string)($value ?? '');
    if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str) === 1) {
        return "'" . $str;
    }
    return $str;
}
