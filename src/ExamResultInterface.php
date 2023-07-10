<?php

namespace Miavaka\MenExamResult;

interface ExamResultInterface
{
    public function search(string $query): string;
}