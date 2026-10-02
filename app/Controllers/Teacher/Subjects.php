<?php

namespace App\Controllers\Teacher;

class Subjects extends TeacherController
{
    public function index(): string
    {
        $grouped = [];
        foreach ($this->offerings() as $o) {
            $grouped[term_label($o)][] = $o;
        }

        return $this->page('teacher/subjects', [
            'title'    => 'My subjects',
            'subtitle' => 'Every subject assigned to you, by term.',
            'grouped'  => $grouped,
        ]);
    }
}
