<?php

/**
 * @author PUAH ZHAN ZHENG
 */

namespace App\Services;

use App\Factories\AdminAccountFactory;
use App\Factories\MentorAccountFactory;
use App\Factories\StudentAccountFactory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected StudentAccountFactory $studentAccountFactory,
        protected MentorAccountFactory $mentorAccountFactory,
        protected AdminAccountFactory $adminAccountFactory,
    ) {}

    public function registerStudent(array $data): User
    {
        return $this->studentAccountFactory->createAccount($data);
    }

    public function createMentor(array $data): User
    {
        return $this->mentorAccountFactory->createAccount($data);
    }

    public function createAdmin(array $data): User
    {
        return $this->adminAccountFactory->createAccount($data);
    }
    
    public function createUser(array $data): User
    {
        $roleName = Role::query()
            ->whereKey($data['role_id'])
            ->value('role_name');

        return match ($roleName) {
            'Student' => $this->registerStudent($data),
            'Mentor' => $this->createMentor($data),
            'Admin' => $this->createAdmin($data),
            default => throw ValidationException::withMessages([
                'role_id' => 'The selected role is not supported.',
            ]),
        };
    }
}
