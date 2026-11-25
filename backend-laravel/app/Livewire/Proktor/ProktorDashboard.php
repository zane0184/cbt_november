<?php

namespace App\Livewire\Proktor;

use Livewire\Component;

/**
 * Retired compatibility stub. There is no permanent Proktor portal.
 * Temporary proctor assignment is an administrative record and grants no
 * monitoring, roster, Attempt, answer, score, or CBT operation access.
 */
class ProktorDashboard extends Component
{
    public function mount(): void
    {
        abort(403, 'Portal Proktor permanen tidak tersedia. Penugasan Guru tidak membuka akses monitoring CBT.');
    }

    public function render()
    {
        abort(403, 'Portal Proktor permanen tidak tersedia.');
    }
}
