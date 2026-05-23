<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $students = [
            ['first_name' => 'Juan',  'last_name' => 'Dela Cruz', 'username' => 'student01', 'email' => 'student01@school.edu'],
            ['first_name' => 'Maria', 'last_name' => 'Santos',    'username' => 'student02', 'email' => 'student02@school.edu'],
            ['first_name' => 'Jose',  'last_name' => 'Reyes',     'username' => 'student03', 'email' => 'student03@school.edu'],
            ['first_name' => 'Ana',   'last_name' => 'Garcia',    'username' => 'student04', 'email' => 'student04@school.edu'],
            ['first_name' => 'Luis',  'last_name' => 'Mendoza',   'username' => 'student05', 'email' => 'student05@school.edu'],
        ];

        foreach ($students as $s) {
            User::create([
                'username'   => $s['username'],
                'email'      => $s['email'],
                'password'   => bcrypt('password'),
                'role'       => 'student',
                'first_name' => $s['first_name'],
                'last_name'  => $s['last_name'],
                'phone'      => null,
                'status'     => 'active',
            ]);
        }

        echo "Done! 5 students created.\n";
    }
}