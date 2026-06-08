<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== MPAf Majors (program_id=9) ===\n";
$majors = \App\Models\ProgramMajor::where('program_id', 9)->get();
foreach ($majors as $m) {
    echo "  ID:{$m->id} | {$m->name}\n";
}

echo "\n=== All Program Majors ===\n";
$allMajors = \App\Models\ProgramMajor::with('program')->orderBy('program_id')->orderBy('name')->get();
foreach ($allMajors as $m) {
    echo "  ID:{$m->id} | prog_id:{$m->program_id} ({$m->program?->code}) | {$m->name}\n";
}

echo "\n=== Student 200795969 enrollee terms vs semester lookup ===\n";
$semLookup = \Illuminate\Support\Facades\DB::table('semesters')->pluck('id', 'term_code')->toArray();
$terms = ['1201', '1202', '1211', '1221'];
foreach ($terms as $t) {
    echo "  term_code:{$t} => sem_id:" . ($semLookup[$t] ?? 'NOT FOUND') . "\n";
}

echo "\n=== Student 200795969 enrollments details ===\n";
$student = \App\Models\Student::where('student_number', '200795969')->first();
$enrollments = \App\Models\StudentEnrollment::where('student_id', $student->id)
    ->with('course', 'semester')
    ->orderBy('semester_id')
    ->get();
foreach ($enrollments as $se) {
    echo "  SE:{$se->id} | sp_id:" . ($se->student_program_id ?? 'NULL') 
        . " | sem_id:{$se->semester_id} (term:" . ($se->semester?->term_code ?? '?') . ")" 
        . " | course:" . ($se->course?->course_code ?? 'NULL') . "\n";
}

echo "\n=== Student 200795969 enrollee data for term 1201, 1202 ===\n";
$enrollees = \App\Models\Enrollee::where('student_number', '200795969')
    ->whereIn('term_id', ['1201', '1202'])
    ->get(['term_id', 'degree_program', 'courses_enrolled']);
foreach ($enrollees as $e) {
    echo "  term:{$e->term_id} | deg:{$e->degree_program} | courses:{$e->courses_enrolled}\n";
}
