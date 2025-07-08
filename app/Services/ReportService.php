<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class ReportService
{
    protected string $studentId;
    protected int $reportType;
    protected Command $console;

    protected array $questions = [];
    protected array $students = [];
    protected array $responses = [];

    /**
     * __construct
     *
     * @param  mixed $studentId
     * @param  mixed $reportType
     * @param  mixed $console
     * @return void
     */
    public function __construct(string $studentId, int $reportType, Command $console)
    {
        $this->studentId = $studentId;
        $this->reportType = $reportType;
        $this->console = $console;

        $this->loadData();
    }

    /**
     * loadData
     *
     * @return void
     */
    protected function loadData(): void
    {
        try {
            $this->questions = json_decode(file_get_contents(base_path('data/questions.json')), true);
            $this->students = json_decode(file_get_contents(base_path('data/students.json')), true);
            $this->responses = json_decode(file_get_contents(base_path('data/student-responses.json')), true);
        } catch (Throwable $th) {
            $this->console->error('Error loading data: ' . $th->getMessage());
            exit(1);
        }
    }

    /**
     * generate
     *
     * @return void
     */
    public function generate(): void
    {
        switch ($this->reportType) {
            case 1:
                $this->generateDiagnostic();
                break;
            case 2:
                $this->generateProgress();
                break;
            case 3:
                $this->generateFeedback();
                break;
        }
    }

    /**
     * getStudent
     *
     * @return array
     */
    protected function getStudent(): ?array
    {
        return collect($this->students)->firstWhere('id', $this->studentId);
    }

    /**
     * getStudentResponses
     *
     * @return Collection
     */
    protected function getStudentResponses(): Collection
    {
        return collect($this->responses)
            ->where('student.id', $this->studentId)
            ->filter(function ($response) {
                return isset($response['responses']) && count($response['responses']) === 16;
            })
            ->sortBy('completed')
            ->values();
    }

    /**
     * generateDiagnostic
     *
     * @return void
     */
    protected function generateDiagnostic(): void
    {
        $student = $this->getStudent();
        $responses = $this->getStudentResponses();

        if (!$student || $responses->isEmpty()) {
            $this->console->error('No student or response data found.');
            return;
        }

        try {
            $latest = $responses->last();

            $end = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $latest['completed']);

            $this->console->info("\n{$student['firstName']} {$student['lastName']} recently completed Numeracy assessment on " . $end->format('jS F Y h:i A'));
            $this->console->line("He got {$latest['results']['rawScore']} questions right out of 16. Details by strand given below:\n");

            $strandSummary = [];

            foreach ($latest['responses'] as $response) {
                $question = collect($this->questions)->firstWhere('id', $response['questionId']);
                if (!$question) {
                    continue;
                }

                $strand = $question['strand'];
                $correctOptionId = $question['config']['key'] ?? null;
                $isCorrect = ($response['response'] === $correctOptionId);

                if (!isset($strandSummary[$strand])) {
                    $strandSummary[$strand] = ['correct' => 0, 'total' => 0];
                }

                $strandSummary[$strand]['total']++;
                if ($isCorrect) {
                    $strandSummary[$strand]['correct']++;
                }
            }

            foreach ($strandSummary as $strand => $score) {
                $this->console->line("$strand: {$score['correct']} out of {$score['total']} correct");
            }
        } catch (\Throwable $e) {
            $this->console->error("Error generating diagnostic report: " . $e->getMessage());
        }
    }

    /**
     * generateProgress
     *
     * @return void
     */
    protected function generateProgress(): void
    {
        $student = $this->getStudent();
        $responses = $this->getStudentResponses();

        if (!$student || $responses->isEmpty()) {
            $this->console->error('No student or response data found.');
            return;
        }

        try {
            $name = "{$student['firstName']} {$student['lastName']}";
            $assessmentName = "Numeracy";
            $count = $responses->count();

            $this->console->info("\n{$name} has completed {$assessmentName} assessment {$count} times in total. Date and raw score given below:\n");

            foreach ($responses as $response) {
                $date = Carbon::createFromFormat('d/m/Y H:i:s', $response['assigned'])->format('jS F Y');
                $rawScore = $response['results']['rawScore'] ?? 0;
                $this->console->line("Date: {$date}, Raw Score: {$rawScore} out of 16");
            }

            $oldest = $responses->first();
            $latest = $responses->last();

            $oldestScore = $oldest['results']['rawScore'] ?? 0;
            $latestScore = $latest['results']['rawScore'] ?? 0;
            $scoreDiff = $latestScore - $oldestScore;

            $this->console->line("\n{$name} got {$scoreDiff} more correct in the recent completed assessment than the oldest\n");
        } catch (\Throwable $e) {
            $this->console->error("Error generating progress report: " . $e->getMessage());
        }
    }

    /**
     * generateFeedback
     *
     * @return void
     */
    protected function generateFeedback(): void
    {
        $student = $this->getStudent();
        $responses = $this->getStudentResponses();

        if (!$student || $responses->isEmpty()) {
            $this->console->error('No student or response data found.');
            return;
        }

        try {
            $latest = $responses->last();
            $completedAt = Carbon::createFromFormat('d/m/Y H:i:s', $latest['completed']);
            $name = "{$student['firstName']} {$student['lastName']}";
            $correctCount = $latest['results']['rawScore'] ?? 0;
            $totalQuestions = count($latest['responses'] ?? []);

            $this->console->info("\n{$name} recently completed Numeracy assessment on " . $completedAt->format('jS F Y h:i A'));
            $this->console->line("He got {$correctCount} questions right out of {$totalQuestions}. Feedback for wrong answers given below\n");

            foreach ($latest['responses'] as $response) {
                $question = collect($this->questions)->firstWhere('id', $response['questionId']);

                if (!$question) {
                    $this->console->warn("Question not found for ID: {$response['questionId']}");
                    continue;
                }

                $correctOptionId = $question['config']['key'] ?? null;
                $isCorrect = ($response['response'] === $correctOptionId);

                if ($isCorrect) {
                    continue;
                }

                $selectedOption = collect($question['config']['options'])->firstWhere('id', $response['response']);
                $correctOption = collect($question['config']['options'])->firstWhere('id', $correctOptionId);

                $questionText = $question['stem'] ?? '[Question text not available]';
                $selectedLabel = $selectedOption['label'] ?? 'N/A';
                $selectedValue = $selectedOption['value'] ?? 'N/A';
                $correctLabel = $correctOption['label'] ?? 'N/A';
                $correctValue = $correctOption['value'] ?? 'N/A';
                $hint = $question['config']['hint'] ?? 'No hint available.';

                $this->console->line("\nQuestion: {$questionText}");
                $this->console->line("Your answer: {$selectedLabel} with value {$selectedValue}");
                $this->console->line("Right answer: {$correctLabel} with value {$correctValue}");
                $this->console->line("Hint: {$hint}");
            }
        } catch (\Throwable $e) {
            $this->console->error("Error generating feedback report: " . $e->getMessage());
        }
    }
}
