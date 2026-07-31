<?php

namespace App\Services;

class ContractChunker
{
    /**
     * Create a new class instance.
     */
    public function chunk(string $text, int $targetWords = 500): array
    {
        // Split on blank-line-delimited paragraphs (the natural section/paragraph breaks).
        $rawParagraphs = preg_split("/\n\s*\n/", trim($text));

        $isHeading = function (string $para): bool {
            $firstLine = trim(strtok($para, "\n"));
            // Heuristic: short line, no terminal punctuation, looks like "TITLE" or "1. TITLE".
            return strlen($firstLine) > 0
                && strlen($firstLine) < 80
                && !preg_match('/[.;:]$/', $firstLine)
                && (ctype_upper(preg_replace('/[^A-Za-z]/', '', $firstLine)) || preg_match('/^\d+\.\s/', $firstLine));
        };

        $chunks = [];
        $currentHeading = null;
        $bufferParas = [];
        $bufferWordCount = 0;

        $flush = function () use (&$bufferParas, &$bufferWordCount, &$currentHeading, &$chunks) {
            if (empty($bufferParas)) {
                return;
            }
            $body = implode("\n\n", $bufferParas);
            $chunks[] = [
                'heading' => $currentHeading,
                'text' => $currentHeading ? $currentHeading . "\n\n" . $body : $body,
                'word_count' => $bufferWordCount,
            ];
            $bufferParas = [];
            $bufferWordCount = 0;
        };

        foreach ($rawParagraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            if ($isHeading($para)) {
                // Heading starts a new section: flush whatever we were accumulating.
                $flush();
                $currentHeading = trim(strtok($para, "\n"));
                continue;
            }

            $wordCount = count(preg_split('/\s+/', $para));

            // If adding this paragraph would overshoot the target and we already
            // have content buffered, flush first so chunks stay near targetWords.
            if ($bufferWordCount > 0 && $bufferWordCount + $wordCount > $targetWords) {
                $flush();
            }

            $bufferParas[] = $para;
            $bufferWordCount += $wordCount;
        }
        $flush();

        return $chunks;
    }
}
