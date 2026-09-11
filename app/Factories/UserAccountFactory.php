<?php

/**
 * @author PUAH ZHAN ZHENG
 */

namespace App\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

abstract class UserAccountFactory
{
    final public function createAccount(array $data): User
    {
        $user = $this->makeUser($data);
        $user->save();

        return $user;
    }

    /** Factory Method: concrete creators construct the account for their role. */
    abstract protected function makeUser(array $data): User;

    /**
     * Share field mapping and hashing between creators without fixed role IDs.
     * The returned model is saved by createAccount().
     */
    protected function buildUser(array $data, string $roleName): User
    {
        $role = Role::query()
            ->where('role_name', $roleName)
            ->firstOrFail();

        return new User([
            'role_id' => $role->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'Active',
        ]);
    }
}
