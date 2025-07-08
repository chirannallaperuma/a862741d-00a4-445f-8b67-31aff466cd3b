<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class GenerateAssessmentReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-assessment-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Please enter the following");

        $studentId = $this->ask("Student ID");

        $options = [
            1 => 'Diagnostic',
            2 => 'Progress',
            3 => 'Feedback',
        ];

        $reportTypeStr  = $this->choice(
            "Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)",
            $options,
        );

        $reportType = array_search($reportTypeStr, $options);
        $service = new ReportService($studentId, $reportType, $this);

        $service->generate();

        return Command::SUCCESS;
    }
}
