<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Curriculum Map — {{ $program->name }}</title>
    <style>
        /* Reset & Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1f2937;
            line-height: 1.4;
            padding: 20px 25px 50px 25px;
        }

        /* Header */
        .pdf-header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1A5C38;
        }
        .pdf-header h1 {
            font-size: 14pt;
            color: #1A5C38;
            margin-bottom: 2px;
        }
        .pdf-header .subtitle {
            font-size: 10pt;
            color: #6b7280;
        }
        .pdf-header .meta {
            font-size: 7pt;
            color: #9ca3af;
            margin-top: 4px;
        }

        /* Unit Requirements Summary */
        .unit-summary {
            margin-bottom: 14px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            overflow: hidden;
        }
        .unit-summary-title {
            background-color: #f3f4f6;
            padding: 4px 10px;
            font-size: 8pt;
            font-weight: bold;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        .unit-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .unit-summary-table td {
            padding: 4px 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        .unit-summary-table .type-cell {
            font-weight: 600;
            width: 60%;
        }
        .unit-summary-table .units-cell {
            text-align: right;
            width: 40%;
            color: #374151;
        }
        .unit-summary-table .total-row td {
            background-color: #f0fdfa;
            font-weight: bold;
            border-top: 1px solid #d1d5db;
            border-bottom: none;
            color: #1A5C38;
        }
        .type-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 4px;
        }

        /* Requirements */
        .requirements {
            margin-bottom: 14px;
            padding: 8px 12px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .requirements h3 {
            font-size: 8pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 4px;
        }
        .requirements li {
            font-size: 7.5pt;
            color: #4b5563;
            margin-left: 14px;
            margin-bottom: 1px;
        }

        /* Course Type Section */
        .type-section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .type-header {
            padding: 5px 10px;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
            margin-bottom: 0;
        }
        .type-subheader {
            font-size: 7.5pt;
            font-weight: normal;
            opacity: 0.85;
            float: right;
            margin-top: 1px;
        }

        /* Course Table */
        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            font-size: 8pt;
        }
        .course-table thead th {
            background-color: #f3f4f6;
            padding: 3px 6px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #e5e7eb;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .course-table tbody td {
            padding: 3px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .course-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .course-code {
            font-weight: bold;
            white-space: nowrap;
        }
        .spec-group {
            font-size: 7.5pt;
            font-weight: 600;
            color: #4b5563;
            padding: 3px 6px;
            background-color: #f0f4f8;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .notes-text {
            font-size: 7pt;
            color: #6b7280;
            font-style: italic;
        }

        /* Semester Table (table view) */
        .semester-section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .semester-header {
            padding: 5px 10px;
            background-color: #1A5C38;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
        }

        /* Page break */
        .page-break { page-break-before: always; }

        /* Footer — text only, page number via PHP script */
        .pdf-footer {
            position: fixed;
            bottom: 10px;
            left: 25px;
            right: 25px;
            text-align: left;
            font-size: 6.5pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="pdf-header">
        <h1>{{ $program->name }}</h1>
        <div class="subtitle">{{ $program->code }} · {{ $program->degree_level?->label() ?? 'N/A' }}</div>
        <div class="meta">Curriculum Map · Generated {{ now()->format('F j, Y') }}</div>
    </div>

    <!-- Unit Requirements Summary -->
    @if (!empty($stats))
        @php
            $typeStats = collect($stats)->where('type', '!=', '__total__')->values();
            $totalStat = collect($stats)->firstWhere('type', '__total__');
            $totalMinSum = $typeStats->sum('min_units');
        @endphp
        <div class="unit-summary">
            <div class="unit-summary-title">Minimum Unit Requirements</div>
            <table class="unit-summary-table">
                @foreach ($typeStats as $stat)
                    @if ($stat['min_units'])
                        <tr>
                            <td class="type-cell">
                                <span class="type-dot" style="background-color: {{ $stat['color'] }};"></span>
                                {{ $stat['type'] }} Courses
                            </td>
                            <td class="units-cell">{{ $stat['min_units'] }} units</td>
                        </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td class="type-cell">Total Units Required</td>
                    <td class="units-cell">{{ $totalStat['min_units'] ?? $totalMinSum ?: '—' }} units</td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Requirements -->
    @if ($requirements->count() > 0)
        <div class="requirements">
            <h3>Program Requirements</h3>
            <ul>
                @foreach ($requirements as $req)
                    <li>{{ $req->requirement_text }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Curriculum Content -->
    @if ($viewMode === 'table')
        {{-- Table View: grouped by semester --}}
        @foreach ($semesterData as $semester => $courses)
            <div class="semester-section">
                <div class="semester-header">{{ $semester }}</div>
                <table class="course-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Code</th>
                            <th style="width: 30%;">Course Name</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 6%;">Units</th>
                            <th style="width: 20%;">Prerequisite</th>
                            <th style="width: 22%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td class="course-code">{{ $course['code'] }}</td>
                                <td>{{ $course['name'] }}</td>
                                <td>
                                    <span style="color: {{ $course['type_color'] }};">
                                        {{ $course['type_label'] }}
                                    </span>
                                </td>
                                <td style="text-align: center;">{{ $course['units'] ?? '—' }}</td>
                                <td>{{ $course['prerequisite'] ?? '—' }}</td>
                                <td class="notes-text">{{ $course['notes'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        {{-- Timeline View: grouped by course type --}}
        @foreach ($curriculum as $courseType => $specializations)
            @php
                $typeColor = \App\Filament\Pages\CurriculumMap::getTypeColor($courseType);
                $minUnitsMap = collect($stats)->where('type', '!=', '__total__')->pluck('min_units', 'type')->toArray();
                $typeMinUnits = $minUnitsMap[$courseType] ?? null;
            @endphp
            <div class="type-section">
                <div class="type-header" style="background-color: {{ $typeColor }};">
                    {{ $courseType }} Courses
                    @php
                        $typeCourseCount = 0;
                        foreach ($specializations as $courses) {
                            $typeCourseCount += count($courses);
                        }
                    @endphp
                    <span class="type-subheader">
                        {{ $typeCourseCount }} {{ Str::plural('course', $typeCourseCount) }}
                        @if ($typeMinUnits)
                            · min. {{ $typeMinUnits }} {{ Str::plural('unit', $typeMinUnits) }}
                        @endif
                    </span>
                </div>

                <table class="course-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Code</th>
                            <th style="width: 32%;">Course Name</th>
                            <th style="width: 6%;">Units</th>
                            <th style="width: 14%;">Semester</th>
                            <th style="width: 18%;">Prerequisite</th>
                            <th style="width: 18%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($specializations as $specName => $courses)
                            @if ($specName !== 'General' && count($specializations) > 1)
                                <tr>
                                <td colspan="6" class="spec-group">&gt; {{ $specName }}</td>
                                </tr>
                            @endif
                            @foreach ($courses as $course)
                                <tr>
                                    <td class="course-code">{{ $course['code'] }}</td>
                                    <td>{{ $course['name'] }}</td>
                                    <td style="text-align: center;">{{ $course['units'] ?? '—' }}</td>
                                    <td>{{ $course['semester'] ?? '—' }}</td>
                                    <td>{{ $course['prerequisite'] ?? '—' }}</td>
                                    <td class="notes-text">{{ $course['notes'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <!-- Footer -->
    <div class="pdf-footer">
        PATH — Progress and Academic Tracking Hub · {{ $program->code }} Curriculum Map
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans");
            $size = 7;
            $pageText = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $width = $fontMetrics->getTextWidth($pageText, $font, $size);
            $x = $pdf->get_width() - $width - 25;
            $y = $pdf->get_height() - 22;
            $pdf->page_text($x, $y, $pageText, $font, $size, array(0.62, 0.63, 0.67));
        }
    </script>
</body>
</html>
