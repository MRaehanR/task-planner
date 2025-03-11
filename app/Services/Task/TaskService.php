<?php

namespace App\Services\Task;

interface TaskService
{
    public function getTasks(array $params = []);
    public function getTaskById(int $id);
    public function deleteTaskById(int $id);
}
