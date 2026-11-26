<?php

namespace App\Services\Questions;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Validation\ValidationException;

final class OmmlToMathMlConverter
{
    private const MATH_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/math';

    private array $unsupported = [];

    /**
     * Convert the common Word OMML constructs into safe MathML.
     * Original OMML is retained separately in the immutable question metadata.
     *
     * @return array{mathml:string,fallback_text:string,supported:bool,unsupported_nodes:array<int,string>}
     */
    public function convert(string $ommlXml): array
    {
        if ($ommlXml === '' || preg_match('/<!DOCTYPE|<!ENTITY/i', $ommlXml) === 1) {
            throw ValidationException::withMessages(['docx' => 'Sumber rumus Word tidak valid.']);
        }

        $document = new DOMDocument();
        if (!$document->loadXML($ommlXml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            throw ValidationException::withMessages(['docx' => 'Sumber rumus Word tidak dapat dibaca.']);
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('m', self::MATH_NS);
        $mathNode = $xpath->query('//*[local-name()="oMath" or local-name()="oMathPara"]')->item(0);
        if (!$mathNode) {
            throw ValidationException::withMessages(['docx' => 'Objek rumus Word tidak ditemukan pada fragmen sumber.']);
        }

        $this->unsupported = [];
        $body = $this->renderChildren($mathNode, $xpath);
        $fallback = $this->extractFallbackText($mathNode, $xpath);
        $unsupported = array_values(array_unique($this->unsupported));

        return [
            'mathml' => '<math xmlns="http://www.w3.org/1998/Math/MathML" display="block"><mrow>' . $body . '</mrow></math>',
            'fallback_text' => $fallback,
            'supported' => $unsupported === [],
            'unsupported_nodes' => $unsupported,
        ];
    }

    private function renderChildren(DOMNode $node, \DOMXPath $xpath): string
    {
        $output = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $output .= $this->renderNode($child, $xpath);
            }
        }

        return $output;
    }

    private function renderNode(DOMElement $node, \DOMXPath $xpath): string
    {
        $name = $node->localName;
        if (str_ends_with($name, 'Pr') || in_array($name, ['ctrlPr', 'argPr', 'endParaRPr', 'rPr'], true)) {
            return '';
        }

        return match ($name) {
            'oMath', 'oMathPara', 'e', 'num', 'den', 'sub', 'sup', 'deg', 'fName', 'lim', 'mr' => $this->renderChildren($node, $xpath),
            'r' => $this->renderRun($node, $xpath),
            't' => $this->token($node->textContent),
            'f' => $this->renderFraction($node, $xpath),
            'sSup' => $this->renderScript($node, $xpath, 'msup', ['e', 'sup']),
            'sSub' => $this->renderScript($node, $xpath, 'msub', ['e', 'sub']),
            'sSubSup' => $this->renderScript($node, $xpath, 'msubsup', ['e', 'sub', 'sup']),
            'rad' => $this->renderRadical($node, $xpath),
            'd' => $this->renderDelimiter($node, $xpath),
            'nary' => $this->renderNary($node, $xpath),
            'func' => $this->renderFunction($node, $xpath),
            'acc' => $this->renderAccent($node, $xpath),
            'bar' => $this->renderBar($node, $xpath),
            'limLow' => $this->renderLimit($node, $xpath, 'munder'),
            'limUpp' => $this->renderLimit($node, $xpath, 'mover'),
            'matrix' => $this->renderMatrix($node, $xpath),
            'eqArr' => $this->renderEquationArray($node, $xpath),
            'groupChr' => $this->renderGroupCharacter($node, $xpath),
            'borderBox' => '<menclose notation="box"><mrow>' . $this->renderChildren($node, $xpath) . '</mrow></menclose>',
            'box' => '<menclose notation="roundedbox"><mrow>' . $this->renderChildren($node, $xpath) . '</mrow></menclose>',
            'phant' => '',
            'degHide' => '',
            'br' => '<mspace linebreak="newline"/>',
            default => $this->renderUnknown($node, $xpath),
        };
    }

    private function renderRun(DOMElement $node, \DOMXPath $xpath): string
    {
        $text = '';
        foreach ($xpath->query('.//m:t', $node) as $textNode) {
            $text .= $textNode->textContent;
        }

        return $this->token($text);
    }

    private function token(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        if (preg_match('/^\p{N}+(?:[.,]\p{N}+)?$/u', $text) === 1) {
            return '<mn>' . $escaped . '</mn>';
        }
        if (preg_match('/^[\p{L}\p{M}]+$/u', $text) === 1) {
            return '<mi>' . $escaped . '</mi>';
        }
        if (preg_match('/^[=+\-*\/<>≤≥≠≈±×÷∑∏∫√∞(),.;:!%|{}\[\]]+$/u', $text) === 1) {
            return '<mo>' . $escaped . '</mo>';
        }

        return '<mtext>' . $escaped . '</mtext>';
    }

    private function renderFraction(DOMElement $node, \DOMXPath $xpath): string
    {
        $numerator = $xpath->query('./m:num', $node)->item(0);
        $denominator = $xpath->query('./m:den', $node)->item(0);

        return '<mfrac><mrow>' . ($numerator ? $this->renderChildren($numerator, $xpath) : '') . '</mrow><mrow>'
            . ($denominator ? $this->renderChildren($denominator, $xpath) : '')
            . '</mrow></mfrac>';
    }

    private function renderScript(DOMElement $node, \DOMXPath $xpath, string $tag, array $parts): string
    {
        $output = '<' . $tag . '>';
        foreach ($parts as $part) {
            $child = $xpath->query('./m:' . $part, $node)->item(0);
            $output .= '<mrow>' . ($child ? $this->renderChildren($child, $xpath) : '') . '</mrow>';
        }

        return $output . '</' . $tag . '>';
    }

    private function renderRadical(DOMElement $node, \DOMXPath $xpath): string
    {
        $degree = $xpath->query('./m:deg', $node)->item(0);
        $expression = $xpath->query('./m:e', $node)->item(0);
        $degreeText = trim($degree?->textContent ?? '');
        if ($degreeText === '') {
            return '<msqrt><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '') . '</mrow></msqrt>';
        }

        return '<mroot><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '') . '</mrow><mrow>'
            . ($degree ? $this->renderChildren($degree, $xpath) : '')
            . '</mrow></mroot>';
    }

    private function renderDelimiter(DOMElement $node, \DOMXPath $xpath): string
    {
        $properties = $xpath->query('./m:dPr', $node)->item(0);
        $open = $properties ? $xpath->query('./m:begChr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $close = $properties ? $xpath->query('./m:endChr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $separators = $properties ? $xpath->query('./m:sepChr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $expression = $xpath->query('./m:e', $node)->item(0);

        return '<mfenced open="' . htmlspecialchars($open ?: '(', ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '" close="' . htmlspecialchars($close ?: ')', ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '" separators="' . htmlspecialchars($separators ?: ',', ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '"><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '') . '</mrow></mfenced>';
    }

    private function renderNary(DOMElement $node, \DOMXPath $xpath): string
    {
        $properties = $xpath->query('./m:naryPr', $node)->item(0);
        $operator = $properties ? $xpath->query('./m:chr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $operator = $operator ?: '∑';
        $sub = $xpath->query('./m:sub', $node)->item(0);
        $sup = $xpath->query('./m:sup', $node)->item(0);
        $expression = $xpath->query('./m:e', $node)->item(0);
        $operatorXml = '<mo>' . htmlspecialchars($operator, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</mo>';

        if ($sub && $sup) {
            $operatorXml = '<munderover>' . $operatorXml . '<mrow>' . $this->renderChildren($sub, $xpath)
                . '</mrow><mrow>' . $this->renderChildren($sup, $xpath) . '</mrow></munderover>';
        } elseif ($sub) {
            $operatorXml = '<munder>' . $operatorXml . '<mrow>' . $this->renderChildren($sub, $xpath) . '</mrow></munder>';
        } elseif ($sup) {
            $operatorXml = '<mover>' . $operatorXml . '<mrow>' . $this->renderChildren($sup, $xpath) . '</mrow></mover>';
        }

        return '<mrow>' . $operatorXml . '<mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '') . '</mrow></mrow>';
    }

    private function renderFunction(DOMElement $node, \DOMXPath $xpath): string
    {
        $name = $xpath->query('./m:fName', $node)->item(0);
        $expression = $xpath->query('./m:e', $node)->item(0);

        return '<mrow>' . ($name ? $this->renderChildren($name, $xpath) : '')
            . '<mo>⁡</mo><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '') . '</mrow></mrow>';
    }

    private function renderAccent(DOMElement $node, \DOMXPath $xpath): string
    {
        $properties = $xpath->query('./m:accPr', $node)->item(0);
        $character = $properties ? $xpath->query('./m:chr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $expression = $xpath->query('./m:e', $node)->item(0);

        return '<mover accent="true"><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '')
            . '</mrow><mo>' . htmlspecialchars($character ?: 'ˆ', ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</mo></mover>';
    }

    private function renderBar(DOMElement $node, \DOMXPath $xpath): string
    {
        $properties = $xpath->query('./m:barPr', $node)->item(0);
        $position = $properties ? $xpath->query('./m:pos', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : 'top';
        $expression = $xpath->query('./m:e', $node)->item(0);
        $tag = $position === 'bot' ? 'munder' : 'mover';

        return '<' . $tag . ' accent="true"><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '')
            . '</mrow><mo>¯</mo></' . $tag . '>';
    }

    private function renderLimit(DOMElement $node, \DOMXPath $xpath, string $tag): string
    {
        $expression = $xpath->query('./m:e', $node)->item(0);
        $limit = $xpath->query('./m:lim', $node)->item(0);

        return '<' . $tag . '><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '')
            . '</mrow><mrow>' . ($limit ? $this->renderChildren($limit, $xpath) : '')
            . '</mrow></' . $tag . '>';
    }

    private function renderMatrix(DOMElement $node, \DOMXPath $xpath): string
    {
        $rows = '';
        foreach ($xpath->query('./m:mr', $node) as $row) {
            $cells = '';
            foreach ($xpath->query('./m:e', $row) as $cell) {
                $cells .= '<mtd><mrow>' . $this->renderChildren($cell, $xpath) . '</mrow></mtd>';
            }
            $rows .= '<mtr>' . $cells . '</mtr>';
        }

        return '<mtable>' . $rows . '</mtable>';
    }

    private function renderEquationArray(DOMElement $node, \DOMXPath $xpath): string
    {
        $rows = '';
        foreach ($xpath->query('./m:e', $node) as $row) {
            $rows .= '<mtr><mtd><mrow>' . $this->renderChildren($row, $xpath) . '</mrow></mtd></mtr>';
        }

        return '<mtable>' . $rows . '</mtable>';
    }

    private function renderGroupCharacter(DOMElement $node, \DOMXPath $xpath): string
    {
        $properties = $xpath->query('./m:groupChrPr', $node)->item(0);
        $character = $properties ? $xpath->query('./m:chr', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : null;
        $position = $properties ? $xpath->query('./m:pos', $properties)->item(0)?->getAttributeNS(self::MATH_NS, 'val') : 'top';
        $expression = $xpath->query('./m:e', $node)->item(0);
        $tag = $position === 'bot' ? 'munder' : 'mover';

        return '<' . $tag . '><mrow>' . ($expression ? $this->renderChildren($expression, $xpath) : '')
            . '</mrow><mo>' . htmlspecialchars($character ?: '⏞', ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</mo></' . $tag . '>';
    }

    private function renderUnknown(DOMElement $node, \DOMXPath $xpath): string
    {
        $this->unsupported[] = $node->localName;

        return $this->renderChildren($node, $xpath);
    }

    private function extractFallbackText(DOMNode $node, \DOMXPath $xpath): string
    {
        $parts = [];
        foreach ($xpath->query('.//m:t', $node) as $textNode) {
            $parts[] = $textNode->textContent;
        }

        return trim(implode('', $parts));
    }
}
