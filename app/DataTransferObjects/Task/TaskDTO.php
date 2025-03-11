<?php

namespace App\DataTransferObjects\Task;

class TaskDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $desc,
        public string $day_of_week,
        public string $start_time,
        public string $end_time,
        public bool $all_day,
        public bool $is_reccurring,
        public bool $is_fixed,
        public ?string $deadline,
        public object $start_time_attributes,
        public object $end_time_attributes,
        public ?object $deadline_attributes
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            desc: $data['desc'],
            day_of_week: $data['day_of_week'],
            start_time: $data['start_time'],
            end_time: $data['end_time'],
            all_day: (bool) $data['all_day'],
            is_reccurring: (bool) $data['is_reccurring'],
            is_fixed: (bool) $data['is_reccurring'],
            deadline: $data['deadline'] ?? null,
            start_time_attributes: self::parseDateTime($data['start_time']),
            end_time_attributes: self::parseDateTime($data['end_time']),
            deadline_attributes: isset($data['deadline']) ? self::parseDateTime($data['deadline']) : null
        );
    }

    public static function parseDateTime(string $datetime): object
    {
        $date = new \DateTime($datetime);
        return (object) [
            'year' => (int) $date->format('Y'),
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'hour' => (int) $date->format('H'),
            'minute' => (int) $date->format('i'),
            'second' => (int) $date->format('s')
        ];
    }
}
