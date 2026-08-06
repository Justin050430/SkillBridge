<?php

namespace App\Services;

use App\Factories\UserAccountFactory;
use App\Models\User;

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
}