<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Ai;

use App\Domain\Service\AiCardGeneratorInterface;
use App\Domain\Service\GenerateCardsResult;
use App\Domain\Value\CardPreview;
use App\Domain\Value\SourceText;
use App\Domain\Value\SuggestedSetName;

/**
 * Mock implementation of AI card generator for testing and development.
 *
 * This mock generator:
 * - Returns hardcoded example flashcards
 * - Extracts suggested name from first words of source text
 * - Simulates AI metadata (model name, token usage)
 *
 * Use this during development/testing when you don't have OpenRouter API key
 * or want predictable results for tests.
 */
final class MockAiCardGenerator implements AiCardGeneratorInterface
{
    public function generate(SourceText $sourceText): GenerateCardsResult
    {
        // Extract topic from first few words for suggested name
        $suggestedName = $this->extractSuggestedName($sourceText);

        // Generate example cards based on source text length
        $cards = $this->generateExampleCards($sourceText);

        // Simulate AI metadata
        $modelName = 'mock-ai-model';
        $tokensIn = (int) ($sourceText->length() / 4); // rough approximation
        $tokensOut = count($cards) * 50; // rough approximation

        return new GenerateCardsResult(
            cards: $cards,
            suggestedName: $suggestedName,
            modelName: $modelName,
            tokensIn: $tokensIn,
            tokensOut: $tokensOut
        );
    }

    /**
     * Extract suggested name from first 50 characters of source text.
     */
    private function extractSuggestedName(SourceText $sourceText): SuggestedSetName
    {
        $text = $sourceText->toString();
        $firstWords = mb_substr($text, 0, 50, 'UTF-8');

        // Remove newlines and extra spaces
        $cleaned = preg_replace('/\s+/', ' ', $firstWords);
        $cleaned = trim($cleaned);

        // Add ellipsis if truncated
        if (mb_strlen($text, 'UTF-8') > 50) {
            $cleaned .= '...';
        }

        return SuggestedSetName::fromString($cleaned ?: 'Generated Flashcard Set');
    }

    /**
     * Generate example flashcards (10-15 cards based on text length).
     *
     * @return CardPreview[]
     */
    private function generateExampleCards(SourceText $sourceText): array
    {
        $length = $sourceText->length();
        $cardCount = min(15, max(10, (int) ($length / 500))); // 10-15 cards

        $cards = [];
        for ($i = 1; $i <= $cardCount; ++$i) {
            $cards[] = CardPreview::create(
                front: sprintf('Pytanie %d: Co to jest pojęcie %d z tekstu?', $i, $i),
                back: sprintf(
                    'Odpowiedź %d: To jest wyjaśnienie pojęcia %d, które pojawia się w tekście źródłowym. '.
                    'Jest to przykładowa odpowiedź wygenerowana przez mock generator.',
                    $i,
                    $i
                )
            );
        }

        return $cards;
    }
}
