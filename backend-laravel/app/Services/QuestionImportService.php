<?php

namespace App\Services;

interface QuestionImportService
{
    /**
     * Import questions from a file.
     *
     * @param string $filePath
     * @param array $options
     * @return array Summary of import results
     */
    public function import(string $filePath, array $options = []): array;
}
