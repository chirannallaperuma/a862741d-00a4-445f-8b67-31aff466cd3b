<?php

namespace Tests\Feature;

use Tests\TestCase;

class GenerateAssessmentReportTest extends TestCase
{
    /** @test */
    public function it_generates_diagnostic_report_for_a_valid_student()
    {
        $this->artisan('app:generate-assessment-report')
            ->expectsQuestion('Student ID', 'student1')
            ->expectsChoice(
                'Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)',
                'Diagnostic',
                ['1' => 'Diagnostic', '2' => 'Progress', '3' => 'Feedback']
            )
            ->expectsOutputToContain('recently completed Numeracy assessment')
            ->assertExitCode(0);
    }

    /** @test */
    public function report_generate_command_runs_successfully_with_all_types()
    {
        $studentId = 'student1';
        $questionPrompt = 'Student ID';
        $choicePrompt = 'Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)';
        $reportOptions = ['1' => 'Diagnostic', '2' => 'Progress', '3' => 'Feedback'];

        foreach ($reportOptions as $key => $label) {
            $this->artisan('app:generate-assessment-report')
                ->expectsQuestion($questionPrompt, $studentId)
                ->expectsChoice($choicePrompt, $label, $reportOptions)
                ->assertExitCode(0);
        }
    }
}
