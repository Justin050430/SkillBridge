<?php

/**
 * @author PUAH ZHAN ZHENG
 */

namespace App\Factories;

use App\Models\User;

class MentorAccountFactory extends UserAccountFactory
{
    protected function makeUser(array $data): User
    {
        return $this->buildUser($data, 'Mentor');
    }
}
