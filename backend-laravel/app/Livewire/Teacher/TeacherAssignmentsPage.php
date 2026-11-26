<?php

namespace App\\Livewire\\Teacher;

use App\\Livewire\\Admin\\TeacherSubjectAssignmentManagement;

/**
 * Guru-facing route reuses the existing validated assignment workflow.
 * Ownership is resolved from the authenticated Guru profile by the shared component.
 */
class TeacherAssignmentsPage extends TeacherSubjectAssignmentManagement
{
}
