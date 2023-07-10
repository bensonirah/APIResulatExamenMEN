<?php

namespace Miavaka\MenExamResult;

interface DataProviderInterface
{
    public function get(string $query, string $typeRec, string $exam): array;
}