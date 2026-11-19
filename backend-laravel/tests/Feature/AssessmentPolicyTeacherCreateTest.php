<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use Tests\TestCase;

class AssessmentPolicyTeacherCreateTest extends TestCase
{
    public function test_teacher_role_can_reach_assignment_validation_when_creating_assessment(): void
    {
        $teacher = new User();
        $teacher->setRelation('roles', collect([
            new Role(['name' => 'GURU']),
        ]));

        $this->assertTrue((new AssessmentPolicy())->create($teacher));
    }

    public function test_student_role_cannot_create_assessment(): void
    {
        $student = new User();
        $student->setRelation('roles', collect([
            new Role(['name' => 'SISWA']),
        ]));

        $this->assertFalse((new AssessmentPolicy())->create($student));
    }
}
