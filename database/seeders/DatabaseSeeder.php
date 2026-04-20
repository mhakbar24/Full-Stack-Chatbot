<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@quiz.local'],
            [
                'name' => 'Admin Quiz',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'guru@quiz.local'],
            [
                'name' => 'Guru Quiz',
                'password' => Hash::make('guru12345'),
                'role' => 'guru',
            ]
        );

        Teacher::query()->get()->each(function (Teacher $teacher): void {
            User::updateOrCreate(
                ['email' => $teacher->email],
                [
                    'name' => $teacher->name,
                    'password' => $teacher->password,
                    'role' => 'guru',
                ]
            );
        });

        Student::query()->get()->each(function (Student $student): void {
            User::updateOrCreate(
                ['email' => $student->email],
                [
                    'name' => $student->name,
                    'password' => $student->password,
                    'role' => 'siswa',
                ]
            );
        });
    }
}
