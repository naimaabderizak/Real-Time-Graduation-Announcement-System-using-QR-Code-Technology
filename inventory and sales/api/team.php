<?php
/**
 * api/team.php
 * 
 * Team API Endpoint
 * 
 * Returns a static list of team members and their roles.
 * Used for the "Our Team" page.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$team = [
    ['name' => 'Saleh Nasser Ahmed', 'role' => 'Team Leader & Full Stack Dev', 'img' => 'saleh.jpg', 'is_leader' => true],
    ['name' => 'Naima Abdirizak Ahmed', 'role' => 'Software Developer', 'img' => 'naima.jpg', 'is_leader' => false],
    ['name' => 'Abdihamid Abdi Nunow', 'role' => 'UI/UX Designer', 'img' => 'abdihamid.jpg', 'is_leader' => false],
    ['name' => 'Ibrahim Mumin Ali', 'role' => 'Backend Developer', 'img' => 'ibrahim.jpg', 'is_leader' => false],
    ['name' => 'Ayaan Mohamed', 'role' => 'Frontend Developer', 'img' => 'ayaan.jpg', 'is_leader' => false],
    ['name' => 'Abdulkadir Salah Ali', 'role' => 'Database Administrator', 'img' => 'abdulkadir.jpg', 'is_leader' => false],
    ['name' => 'Abdikafi Abdifitah Bare', 'role' => 'System Analyst', 'img' => 'abdikafi.jpg', 'is_leader' => false],
    ['name' => 'Maryam Abdifitah Bashiir', 'role' => 'QA Engineer', 'img' => 'maryam.jpg', 'is_leader' => false],
    ['name' => 'Salman Isse Adan', 'role' => 'DevOps Engineer', 'img' => 'salman.jpg', 'is_leader' => false],
];

echo json_encode(['success' => true, 'data' => $team]);
?>
