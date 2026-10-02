<?php

namespace App\Controllers\Student;

use App\Services\ClearanceService;
use App\Services\FileStorage;
use App\Services\ReceiptService;

class Clearance extends StudentController
{
    public function start()
    {
        return $this->act(function () {
            $clearance = (new ClearanceService())->start($this->studentId());

            return "Clearance {$clearance['reference_no']} started. Upload your tuition receipt to continue.";
        }, 'Clearance started.', 'student/receipt');
    }

    public function receipt(): string
    {
        [$term, $clearance] = $this->current();

        return $this->page('student/receipt', [
            'title'     => 'Tuition receipt',
            'subtitle'  => 'Upload your official receipt so the registrar can open your clearance card.',
            'term'      => $term,
            'bundle'    => (new ClearanceService())->bundle($clearance),
            'maxBytes'  => FileStorage::maxBytes(),
        ]);
    }

    public function uploadReceipt()
    {
        [, $clearance] = $this->current();
        if (! $clearance) {
            return redirect()->to(site_url('student/dashboard'))->with('toast_error', 'Start your clearance application first.');
        }

        return $this->act(function () use ($clearance) {
            (new ReceiptService())->submitTuition(
                $this->studentId(),
                (int) $clearance['id'],
                $this->request->getFile('receipt'),
                $this->request->getPost(['or_number', 'amount', 'payment_date']),
            );

            return 'Receipt submitted. The registrar will review it shortly.';
        }, '', 'student/receipt');
    }

    public function card(): string
    {
        [$term, $clearance] = $this->current();
        $svc    = new ClearanceService();
        $bundle = $svc->bundle($clearance);
        $ids    = array_map('intval', array_column($bundle['subjects'], 'id'));

        return $this->page('student/card', [
            'title'         => 'Clearance card',
            'subtitle'      => 'Your official subject clearance for ' . term_label($term) . '.',
            'term'          => $term,
            'bundle'        => $bundle,
            'reenrollments' => $svc->latestReenrollmentReceipts($ids),
        ]);
    }

    public function subjects(): string
    {
        [$term, $clearance] = $this->current();
        $svc    = new ClearanceService();
        $bundle = $svc->bundle($clearance);
        $ids    = array_map('intval', array_column($bundle['subjects'], 'id'));

        return $this->page('student/subjects', [
            'title'        => 'Subject status',
            'subtitle'     => 'Only teachers can mark a subject outcome. Here is where each subject stands.',
            'term'         => $term,
            'bundle'       => $bundle,
            'requirements' => $svc->requirementsFor($ids),
            'reenrollments' => $svc->latestReenrollmentReceipts($ids),
        ]);
    }
}
