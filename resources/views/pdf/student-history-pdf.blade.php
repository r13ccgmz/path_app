<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student History — {{ $studentInfo['name'] ?? 'Unknown' }}</title>
    <style>
        /* ── Reset & Base ──────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
            padding: 40px 50px;
        }

        /* ── Header ────────────────────────────────────── */
        .header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header-logo {
            width: 280px;
            height: auto;
            margin: 0 auto 12px auto;
            display: block;
        }
        .header h1 {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-top: 4px;
        }
        .header p {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Cards & Containers ───────────────────────── */
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .info-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            page-break-after: avoid;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }
        .info-grid td {
            padding: 4px 12px 6px 0;
            vertical-align: top;
        }
        .info-label {
            font-size: 9px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
            word-wrap: break-word;
        }

        /* ── Tables ────────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .data-table thead th {
            background: #1e40af;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            page-break-after: avoid;
        }
        .data-table tbody td {
            padding: 7px 10px;
            font-size: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table tbody tr { page-break-inside: avoid; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table tbody tr:last-child td { border-bottom: 2px solid #1e40af; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* ── Graduation/Success Card ──────────────────── */
        .grad-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .grad-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #bbf7d0;
            padding-bottom: 6px;
        }

        /* ── Status Badges ────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-gray { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 4px;
            height: 10px;
            width: 100%;
            margin-top: 6px;
        }
        .progress-bar { border-radius: 4px; height: 10px; background: #16a34a; }

        /* ── Page-break protection ───────────────────── */
        .info-card, .grad-card { page-break-inside: avoid; }
        .ao-entry { page-break-inside: avoid; }
        .page-break { page-break-before: always; }

        /* ── Plain text (suppress auto-linking) ──────── */
        .no-link, .no-link a { color: #1e293b !important; text-decoration: none !important; }

        /* ── Emphasized link values ───────────────────── */
        .link-value { color: #2563eb; font-size: 10px; word-break: break-all; }

        /* ── Stacked list items ──────────────────────── */
        .stacked-list { padding: 0; margin: 0; list-style: none; }
        .stacked-list li { padding: 2px 0; font-size: 11px; font-weight: 600; color: #1e293b; }
        .stacked-list li::before { content: "• "; color: #94a3b8; }

        /* ── Type progress mini table ────────────────── */
        .type-progress-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .type-progress-table td { padding: 3px 6px; font-size: 9px; vertical-align: middle; }
        .type-progress-table .type-name { font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
        .type-bar-bg { background: #e2e8f0; border-radius: 3px; height: 6px; width: 100%; }
        .type-bar-fill { border-radius: 3px; height: 6px; }

        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sidebar-branding-light-mode.png'))) }}" class="header-logo" alt="CPAf Logo">
        <h1>Institute for Governance and Rural Development (IGRD)</h1>
        <h2>Student Academic Record</h2>
        <p>Generated on {{ now()->timezone('Asia/Manila')->format('F j, Y — g:i A \P\H\T') }} &bull; <strong style="color: #991b1b;">CONFIDENTIAL</strong></p>
    </div>

    {{-- Demographics Card --}}
    <div class="info-card">
        <h3>Student Information</h3>
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Full Name</div>
                    <div class="info-value" style="font-size: 14px;">{{ $studentInfo['name'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Student Number</div>
                    <div class="info-value">{{ empty($studentInfo['student_number']) ? '-' : \App\Models\Enrollee::formatStudentNumber($studentInfo['student_number']) }}</div>
                </td>
                <td>
                    <div class="info-label">Student Status</div>
                    <div class="info-value">
                        @php
                            $sStatus = $studentInfo['student_status'] ?? '-';
                            $statusColor = '#1e293b';
                            if ($sStatus === 'active') $statusColor = '#16a34a';
                            if ($sStatus === 'graduated') $statusColor = '#2563eb';
                            if (str_contains($sStatus, 'leave')) $statusColor = '#d97706';
                            if ($sStatus === 'inactive' || $sStatus === 'dismissed' || $sStatus === 'dropped') $statusColor = '#dc2626';
                        @endphp
                        <span style="color: {{ $statusColor }}; font-weight: 700;">{{ ucwords(str_replace('-', ' ', $sStatus)) }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Birthdate</div>
                    <div class="info-value">{{ $studentInfo['birthdate'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Sex</div>
                    <div class="info-value">{{ ucfirst($studentInfo['sex'] ?? '-') }}</div>
                </td>
                <td>
                    <div class="info-label">Nationality</div>
                    <div class="info-value">{{ empty($studentInfo['nationality']) ? '-' : (is_array($studentInfo['nationality']) ? implode(', ', $studentInfo['nationality']) : $studentInfo['nationality']) }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Marital Status</div>
                    <div class="info-value">{{ ucfirst($studentInfo['marital_status'] ?? '-') }}</div>
                </td>
                <td>
                    <div class="info-label">Country of Origin</div>
                    <div class="info-value">{{ $studentInfo['country_of_origin'] ?? '-' }}</div>
                </td>
                <td></td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        @if(empty($studentInfo['address']))
                            -
                        @elseif(is_array($studentInfo['address']))
                            @foreach($studentInfo['address'] as $index => $addr)
                                <div style="margin-bottom: 2px;"><span style="color: #94a3b8; font-size: 9px; font-weight: 600;">Address {{ $index + 1 }}:</span> {{ $addr }}</div>
                            @endforeach
                        @else
                            {{ $studentInfo['address'] }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Contact Information Card --}}
    <div class="info-card">
        <h3>Contact Information</h3>
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Primary Contact</div>
                    <div class="info-value">{{ $studentInfo['primary_contact_number'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Alternative Contact(s)</div>
                    <div class="info-value">{{ empty($studentInfo['alternative_contact_number']) ? '-' : (is_array($studentInfo['alternative_contact_number']) ? implode(', ', $studentInfo['alternative_contact_number']) : $studentInfo['alternative_contact_number']) }}</div>
                </td>
                <td></td>
            </tr>

            <tr>
                <td>
                    <div class="info-label">Primary Email</div>
                    <div class="info-value no-link">{{ $studentInfo['email'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">UP Email</div>
                    <div class="info-value no-link">{{ $studentInfo['up_email'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Alternative Email(s)</div>
                    <div class="info-value no-link">{{ empty($studentInfo['alternative_email']) ? '-' : (is_array($studentInfo['alternative_email']) ? implode(', ', $studentInfo['alternative_email']) : $studentInfo['alternative_email']) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Social Media & Affiliation Card --}}
    @if (($studentInfo['social_facebook'] ?? null) || ($studentInfo['social_linkedin'] ?? null) || ($studentInfo['social_other'] ?? null) || ($studentInfo['institution_affiliated'] ?? null))
    <div class="info-card">
        <h3>Social Media & Affiliation</h3>
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Facebook</div>
                    @if(!empty($studentInfo['social_facebook']))
                        <div class="link-value">{{ $studentInfo['social_facebook'] }}</div>
                    @else
                        <div class="info-value">-</div>
                    @endif
                </td>
                <td>
                    <div class="info-label">LinkedIn</div>
                    @if(!empty($studentInfo['social_linkedin']))
                        <div class="link-value">{{ $studentInfo['social_linkedin'] }}</div>
                    @else
                        <div class="info-value">-</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="info-label">Office / School / Institution Affiliated</div>
                    @if(empty($studentInfo['institution_affiliated']))
                        <div class="info-value">-</div>
                    @elseif(is_array($studentInfo['institution_affiliated']))
                        <ul class="stacked-list">
                            @foreach($studentInfo['institution_affiliated'] as $inst)
                                <li>{{ $inst }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="info-value">{{ $studentInfo['institution_affiliated'] }}</div>
                    @endif
                </td>
            </tr>
            @if(!empty($studentInfo['social_other']))
            <tr>
                <td colspan="2">
                    <div class="info-label">Other Social Media / Contact Handles</div>
                    @if(is_array($studentInfo['social_other']))
                        <ul class="stacked-list">
                            @foreach($studentInfo['social_other'] as $social)
                                <li>
                                    @if(str_starts_with($social, 'http'))
                                        <span class="link-value">{{ $social }}</span>
                                    @else
                                        {{ $social }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        @if(str_starts_with($studentInfo['social_other'], 'http'))
                            <div class="link-value">🔗 {{ $studentInfo['social_other'] }}</div>
                        @else
                            <div class="info-value">{{ $studentInfo['social_other'] }}</div>
                        @endif
                    @endif
                </td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- Admission Card (with committee inline) --}}
    <div class="info-card">
        <h3>Admission Information</h3>
        <table class="info-grid">
            <tr>
                <td style="width: 20%;">
                    <div class="info-label">Applicant Status</div>
                    <div class="info-value">{{ empty($studentInfo['applicant_status']) ? '-' : ucwords(str_replace('-', ' ', $studentInfo['applicant_status'])) }}</div>
                </td>
                <td style="width: 30%;">
                    <div class="info-label">Admission Semester</div>
                    <div class="info-value">{{ $studentInfo['admission_semester'] ?? '-' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="info-label">Admission Date</div>
                    <div class="info-value">{{ $studentInfo['admission_date'] ?? '-' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="info-label">Registration Adviser</div>
                    <div class="info-value">{{ $studentInfo['registration_adviser_name'] ?? '-' }}{{ !empty($studentInfo['registration_adviser_designation']) ? ' (' . $studentInfo['registration_adviser_designation'] . ')' : '' }}</div>
                </td>
            </tr>
        </table>
        @if(!empty($studentInfo['committee_members']))
        <div style="border-top: 1px solid #e2e8f0; margin-top: 10px; padding-top: 10px;">
            <div class="info-label" style="margin-bottom: 6px; font-size: 10px;">Advisory Committee</div>
            <table class="info-grid">
                @if(!empty($studentInfo['committee_members']))
                    @php
                        $rolePriority = ['Adviser' => 0, 'Former Adviser' => 1, 'Reg. Adviser' => 2, 'Chair' => 3, 'Co-Chair' => 4, 'Major' => 5, 'Minor' => 6, 'Cognate' => 7, 'Member' => 8];
                        $sortedMembers = collect($studentInfo['committee_members'])->sortBy(fn($m) => $rolePriority[$m['role'] ?? 'Member'] ?? 99)->values()->all();
                    @endphp
                    @foreach ($sortedMembers as $cm)
                    <tr>
                        <td style="width: 25%;"><div class="info-label">{{ $cm['role'] ?? 'Member' }}</div></td>
                        <td>
                            <div class="info-value">
                                {{ $cm['name'] }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}{{ !empty($cm['appointed_date']) ? ' - (Appointed: ' . $cm['appointed_date'] . ')' : '' }}
                                @if(!empty($cm['term_start']) || !empty($cm['term_end']))
                                    <br><span style="color: #64748b;">Term: {{ $cm['term_start'] ?? '-' }} to {{ $cm['term_end'] ?? 'Present' }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </table>
        </div>
        @endif
    </div>

    {{-- Graduation Card (with committee inline) --}}
    @if (!empty($studentInfo['graduation_records']))
    @foreach ($studentInfo['graduation_records'] as $gradRecord)
    <div class="grad-card">
        <h3>Graduation Information{{ count($studentInfo['graduation_records']) > 1 ? ' — ' . ($gradRecord['program_name'] ?? $gradRecord['degree'] ?? '') : '' }}</h3>
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-label">Semester Graduated</div>
                    <div class="info-value">{{ $gradRecord['semester_graduated'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Degree</div>
                    <div class="info-value">{{ $gradRecord['degree'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Country of Origin</div>
                    <div class="info-value">{{ $gradRecord['country_of_origin'] ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="info-label">Program</div>
                    <div class="info-value">{{ $gradRecord['program_name'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="info-label">Major Field</div>
                    <div class="info-value">{{ $gradRecord['major'] ?? '-' }}</div>
                </td>
            </tr>
        </table>
        @if(!empty($gradRecord['committee_data']))
        <div style="border-top: 1px solid #bbf7d0; margin-top: 10px; padding-top: 10px;">
            <div class="info-label" style="margin-bottom: 6px; font-size: 10px;">Graduation Committee</div>
            @php
                $gradRolePriority = ['Adviser' => 0, 'Chair' => 1, 'Co-Chair' => 2, 'Major' => 3, 'Minor' => 4, 'Cognate' => 5, 'Member' => 6];
                $sortedGradCommittee = collect($gradRecord['committee_data'])->sortBy(fn($m) => $gradRolePriority[$m['role_label'] ?? $m['role'] ?? 'Member'] ?? 99)->values()->all();
            @endphp
            @foreach ($sortedGradCommittee as $cm)
            <div style="padding: 2px 0;">
                <span class="info-label" style="display: inline-block; width: 25%; vertical-align: top;">{{ $cm['role_label'] ?? $cm['role'] }}</span>
                <span class="info-value" style="display: inline-block; width: 73%; vertical-align: top;">
                    {{ $cm['name'] }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}{{ !empty($cm['formatted_appointed_date']) ? ' - (Appointed: ' . $cm['formatted_appointed_date'] . ')' : (!empty($cm['appointed_date']) ? ' - (Appointed: ' . $cm['appointed_date'] . ')' : '') }}
                    @if(!empty($cm['term_start']) || !empty($cm['term_end']))
                        <br><span style="color: #64748b;">Term: {{ $cm['term_start'] ?? '-' }} to {{ $cm['term_end'] ?? 'Present' }}</span>
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
    @endif

    {{-- Academic Progress --}}
    @if (!empty($academicProgress))
    <div class="info-card" style="page-break-inside: auto;">
        <h3>Curriculum Progress</h3>
        @foreach ($academicProgress as $progress)
            @php
                $program = $progress['program'] ?? null;
                $programName = is_object($program) ? $program->name : ($progress['program_name'] ?? '-');
                $unitsEarned = $progress['total_earned'] ?? 0;
                $unitsRequired = $progress['total_required'] ?? 0;
                $completionPct = $unitsRequired > 0 ? round(($unitsEarned / $unitsRequired) * 100) : 0;
                $gwa = $progress['gwa'] ?? null;
                $completedCourses = $progress['completed_courses'] ?? 0;
                $totalCourses = $progress['total_courses'] ?? 0;
            @endphp
            <div class="ao-entry" style="page-break-inside: avoid; margin-top: {{ $loop->first ? '0' : '16px' }}; {{ !$loop->last ? 'border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;' : '' }}">
                <table class="info-grid" style="margin-bottom: 4px;">
                    <tr>
                        <td style="width: 40%;">
                            <div class="info-label">Program</div>
                            <div class="info-value">{{ $programName }}</div>
                        </td>
                        <td style="width: 15%;">
                            <div class="info-label">Units</div>
                            <div class="info-value">{{ $unitsEarned }} / {{ $unitsRequired ?: '?' }} units</div>
                        </td>
                        <td style="width: 15%;">
                            <div class="info-label">Courses</div>
                            <div class="info-value">{{ $completedCourses }} completed</div>
                        </td>
                        <td style="width: 15%; text-align: right;">
                            <div class="info-label">Completion</div>
                            <div class="info-value">{{ $completionPct }}%</div>
                        </td>
                    </tr>
                </table>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: {{ min($completionPct, 100) }}%;"></div>
                </div>

                @if(!empty($progress['type_progress']))
                <table class="type-progress-table">
                    @foreach($progress['type_progress'] as $tp)
                    @php
                        $tpPct = ($tp['required_units'] ?? 0) > 0 ? min(100, round(($tp['earned_units'] / $tp['required_units']) * 100)) : 0;
                    @endphp
                    <tr>
                        <td style="width: 20%;"><span class="type-name">{{ $tp['label'] }}</span></td>
                        <td style="width: 50%;">
                            <div class="type-bar-bg">
                                <div class="type-bar-fill" style="width: {{ $tpPct }}%; background: {{ $tp['color'] ?? '#16a34a' }};"></div>
                            </div>
                        </td>
                        <td style="width: 15%; text-align: right; font-weight: 600; color: #334155;">{{ $tp['earned_units'] }} / {{ $tp['required_units'] ?? '?' }} units</td>
                        <td style="width: 15%; text-align: right; color: #64748b;">{{ $tp['completed_courses'] }} courses completed</td>
                    </tr>
                    @endforeach
                </table>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    {{-- Academic Milestones --}}
    <div class="info-card" style="page-break-inside: avoid;">
        <h3>Academic Milestones</h3>
        @if (!empty($milestones) && count($milestones) > 0)
        <table class="data-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 40%;">Milestone</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%;">Date</th>
                    <th style="width: 25%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($milestones as $m)
                <tr>
                    <td><strong>{{ $m['name'] ?? '-' }}</strong></td>
                    <td>
                        @php
                            $statusStr = strtolower($m['status'] ?? '');
                            $badge = 'badge-gray';
                            if ($statusStr === 'completed') $badge = 'badge-success';
                            if (str_contains($statusStr, 'progress')) $badge = 'badge-warning';
                            if ($statusStr === 'failed') $badge = 'badge-danger';
                        @endphp
                        <span class="badge {{ $badge }}">{{ $m['status_label'] ?? ucfirst($statusStr) }}</span>
                    </td>
                    <td>{{ $m['date_completed'] ? \Carbon\Carbon::parse($m['date_completed'])->format('Y-m-d') : '-' }}</td>
                    <td>{{ $m['remarks'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="padding: 12px 0; color: #64748b; font-style: italic; font-size: 10px;">No academic milestones recorded.</div>
        @endif
    </div>

        {{-- Academic Outputs --}}
    <div class="info-card" style="page-break-inside: auto;">
        <h3>Academic Outputs</h3>
        @if (!empty($academicOutputs) && count($academicOutputs) > 0)
            @foreach ($academicOutputs as $ao)
            <table style="width: 100%; page-break-inside: avoid; margin-bottom: 16px; border-collapse: separate; border-spacing: 0;">
                <tr>
                    <td class="grad-card" style="margin-bottom: 0; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                <table class="info-grid">
                    <tr>
                        <td colspan="4">
                            <div class="info-value" style="font-size: 13px; color: #1e40af;">{{ $ao['title'] ?? 'Untitled' }}</div>
                            @if(!empty($ao['primary_authors']))
                                <div class="info-value" style="font-size: 11px; color: #475569; margin-top: 4px;">Primary Author(s): {{ implode('; ', $ao['primary_authors']) }}</div>
                            @endif
                            @if(!empty($ao['co_authors']))
                                <div class="info-value" style="font-size: 11px; color: #475569; margin-top: 2px;">Co-Author(s): {{ implode('; ', $ao['co_authors']) }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">Type</div>
                            <div class="info-value">{{ $ao['type_label'] ?? ucfirst(str_replace('-', ' ', $ao['type'] ?? '-')) }}</div>
                        </td>
                        <td>
                            <div class="info-label">Status</div>
                            <div class="info-value">{{ ucfirst(str_replace('-', ' ', $ao['status'] ?? '-')) }}</div>
                        </td>
                        <td>
                            <div class="info-label">Date Submitted</div>
                            <div class="info-value">{{ $ao['date_submitted'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="info-label">Semester / Term</div>
                            <div class="info-value">{{ !empty($ao['term_code']) ? '[' . $ao['term_code'] . ']' . (!empty($ao['semester_label']) ? ' ' . $ao['semester_label'] : '') : '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="info-label">Proposal Defense</div>
                            <div class="info-value">{{ $ao['proposal_defense_date'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="info-label">Proposal Result</div>
                            <div class="info-value">
                                @if(!empty($ao['proposal_defense_result']))
                                    @php
                                        $pRes = strtolower($ao['proposal_defense_result']);
                                        $pBadge = 'badge-gray';
                                        if (str_contains($pRes, 'pass')) $pBadge = 'badge-success';
                                        if (str_contains($pRes, 'fail')) $pBadge = 'badge-danger';
                                        if (str_contains($pRes, 'revision')) $pBadge = 'badge-warning';
                                    @endphp
                                    <span class="badge {{ $pBadge }}">{{ ucfirst(str_replace('-', ' ', $ao['proposal_defense_result'])) }}</span>
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="info-label">Final Defense</div>
                            <div class="info-value">{{ $ao['final_defense_date'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="info-label">Final Result</div>
                            <div class="info-value">
                                @if(!empty($ao['final_defense_result']))
                                    @php
                                        $fRes = strtolower($ao['final_defense_result']);
                                        $fBadge = 'badge-gray';
                                        if (str_contains($fRes, 'pass')) $fBadge = 'badge-success';
                                        if (str_contains($fRes, 'fail')) $fBadge = 'badge-danger';
                                        if (str_contains($fRes, 'revision')) $fBadge = 'badge-warning';
                                    @endphp
                                    <span class="badge {{ $fBadge }}">{{ ucfirst(str_replace('-', ' ', $ao['final_defense_result'])) }}</span>
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if(!empty($ao['drive_link']))
                    <tr>
                        <td colspan="4">
                            <div class="info-label">Academic Output Link</div>
                            <div class="link-value">{{ $ao['drive_link'] }}</div>
                        </td>
                    </tr>
                    @endif
                    @if(!empty($ao['keywords']))
                    <tr>
                        <td colspan="4">
                            <div class="info-label">Keywords</div>
                            <div class="info-value" style="font-weight: normal;">{{ is_array($ao['keywords']) ? implode(', ', $ao['keywords']) : $ao['keywords'] }}</div>
                        </td>
                    </tr>
                    @endif
                    @if(!empty($ao['abstract']))
                    <tr>
                        <td colspan="4">
                            <div class="info-label">Abstract</div>
                            <div class="info-value" style="white-space: pre-wrap; font-weight: normal;">{{ $ao['abstract'] }}</div>
                        </td>
                    </tr>
                    @endif

                    @if(!empty($ao['committee']))
                    <tr>
                        <td colspan="4">
                            <div class="info-label" style="margin-bottom: 4px;">Advisory Committee</div>
                            @php
                                $aoRolePriority = ['Adviser' => 0, 'Chair' => 1, 'Co-Chair' => 2, 'Major' => 3, 'Minor' => 4, 'Cognate' => 5, 'Member' => 6];
                                $sortedAoCommittee = collect($ao['committee'])->sortBy(fn($m) => $aoRolePriority[$m['role_label'] ?? $m['role'] ?? 'Member'] ?? 99)->values()->all();
                            @endphp
                            @foreach($sortedAoCommittee as $cm)
                            <div style="padding: 2px 0;">
                                <span class="info-label" style="display: inline-block; width: 25%; vertical-align: top;">{{ $cm['role_label'] ?? $cm['role'] }}</span>
                                <span class="info-value" style="display: inline-block; width: 73%; vertical-align: top;">
                                    {{ $cm['name'] }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}{{ !empty($cm['formatted_appointed_date']) ? ' - (Appointed: ' . $cm['formatted_appointed_date'] . ')' : '' }}
                                    @if(!empty($cm['term_start']) || !empty($cm['term_end']))
                                        <br><span style="color: #64748b;">Term: {{ $cm['term_start'] ?? '-' }} to {{ $cm['term_end'] ?? 'Present' }}</span>
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>
                    </td>
                </tr>
            </table>
            @endforeach
        @else
            <div style="padding: 12px 0; color: #64748b; font-style: italic; font-size: 10px;">No academic outputs recorded.</div>
        @endif
    </div>

    {{-- Enrollment History --}}
    <div class="info-card" style="page-break-inside: auto;">
        <h3>Enrollment History</h3>
        <table class="data-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Degree / Program</th>
                    <th>Courses Enrolled</th>
                    <th class="text-center">Units</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @php $ftThreshold = (int) \App\Models\SystemSetting::get('full_time_units_threshold', 9); @endphp
                @forelse($records as $record)
                    @php
                        $units = (int) ($record->total_units ?? 0);
                        $ftLabel = $units === 0 ? '-' : ($units >= $ftThreshold ? 'Full-Time' : 'Part-Time');
                        $ftColor = $ftLabel === 'Full-Time' ? '#16a34a' : ($ftLabel === 'Part-Time' ? '#d97706' : '#94a3b8');
                    @endphp
                    <tr>
                        <td>{{ $record->term_id }}</td>
                        <td>{{ $record->degree_program }}</td>
                        <td>{{ $record->courses_enrolled }}</td>
                        <td class="text-center">{{ $record->total_units }}</td>
                        <td class="text-center"><span style="color: {{ $ftColor }}; font-weight: 600; font-size: 9px;">{{ $ftLabel }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding:16px; color:#94a3b8;">No enrollment records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        This document was generated from PATH — Progress and Academic Tracking Hub. &bull; Confidential
    </div>

</body>
</html>
