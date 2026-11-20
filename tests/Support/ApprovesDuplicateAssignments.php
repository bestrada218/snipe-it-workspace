<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Checking out two or more units of the same category to one person needs a
 * recorded approval (App\Services\DuplicateAssignments). Tests that exercise
 * multi-unit checkouts merge this payload into the request.
 */
trait ApprovesDuplicateAssignments
{
    /**
     * @return array{duplicate_assignment_confirmed: int, duplicate_assignment_reason: string, duplicate_assignment_ticket: string, duplicate_assignment_approver: int, duplicate_assignment_evidence: UploadedFile}
     */
    protected function duplicateAssignmentApproval(): array
    {
        Storage::fake();
        $approver = User::factory()->create();
        $this->settings->set(['duplicate_assignment_approvers' => [$approver->id]]);

        return [
            'duplicate_assignment_confirmed' => 1,
            'duplicate_assignment_reason' => 'Approved for testing',
            'duplicate_assignment_ticket' => 'INC-1001',
            'duplicate_assignment_approver' => $approver->id,
            'duplicate_assignment_evidence' => UploadedFile::fake()->create('approval.pdf', 10, 'application/pdf'),
        ];
    }
}
