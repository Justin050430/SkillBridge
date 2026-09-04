<?php

/**
 * @author PUAH ZHAN ZHENG
 */

namespace App\Services;

use App\Factories\UserAccountFactory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserService
{
    protected UserAccountFactory $userAccountFactory;

    public function __construct(UserAccountFactory $userAccountFactory)
    {
        $this->userAccountFactory = $userAccountFactory;
    }

    public function registerStudent(array $data): User
    {
        return $this->userAccountFactory->createStudent($data);
    }

    public function createMentor(array $data): User
    {
        return $this->userAccountFactory->createMentor($data);
    }

    public function createAdmin(array $data): User
    {
        return $this->userAccountFactory->createAdmin($data);
    }

    /**
     * Create an account selected by an Administrator through the user form.
     */
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
